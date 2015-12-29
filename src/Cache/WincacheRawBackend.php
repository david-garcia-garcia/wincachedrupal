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

  use WincacheBackendTrait;

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
    $this->sitePrefix = $this->shortMd5($site_prefix);
    $this->binPrefix = $this->sitePrefix . ':' . $this->bin . ':';
    $this->refreshRequestTime();
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
  public function getAll($prefix = '') {
    $keys = $this->getAllKeys($prefix);
    $result = $this->getMultiple($keys);
    return $result;
  }

  /**
   * Return all keys of cached items.
   *
   * @param string $prefix
   * @return array
   */
  public function getAllKeys($prefix = '') {
    $key = $this->getKey($prefix);
    return $this->getAllKeysWithPrefix($key);
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
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = CacheRawBackendInterface::CACHE_PERMANENT) {
    $this->wincacheSet($this->getKey($cid), $data, $expire);
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
  public function garbageCollection() {
    // Wincache performs garbage collection automatically.
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    $this->deleteMultiple($this->getAllKeys());
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->deleteMultiple($this->getAllKeys());
  }

  /**
   * {@inheritdoc}
   */
  public function counter($cid, $increment, $default = 0) {
    $success = FALSE;
    $key = $this->getKey($cid);
    wincache_ucache_inc($key, $increment, $success);
    if (!$success) {
      if (wincache_ucache_exists($key)) {
        throw new \Exception("Failed to increment item.");
      }
      $this->wincacheSet($key, $default, CacheRawBackendInterface::CACHE_PERMANENT);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function counterMultiple(array $cids, $increment, $default = 0) {
    foreach ($cids as $cid) {
      $this->counter($cid, $increment, $default);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function counterSet($cid, $value) {
    $this->set($cid, (int) $value);
  }

  /**
   * {@inheritdoc}
   */
  public function counterSetMultiple(array $items) {
    foreach ($items as $cid => $item) {
      $this->counterSet($cid, (int) $item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function counterGet($cid) {
    if ($result = $this->get($cid)) {
      return (int) $result->data;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function counterGetMultiple(array &$cids) {
    $results = $this->getMultiple($cids);
    $counters = [];
    foreach ($results as $cid => $item) {
      $counters[$cid] = (int) $item->data;
    }
    return $counters;
  }

  /**
   * {@inheritdoc}
   */
  public function touch($cid, $expire = CacheRawBackendInterface::PERMANENT) {
    $success = FALSE;
    $key = $this->getKey($cid);
    $data = wincache_ucache_get($key, $success);
    if (!$success) {
      throw new \Exception("Failed to touch item.");
    }
    $this->wincacheSet($key, $data, $expire);
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
