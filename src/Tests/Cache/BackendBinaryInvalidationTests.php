<?php

namespace Drupal\wincachedrupal\Tests\Cache;

use Drupal\KernelTests\KernelTestBase;
use Drupal\wincachedrupal\Cache\DummyTagChecksum;
use Drupal\wincachedrupal\Cache\WincacheBackendFactory;

/**
 * Testea funciones basicas.
 *
 * @group wincachedrupal
 */
class BackendBinaryInvalidationTests extends KernelTestBase
{

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'wincachedrupal'
  ];

  /**
   * Make sure that invalidations and garbage collection works fine.
   */
  public function testInvalidations()
  {

    $data1 = [
      'a' => ['data' => 'b'],
      'b' => ['data' => 'b'],
      'v' => ['data' => 'b'],
      'd' => ['data' => 'b'],
      'e' => ['data' => 'b'],
    ];

    $data2 = [
      'a' => ['data' => 'b2'],
      'b' => ['data' => 'b2'],
      'v' => ['data' => 'b2'],
      'd' => ['data' => 'b2'],
      'e' => ['data' => 'b2'],
    ];

    $factory = new WincacheBackendFactory('', '', new DummyTagChecksum());

    $backend = $factory->get('bootstrap');

    $zero_prefix = $backend->getBinKey('');

    $backend->deleteAll();
    $backend->setMultiple($data1);

    // Wincache won't return detailed of stored keys: reported here: https://forums.iis.net/p/1249312/2161546.aspx?p=True&t=637307565571675358
    $this->assertEquals(count($data1), count($backend->getAll()));
    $firstPrefix = $backend->getBinKey('');
    $this->assertEquals(count($data1), count($backend->getAllKeysWithPrefix($firstPrefix)), 'Correct number of items returned.');

    $backend->deleteAll();
    $this->assertEquals([], $backend->getAll(), 'Correct number of items returned.');
    $backend->setMultiple($data2);
    $this->assertEquals(count($data1), count($backend->getAllKeysWithPrefix($firstPrefix)), 'Correct number of items returned.');

    $backend->garbageCollection();
    $this->assertEquals(0, count($backend->getAllKeysWithPrefix($firstPrefix)), 'Correct number of items returned.');

    // Invalidate all after a garbage collection
    // that had a higher counter should reset
    // the prefix to 0.
    $backend->deleteAll();
    $this->assertEquals($zero_prefix, $backend->getBinKey(''));
  }

}
