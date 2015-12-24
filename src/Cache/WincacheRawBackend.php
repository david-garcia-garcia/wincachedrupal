<?php

/**
 * @file
 * Contains \Drupal\wincachedrupal\Cache\WincacheRawBackend.
 */

namespace Drupal\wincachedrupal\Cache;

use Drupal\supercache\Cache\CacheRawBackendInterface;

/**
 * Stores cache items in Wincache.
 */
class WincacheRawBackend implements CacheRawBackendInterface {

  /**
   * The Cache API uses a unixtimestamp to set
   * expiration. But Wincache expects a TTL.
   * 
   * @param int $expire
   */
  protected function getTtl($expire) {
    if ($expire == CacheRawBackendInterface::CACHE_PERMANENT) {
      // If no ttl is supplied (or if the ttl is 0), the value will persist until it is removed from the cache manually, or otherwise fails to exist in the cache (clear, restart, etc.).
      return 0;
    }
    return $expire - time();
  }

  /**
   * The name of the cache bin to use.
   *
   * @var string
   */
  protected $bin;

  /**
   * Prefix for all keys in the storage that belong to this site.
   *
   * @var string
   */
  protected $sitePrefix;

  /**
   * Prefix for all keys in this cache bin.
   *
   * Includes the site-specific prefix in $sitePrefix.
   *
   * @var string
   */
  protected $binPrefix;

  /**
   * Constructs a new WincacheRawBackend instance.
   *
   * @param string $bin
   *   The name of the cache bin.
   * @param string $site_prefix
   *   The prefix to use for all keys in the storage that belong to this site.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum provider.
   */
  public function __construct($bin, $site_prefix) {
    $this->bin = $bin;
    $this->sitePrefix = $site_prefix;
    $this->binPrefix = $this->sitePrefix . '::' . $this->bin . '::';
  }

  /**
   * Prepends the APC user variable prefix for this bin to a cache item ID.
   *
   * @param string $cid
   *   The cache item ID to prefix.
   *
   * @return string
   *   The APCu key for the cache item ID.
   */
  public function getKey($cid) {
    return $this->binPrefix . $cid;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid) {
    $success = FALSE;
    $data = wincache_ucache_get($this->getKey($cid), $success);
    if (!$success) {
      return FALSE;
    }
    return $this->prepareItem($cid, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids) {
    // Translate the requested cache item IDs to APCu keys.
    $map = array();
    foreach ($cids as $cid) {
      $map[$this->getKey($cid)] = $cid;
    }

    $result = wincache_ucache_get(array_keys($map));
    $cache = array();
    if ($result) {
      foreach ($result as $key => $item) {
        $item = $this->prepareItem($key, $item);
        $cache[$map[$key]] = $item;
      }
    }
    unset($result);

    $cids = array_diff($cids, array_keys($cache));
    return $cache;
  }

  /**
   * Returns all cached items, optionally limited by a cache ID prefix.
   *
   * APCu is a memory cache, shared across all server processes. To prevent
   * cache item clashes with other applications/installations, every cache item
   * is prefixed with a unique string for this site. Therefore, functions like
   * apc_clear_cache() cannot be used, and instead, a list of all cache items
   * belonging to this application need to be retrieved through this method
   * instead.
   *
   * @param string $prefix
   *   (optional) A cache ID prefix to limit the result to.
   *
   * @return string[]
   *   An APCIterator containing matched items.
   */
  protected function getAll($prefix = '') {
    $data = wincache_ucache_info();
    $k = array_column($data['ucache_entries'], 'key_name');
    $keys = preg_grep('/^' . $this->getKey($prefix) . '/', $k);
    return $keys;
  }

  /**
   * Prepares a cached item.
   *
   * Checks that the item is either permanent or did not expire.
   *
   * @param string $cid
   *   The cache id.
   * @param mixed $data
   *   An item retrieved from the cache.
   *
   * @return \stdClass
   *   The cache item as a Drupal cache object.
   */
  protected function prepareItem($cid, $data) {
    return (object) array('data' => $data, 'cid' => $cid);
  }

  /**
   * Wrapper for wincache_ucache_set to properly manage expirations.
   * 
   * @param string $cid 
   * @param mixed $data 
   * @param int $expire 
   */
  protected function wincacheStore($cid, $data, $expire) {
     if ($ttl = $this->getTtl($expire)) {
       wincache_ucache_set($this->getKey($cid), $data, $ttl);
     }
     else {
       wincache_ucache_set($this->getKey($cid), $data);
     }
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = CacheRawBackendInterface::CACHE_PERMANENT) {
    // APC Serializes/Unserializes automatically plus
    // we want to store native types unserialized when possible.
    $this->wincacheStore($cid, $data, $expire);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items = array()) {
    foreach ($items as $cid => $item) {
      $this->set($cid, $item['data'], isset($item['expire']) ? $item['expire'] : CacheRawBackendInterface::CACHE_PERMANENT);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    wincache_ucache_delete($this->getKey($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    wincache_ucache_delete(array_map(array($this, 'getKey'), $cids));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    wincache_ucache_delete($this->getAll());
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    // APC performs garbage collection automatically.
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    wincache_ucache_delete($this->getAll());
  }

  /**
   * {@inheritdoc}
   */
  public function counter($cid, $increment, $default = 0, $expire = CacheRawBackendInterface::PERMANENT) {
    $success = FALSE;
    wincache_ucache_inc($this->getKey($cid), $increment, $success);
    if (!$success) {
      $this->wincacheStore($cid, $default, $expire);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function counterMultiple(array $cids, $increment, $default = 0, $expire = CacheRawBackendInterface::PERMANENT) {
    foreach ($cids as $cid) {
      $this->counter($cid, $increment, $default, $expire);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function touch($cid, $expire = CacheRawBackendInterface::PERMANENT) {
    $success = FALSE;
    $data = wincache_ucache_get($this->getKey($cid), $success);
    if (!$success) {
      throw new \Exception("Cannot touch a non existent item.");
    }
    $this->wincacheStore($cid, $data, $expire);
  }

  /**
   * {@inheritdoc}
   */
  public function touchMultiple(array $cids, $expire = CacheRawBackendInterface::PERMANENT) {
    foreach ($cids as $cid) {
      $this->touch($cid, $expire);
    }
  }

}
