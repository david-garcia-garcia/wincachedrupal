<?php

/**
 * @file
 * Contains \Drupal\Core\KeyValueStore\KeyValueWincacheFactory.
 */

namespace Drupal\wincache\KeyValueStorage;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

/**
 * Defines the key/value store factory for the database backend.
 */
class KeyValueWincacheFactory implements KeyValueFactoryInterface {

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   */
  function __construct(SerializationInterface $serializer, Connection $connection) {
    $this->serializer = $serializer;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    return new WincacheStorage($collection, $this->serializer, $this->connection);
  }
}
