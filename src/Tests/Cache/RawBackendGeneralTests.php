<?php

namespace Drupal\wincachedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\RawBackendGeneralTests as GeneralTests;

/**
 * Extends supercahe RawBackendGeneralTests.
 *
 * @group wincachedrupal_supercache
 */
class RawBackendGeneralTests extends GeneralTests {
  use RawBackendGeneralTestCaseTrait;

}
