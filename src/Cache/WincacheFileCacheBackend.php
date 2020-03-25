<?php

namespace Drupal\wincachedrupal\Cache;

use Drupal\Component\FileCache\FileCacheBackendInterface;

if (function_exists('module_load_include')) {
  module_load_include('inc', 'wincachedrupal', 'wincache');
}
else {
  require_once __DIR__ . '/../../wincache.inc';
}

/**
 * Wincache backend for the file cache.
 */
class WincacheFileCacheBackend implements FileCacheBackendInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(array $cids) {
    return wincachedrupal_ucache_get($cids);
  }

  /**
   * {@inheritdoc}
   */
  public function store($cid, $data) {
    wincachedrupal_ucache_set($cid, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    wincachedrupal_ucache_delete($cid);
  }

}
