<?php

namespace Drupal\wincachedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendGetMultipleTests as GetMultipleTests;

/**
 * Extends supercache BackendGetMultipleTests.
 */
class RawBackendGetMultipleTests extends GetMultipleTests {
  use RawBackendGeneralTestCaseTrait;

}
