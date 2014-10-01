<?php

/**
 * @file
 * This integrates the drupal WinCache backend.
 */

// Array to collect usage statistics.
$wincache_statistics = array();

/**
 * Win cache implementation.
 *
 * This is Drupal's win cache implementation. It uses Wincache
 * to store cached data. Each cache bin corresponds to a prefix of
 * the wincache variables with the same name. The name of this class
 * is DrupalWinCache instead of WinCacheDrupal for historical reasons
 * and backwards compatibility.
 */
class DrupalWinCache implements DrupalCacheInterface {
  protected $bin;

  /**
   * The constructor.
   *
   * @param var $bin
   *   The bin parameter.
   */
  public function __construct($bin) {
    $this->bin = $bin;
  }

  /**
   * Manage statistics.
   */
  protected function AddStatistic($method, $keys, $wildcard = NULL) {
    global $wincache_collect_statistics;
    // $wincache_collect_statistics is set during hook_init in
    // WincacheDrupal module.
    // We might be collecting statistics prior to hook_init in all requests
    // but that is the only way to get them.
    if (!($wincache_collect_statistics == FALSE)) {
      $GLOBALS['wincache_statistics'][] = array($method, $this->bin, $keys, $wildcard);
    }
  }
  
  /**
   * Function which retrieves the safe key for the cache bin.
   *
   * @return var
   *   The safe WinCache key.
   */
  protected function binKey() {
    static $binkey = array();

    $test_info = &$GLOBALS['drupal_test_info'];
    $bin = (!empty($test_info['test_run_id']) ? ('_' . $test_info['test_run_id']) : '') . $this->bin;

    if (!isset($binkey[$bin])) {
      $binkey[$bin] = $_SERVER['HTTP_HOST'] . '|' . $bin . '|';
    }
    return $binkey[$bin];
  }

  /**
   * Function which retrieves the safe key for the cache cid.
   *
   * @param var $cid
   *   The cache id.
   *
   * @return var
   *   The safe WinCache key.
   */
  protected function key($cid) {
    return $this->binKey() . $cid;
  }

  /**
   * Function which retrieves a cached item.
   *
   * @param var $cid
   *   The cache id.
   *
   * @return var
   *   The cached item.
   */
  public function get($cid) {
    // Garbage collection necessary when enforcing a minimum cache lifetime.
    $this->garbageCollection($this->bin);

    // Add a get to our statistics.
    $this->AddStatistic('get', array($cid));

    $success = FALSE;

    // Fetch the data.
    $cache = wincache_ucache_get($this->key($cid), $success);

    // Should return FALSE on failure.
    if ($success === FALSE) {
      return FALSE;
    }

    // Return the prepared item.
    return $this->prepareItem($cache);
  }

  /**
   * Prepare a cached item.
   *
   * Checks that items are either permanent or did not expire, and unserializes
   * data as appropriate.
   *
   * @param var $cache
   *   An item loaded from cache_get() or cache_get_multiple().
   *
   * @return var
   *   The item with data unserialized as appropriate or FALSE if there is no
   *   valid item to load.
   */
  protected function prepareItem($cache) {
    global $user;

    if (!isset($cache->data)) {
      return FALSE;
    }
    // If the data is permanent or we are not enforcing a minimum cache lifetime
    // always return the cached data.
    if ($cache->expire == CACHE_PERMANENT || !variable_get('cache_lifetime', 0)) {
      if ($cache->serialized) {
        $cache->data = unserialize($cache->data);
      }
    }

    /*
      If enforcing a minimum cache lifetime, validate that the data is
      currently valid for this user before we return it by making sure
      the cache entry was created before the timestamp in the current
      session's cache timer. The cache variable is loaded into the $user
      object by _drupal_session_read()
      in session.inc. If the data is permanent or we're not enforcing a
      minimum cache lifetime always return the cached data.
    */
    if ($cache->expire != CACHE_PERMANENT && variable_get('cache_lifetime', 0) &&
        (isset($user->cache) && $user->cache > $cache->created)) {
      // This cache data is too old and thus not valid for us, ignore it.
      return FALSE;
    }

    if (isset($cache->headers)) {
      $cache->headers = unserialize($cache->headers);
    }

    return $cache;
  }

  /**
   * Get multiple cached items.
   *
   * @param array &$cids
   *   The array of keys.
   *
   * @return array
   *   The array of cached items.
   */
  public function getMultiple(&$cids) {
    // If requesting no $cids return empty array.
    // Otherwise warnings are triggered.
    if (empty($cids)) {
      return array();
    }

    // Garbage collection necessary when enforcing a minimum cache lifetime.
    $this->garbageCollection($this->bin);

    // We need to search the cache with the proper keys and
    // be able to get the original $cid back.
    foreach ($cids as $cid) {
      $keys[$this->key($cid)] = $cid;
    }

    $success = FALSE;
    $fetch = wincache_ucache_get(array_keys($keys), $success);
    $cache = array();
    if ($success === TRUE) {
      foreach ($fetch as $key => $data) {
        $cache[$keys[$key]] = $this->prepareItem($fetch[$key]);
      }
    }

    unset($fetch);
    // Add a get to our statistics.
    $this->AddStatistic('getMultiple', $cids);
    $cids = array_diff($cids, array_keys($cache));
    return $cache;
  }

