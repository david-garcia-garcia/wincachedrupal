<?php

namespace Drupal\wincachedrupal\Cache;

use Drupal\Component\FileCache\FileCacheBackendInterface;

/**
 * Wincache backend for the file cache.
 */
class WincacheFileCacheBackend implements FileCacheBackendInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(array $cids) {
    return \Drupal\wincachedrupal\WincacheWrapper::wincachedrupal_ucache_get($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function store($cid, $data) {
      \Drupal\wincachedrupal\WincacheWrapper::wincachedrupal_ucache_set($cid, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
      \Drupal\wincachedrupal\WincacheWrapper::wincachedrupal_ucache_delete($cid);
  }

}
