<?php

/**
 * @file
 * Contains \Drupal\wincachedrupal\Cache\Cache\wincachedrupalBackend.
 */

namespace Drupal\wincachedrupal\Cache;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\Cache;

/**
 * Stores cache items in the Alternative PHP Cache User Cache (APCu).
 */
class WincacheBackend implements CacheBackendInterface {

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
   * The cache tags checksum provider.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $checksumProvider;

  /**
   * Constructs a new WincacheBackend instance.
   *
   * @param string $bin
   *   The name of the cache bin.
   * @param string $site_prefix
   *   The prefix to use for all keys in the storage that belong to this site.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum provider.
   */
  public function __construct($bin, $site_prefix, CacheTagsChecksumInterface $checksum_provider) {
    $this->bin = $bin;
    $this->sitePrefix = md5($site_prefix);
    $this->checksumProvider = $checksum_provider;
    $this->binPrefix = $this->sitePrefix . '::' . $this->bin . '::';
  }

  /**
   * Prepends the Wincache user variable prefix for this bin to a cache item ID.
   *
   * @param string $cid
   *   The cache item ID to prefix.
   *
   * @return string
   *   The APCu key for the cache item ID.
   */
  protected function getBinKey($cid) {
    return $this->binPrefix . $cid;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $success = FALSE;
    $cache = wincache_ucache_get($this->getBinKey($cid), $success);
    if ($success == FALSE) {
      return FALSE;
    }
    return $this->prepareItem($cache, $allow_invalid);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    // Translate the requested cache item IDs to Wincache keys.
    $map = array();
    foreach ($cids as $cid) {
      $map[$this->getBinKey($cid)] = $cid;
    }

    $success = FALSE;
    $result = wincache_ucache_get(array_keys($map), $success);
    $cache = array();
    if ($success === TRUE) {
      foreach ($result as $key => $item) {
        $item = $this->prepareItem($item, $allow_invalid);
        if ($item) {
          $cache[$map[$key]] = $item;
        }
      }
    }
    unset($result);

    $cids = array_diff($cids, array_keys($cache));
    return $cache;
  }

  /**
   * Returns all cached items, optionally limited by a cache ID prefix.
   *
   * Wincache is a memory cache, shared across all server processes. To prevent
   * cache item clashes with other applications/installations, every cache item
   * is prefixed with a unique string for this site. Therefore, functions like
   * wincache_ucache_clear() cannot be used, and instead, a list of all cache items
   * belonging to this application need to be retrieved through this method
   * instead.
   *
   * @param string $prefix
   *   (optional) A cache ID prefix to limit the result to.
   *
   * @return string[]
   *   An array of Keys for this binary.
   */
  public function getAll($prefix = '') {
    $data = wincache_ucache_info();
    $k = array_column($data['ucache_entries'], 'key_name');
    $keys = preg_grep('/^' . $this->getBinKey($prefix) . '/', $k);
    return $keys;
  }

  /**
   * Returns all cached items for this binary.
   * 
   * @return string[]
   */
  protected function getAllBinary() {
    $data = wincache_ucache_info();
    $k = array_column($data['ucache_entries'], 'key_name');
    $keys = preg_grep('/^' . $this->binPrefix . '/', $k);
    return $keys;
  }

  /**
   * Prepares a cached item.
   *
   * Checks that the item is either permanent or did not expire.
   *
   * @param \stdClass $cache
   *   An item loaded from cache_get() or cache_get_multiple().
   * @param bool $allow_invalid
   *   If TRUE, a cache item may be returned even if it is expired or has been
   *   invalidated. See ::get().
   *
   * @return mixed
   *   The cache item or FALSE if the item expired.
   */
  protected function prepareItem($cache, $allow_invalid) {
    if (!isset($cache->data)) {
      return FALSE;
    }

    $cache->tags = $cache->tags ? explode(' ', $cache->tags) : array();

    // Check expire time.
    $cache->valid = $cache->expire == Cache::PERMANENT || $cache->expire >= REQUEST_TIME;

    // Check if invalidateTags() has been called with any of the entry's tags.
    if (!$this->checksumProvider->isValid($cache->checksum, $cache->tags)) {
      $cache->valid = FALSE;
    }

    if (!$allow_invalid && !$cache->valid) {
      return FALSE;
    }

    return $cache;
  }

  protected function prepareCacheItem($cid, $data, $expire, array $tags = array()) {
    Cache::validateTags($tags);
    $tags = array_unique($tags);
    $cache = new \stdClass();
    $cache->cid = $cid;
    $cache->created = round(microtime(TRUE), 3);
    $cache->expire = $expire;
    $cache->tags = implode(' ', $tags);
    $cache->checksum = $this->checksumProvider->getCurrentChecksum($tags);
    // There is no need to serialize unserialize with wincache.
    $cache->serialized = 0;
    $cache->data = $data;
    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = CacheBackendInterface::CACHE_PERMANENT, array $tags = array()) {
    
    $cache = $this->prepareCacheItem($cid, $data, $expire, $tags);

    // wincache_ucache_set()'s $ttl argument must be ommited for permament items,
    // in which case the value will persist until it's removed from the cache or
    // until the next cache clear, restart, etc. This is what we want to do
    // when $expire equals CacheBackendInterface::CACHE_PERMANENT.
    if ($expire === CacheBackendInterface::CACHE_PERMANENT) {
      wincache_ucache_set($this->getBinKey($cid), $cache);
    }
    else {
      wincache_ucache_set($this->getBinKey($cid), $cache, $expire);
    }
  }


  /**
   * Like set() but will fail if the item already exists in the cache.
   * 
   * @param mixed $cid 
   * @param mixed $data 
   * @param mixed $expire 
   * @param array $tags 
   */
  public function add($cid, $data, $expire = CacheBackendInterface::CACHE_PERMANENT, array $tags = array()) {
    
    $cache = $this->prepareCacheItem($cid, $data, $expire, $tags);

    // wincache_ucache_set()'s $ttl argument must be ommited for permament items,
    // in which case the value will persist until it's removed from the cache or
    // until the next cache clear, restart, etc. This is what we want to do
    // when $expire equals CacheBackendInterface::CACHE_PERMANENT.
    set_error_handler(function() { /* Prevent Drupal from logging any exceptions or warning thrown here */ }, E_ALL);
    $set_result = TRUE;
    if ($expire === CacheBackendInterface::CACHE_PERMANENT) {
      $set_result = @wincache_ucache_add($this->getBinKey($cid), $cache);
    }
    else {
      $set_result = @wincache_ucache_add($this->getBinKey($cid), $cache, $expire);
    }
    restore_error_handler();
    return $set_result;
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items = array()) {
    foreach ($items as $cid => $item) {
      $this->set($cid, $item['data'], isset($item['expire']) ? $item['expire'] : CacheBackendInterface::CACHE_PERMANENT, isset($item['tags']) ? $item['tags'] : array());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    wincache_ucache_delete($this->getBinKey($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    wincache_ucache_delete(array_map(array($this, 'getBinKey'), $cids));
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
    // Wincache performs garbage collection automatically.
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    wincache_ucache_delete($this->getAllBinary());
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    $this->invalidateMultiple(array($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    foreach ($this->getMultiple($cids) as $cache) {
      $this->set($cache->cid, $cache, REQUEST_TIME - 1);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    foreach ($this->getAll() as $data) {
      $cid = str_replace($this->binPrefix, '', $data['key']);
      $this->set($cid, $data['value'], REQUEST_TIME - 1);
    }
  }

}
