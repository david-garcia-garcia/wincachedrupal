<?php

/**
 * @file
 * Contains \Drupal\wincachedrupal\KeyValueStorage\wincachedrupalStorageExpirable.
 */

namespace Drupal\wincachedrupal\KeyValueStorage;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Merge;

use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\KeyValueStore\DatabaseStorageExpirable;

use Drupal\wincachedrupal\Cache\wincachedrupalBackend;

/**
 * Defines a default key/value store implementation for expiring items.
 *
 * This key/value store implementation uses the database to store key/value
 * data with an expire date.
 */
class WincacheStorageExpirable extends DatabaseStorageExpirable implements KeyValueStoreExpirableInterface {

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
   *   The name of the SQL table to use, defaults to key_value_expire.
   */
  public function __construct($collection, SerializationInterface $serializer, Connection $connection, $table = 'key_value_expire') {
    parent::__construct($collection, $serializer, $connection, $table);
    $this->cache = new WincacheBackend($collection . '::' . $table, '', new \Drupal\wincachedrupal\Cache\DummyTagChecksum());
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
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::getMultiple().
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
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::getAll().
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
   * {@inheritdoc}
   */
  function setWithExpire($key, $value, $expire) {
    $this->cache->set($key, $value, REQUEST_TIME + $expire);
    parent::setWithExpire($key, $value, $expire);
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface::setWithExpireIfNotExists().
   */
  function setWithExpireIfNotExists($key, $value, $expire) {
    $result = parent::setWithExpireIfNotExists($key, $value, $expire);
    if ($result == Merge::STATUS_INSERT) {
      $this->cache->set($key, $value, REQUEST_TIME + $expire);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  function setMultipleWithExpire(array $data, $expire) {
    foreach ($data as $key => $value) {
      $this->setWithExpire($key, $value, $expire);
    }
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::deleteMultiple().
   */
  public function deleteMultiple(array $keys) {
    parent::deleteMultiple($keys);
  }


}
