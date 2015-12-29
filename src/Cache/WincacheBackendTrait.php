<?php

namespace Drupal\wincachedrupal\Cache;

trait WincacheBackendTrait {

  /**
   * Returns a 12 character length MD5.
   *
   * @param string $string
   * @return string
   */
  public function shortMd5($string) {
    return substr(base_convert(md5($string), 16,32), 0, 12);
  }

  /**
   * Wrapper for wincache_ucache_set to properly manage expirations.
   *
   * @param string $cid
   * @param mixed $data
   * @param int $expire
   */
  protected function wincacheSet($cid, $data, $expire) {
    if ($ttl = $this->getTtl($expire)) {
      return wincache_ucache_set($cid, $data, $ttl);
    }
    else {
      return wincache_ucache_set($cid, $data);
    }
  }

  /**
   * Wrapper for wincache_ucache_add to properly manage expirations.
   *
   * @param string $cid
   * @param mixed $data
   * @param int $expire
   */
  protected function wincacheAdd($cid, $data, $expire) {
    $result = FALSE;
    set_error_handler(function() { /* Prevent Drupal from logging any exceptions or warning thrown here */ }, E_ALL);
    if ($ttl = $this->getTtl($expire)) {
      $result = @wincache_ucache_add($cid, $data, $ttl);
    }
    else {
      $result = @wincache_ucache_add($cid, $data);
    }
    restore_error_handler();
    return $result;
  }

  /**
   * The Cache API uses a unixtimestamp to set
   * expiration. But Wincache expects a TTL.
   *
   * @param int $expire
   */
  protected function getTtl($expire) {
    if ($expire == \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT) {
      // If no ttl is supplied (or if the ttl is 0), the value will persist until 
      // it is removed from the cache manually, or otherwise fails to exist in the cache (clear, restart, etc.).
      return FALSE;
    }
    $result = $expire - time();
    // Weird case, this is more or less like inmediate expiration...
    if ($result <= 0) {
      return 1;
    }
    return $result;
  }

  /**
   * Return all keys of cached items.
   *
   * @param string $prefix
   * @return array
   */
  public function getAllKeysWithPrefix($prefix) {
    $data = wincache_ucache_info();
    $k = array_column($data['ucache_entries'], 'key_name');
    $keys = preg_grep("/^$prefix/", $k);
    $keys = preg_replace("/^$prefix/", '', $keys);
    return $keys;
  }

  /**
   * Current time used to validate
   * cache item expiration times.
   *
   * @var mixed
   */
  protected $requestTime;

  /**
   * Refreshes the current request time.
   *
   * Uses the global REQUEST_TIME on the first
   * call and refreshes to current time on subsequen
   * requests.
   *
   * @param int $time
   */
  public function refreshRequestTime() {
    if (empty($this->requestTime)) {
      if (defined('REQUEST_TIME')) {
        $this->requestTime = REQUEST_TIME;
        return;
      }
      if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
        $this->requestTime = round($_SERVER['REQUEST_TIME_FLOAT'], 3);
        return;
      }
    }

    $this->requestTime = round(microtime(TRUE), 3);
  }
}
