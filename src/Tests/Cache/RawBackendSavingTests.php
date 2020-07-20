<?php

namespace Drupal\wincachedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendSavingTests as ScBackendSavingTests;

/**
 * Extends supercache BackendSavingTests.
 *
 * @group wincachedrupal_supercache
 */
class RawBackendSavingTests extends ScBackendSavingTests {
  use RawBackendGeneralTestCaseTrait;

}