  /**
   * Garbage collection for get and getMultiple methods.
   */
  protected function garbageCollection() {
    // Garbage collection necessary when enforcing a minimum cache lifetime.
    $cache_flush = variable_get('cache_flush_' . $this->bin, 0);
    if ($cache_flush && ($cache_flush + variable_get('cache_lifetime', 0) <= REQUEST_TIME)) {
      // Reset the variable immediately to prevent a meltdown in
      // heavy load situations.
      variable_set('cache_flush_' . $this->bin, 0);
      // Time to flush old cache data.
      $this->clearTemporary();
    }
  }

  /**
   * Set an item in the cache.
   *
   * @param var $cid
   *   The key of the data to be cached.
   * @param var $data
   *   The data to be cached.
   * @param var $expire
   *   The expire mode.
   * @param array $headers
   *   The headers.
   */
  public function set($cid, $data, $expire = CACHE_PERMANENT, array $headers = NULL) {
    // Add set to statistics.
    $this->AddStatistic('set', array($cid));

    // Create new cache object.
    $cache = new stdClass();
    $cache->cid = $cid;
    $cache->serialized = 0;
    $cache->created = REQUEST_TIME;
    $cache->expire = $expire;
    $cache->headers = isset($headers) ? serialize($headers) : NULL;

    if (is_object($data)) {
      $cache->serialized = 1;
      $cache->data = serialize($data);
    }
    else {
      $cache->data = $data;
    }

    // What kind of expiration is being used.
    switch ($expire) {
      case CACHE_PERMANENT:
        $set_result = wincache_ucache_set($this->key($cid), $cache);
        break;

      case CACHE_TEMPORARY:
        if (variable_get('cache_lifetime', 0) > 0) {
          $set_result = wincache_ucache_set(
              $this->key($cid),
              $cache,
              variable_get('cache_lifetime', 0)
          );
        }
        else {
          $set_result = wincache_ucache_set($this->key($cid), $cache);
        }
        break;

      default:
        $set_result = wincache_ucache_set(
            $this->key($cid),
            $cache,
            $expire - time()
        );
        break;
    }
  }

  /**
   * Clear cached item in temporary cached mode.
   */
  public function clearTemporary() {
    $data = wincache_ucache_info();
    $count = count($data['ucache_entries']);
    for ($i = 1; $i <= $count; $i++) {
      if (strpos($data['ucache_entries'][$i]['key_name'], $this->binKey()) === 0) {
        $cache = wincache_ucache_get($data['ucache_entries'][$i]['key_name']);
        if ($cache && $cache->expire == CACHE_TEMPORARY) {
          wincache_ucache_delete($data['ucache_entries'][$i]['key_name']);
        }
      }
    }
  }

  /**
   * Remove an item from the cache.
   *
   * @param var $cid
   *   The key of the data to be removed.
   * @param var $wildcard
   *   The wildcard.
   */
  public function clear($cid = NULL, $wildcard = FALSE) {
    global $user;

    // Add a get to our statistics.
    $this->AddStatistic('clear', $cid, (int) $wildcard);

    if (empty($cid)) {
      if (variable_get('cache_lifetime', 0)) {
        // We store the time in the current user's $user->cache variable which
        // will be saved into the sessions bin by _drupal_session_write(). We
        // then simulate that the cache was flushed for this user by not
        // returning cached data that was cached before the timestamp.
        $user->cache = REQUEST_TIME;

        $cache_flush = variable_get('cache_flush_' . $this->bin, 0);
        if ($cache_flush == 0) {
          // This is the first request to clear the cache, start a timer.
          variable_set('cache_flush_' . $this->bin, REQUEST_TIME);
        }
        elseif (REQUEST_TIME > ($cache_flush + variable_get('cache_lifetime', 0))) {
          // Clear the cache for everyone, cache_lifetime seconds have
          // passed since the first request to clear the cache.
          $this->clearTemporary();
          variable_set('cache_flush_' . $this->bin, 0);
        }
      }
      else {
        $this->clearTemporary();
      }
    }
    else {
      if ($wildcard) {

        $data = wincache_ucache_info();
        $count = count($data['ucache_entries']);
        if ($cid == '*') {
          for ($i = 1; $i <= $count; $i++) {
            if (strpos($data['ucache_entries'][$i]['key_name'], $this->binKey()) === 0) {
              wincache_ucache_delete($data['ucache_entries'][$i]['key_name']);
            }
          }
        }
        else {
          for ($i = 1; $i <= $count; $i++) {
            if (strpos($data['ucache_entries'][$i]['key_name'], $this->binKey() . $cid) === 0) {
              wincache_ucache_delete($data['ucache_entries'][$i]['key_name']);
            }
          }
        }
      }
      elseif (is_array($cid)) {
        foreach ($cid as $delete_cid) {
          wincache_ucache_delete($this->key($delete_cid));
        }
      }
      else {
        wincache_ucache_delete($this->key($cid));
      }
    }
  }

  /**
   * Checks cache is empty.
   *
   * @return bool
   *   TRUE if empty FALSE otherwise.
   */
  public function isEmpty() {
    $data = wincache_ucache_info();
    $count = count($data['ucache_entries']);
    for ($i = 1; $i <= $count; $i++) {
      if (strpos($data['ucache_entries'][$i]['key_name'], $this->binKey()) === 0) {
        return FALSE;
      }
    }
    return TRUE;
  }
}
