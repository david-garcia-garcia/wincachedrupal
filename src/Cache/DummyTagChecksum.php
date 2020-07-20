<?php

/**
 * @file
 * Contains \Drupal\wincachedrupal\Cache\DummyTagChecksum.
 */

namespace Drupal\wincachedrupal\Cache;

use Drupal\Core\Cache\CacheTagsChecksumInterface;

/**
 * Stub checksum interface, for when you need to use a cache backend
 * without tag invalidation support.
 */
class DummyTagChecksum implements CacheTagsChecksumInterface {

  /**
   * {@inheritdoc}
   */
  public function getCurrentChecksum(array $tags) {
    if (!empty($tags)) {
      throw new \Exception("DummyTagChecksum cannot process tags.");
    }
    // Checksum needs to be 0 because the original
    // database storage for tags was designed not
    // to accept NULL.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isValid($checksum, array $tags) {
    if (!empty($tags) || !empty($checksum)) {
      throw new \Exception("DummyTagChecksum cannot process tags and items cannot have a checksum.");
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function reset() { return; }
}
