<?php

/**
 * @file
 * Contains \Drupal\Wincache\Cache\WincacheFileCacheBackend.
 */

namespace Drupal\wincache\Cache;

use Drupal\Component\FileCache\FileCache;
use Drupal\Component\FileCache\FileCacheBackendInterface;

/**
 * APCu backend for the file cache.
 */
class WincacheFileCacheBackend implements FileCacheBackendInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(array $cids) {
    return wincache_ucache_get($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function store($cid, $data) {
    wincache_ucache_set($cid, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    wincache_ucache_delete($cid);
  }

}
