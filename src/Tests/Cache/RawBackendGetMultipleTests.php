<?php

namespace Drupal\wincachedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendGetMultipleTests as GetMultipleTests;

/**
 * Extends supercache BackendGetMultipleTests.
 *
 * @group wincachedrupal_supercache
 */
class RawBackendGetMultipleTests extends GetMultipleTests {
  use RawBackendGeneralTestCaseTrait;

}
