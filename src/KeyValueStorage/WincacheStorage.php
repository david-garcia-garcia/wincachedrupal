<?php

/**
 * @file
 * Contains \Drupal\wincachedrupal\KeyValueStorage\wincachedrupalStorage;
 */

namespace Drupal\wincachedrupal\KeyValueStorage;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

use Drupal\Core\KeyValueStore\DatabaseStorage as KeyValueDatabaseStorage;
use Drupal\Core\KeyValueStore\StorageBase;

use Drupal\wincachedrupal\Cache\wincachedrupalBackend;
use Drupal\wincachedrupal\Cache\DummyTagChecksum;

/**
 *  Defines a key/value store implementation that uses
 *  wincache as the local storage and database as a pluggable
 *  persistent backend.
 */
class WincacheStorage extends KeyValueDatabaseStorage {

  const EMPTY_VALUE = '@@empty';

  /**
   * Overrides Drupal\Core\KeyValueStore\StorageBase::__construct().
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param string $table
   *   The name of the SQL table to use, defaults to key_value.
   */
  public function __construct($collection, SerializationInterface $serializer, Connection $connection, $table = 'key_value') {
    parent::__construct($collection, $serializer, $connection, $table);
    $this->cache = new WincacheBackend($collection . '::' . $table, '', new DummyTagChecksum());
  }

  /**
   * {@inheritdoc}
   */
  public function has($key) {
    // See if available in the local cache.
    if ($cache = $this->cache->get($key)) {
      return $cache->data;
    }
    // Check in the parent.
    return parent::has($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    if ($cache = $this->cache->getMultiple($keys)) {
      return $this->CacheToKeyValue($cache);
    }
    $result = parent::getMultiple($keys);
    $this->cache->setMultiple($this->KeyValueToCache($result));
    $this->populateMissingValuesLocal($keys, $result);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    if ($cache = $this->cache->getAll()) {
      return $this->CacheToKeyValue($cache);
    }
    $result = parent::getAll();
    $this->cache->setMultiple($this->KeyValueToCache($result));
    return $result;
  }

  /**
   * To prevent database lookups we store a special 'empty'
   * object.
   *
   * @param array $requested
   *   The requested set of keys from getMultiple().
   *
   * @param array $obtained
   *   The effectively obtained key-value pairs.
   *
   */
  protected function populateMissingValuesLocal($requested, $obtained) {
    $missing = array_diff_key(array_flip($requested), $obtained);
    foreach ($missing as $key => $value) {
      $missing[$key] = WincacheStorage::EMPTY_VALUE;
    }
    $this->cache->setMultiple($this->KeyValueToCache($missing));
  }

  /**
   * Converts an array of KeyValues to a cache compatible array.
   *
   * @param array $items
   * @return array
   */
  protected function KeyValueToCache(array $items) {
    $result = array();
    foreach ($items as $key => $value) {
      $result[$key] = array(
          'data' => $value,
          'expires' => \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT,
          'tags' => array(),
        );
    }
    return $result;
  }

  /**
   * Converts a cache arry to a KeyValue array.
   *
   * @param array $items
   * @return array
   */
  protected function CacheToKeyValue(array $items) {
    $result = array();
    foreach ($items as $key => $value) {
      if (is_object($value) && $value->data !== WincacheStorage::EMPTY_VALUE) {
        $result[$key] = $value->data;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->cache->set($key, $value);
    parent::set($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setIfNotExists($key, $value) {
    $result = parent::setIfNotExists($key, $value);
    if ($result == Merge::STATUS_INSERT) {
      $this->cache->set($key, $value);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($key, $new_key) {
    parent::rename($key, $new_key);
    // Update on volatile storage, will be updated on next get()
    $this->cache->delete($key);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    parent::deleteMultiple($keys);
    $this->cache->deleteMultiple($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    parent::deleteAll();
    $this->cache->deleteAll();
  }
}
