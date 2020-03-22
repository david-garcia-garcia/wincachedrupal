<?php

namespace Drupal\wincachedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendExpireTests as ExpireTests;

/**
 * Extends supercache BackendExpireTests.
 */
class RawBackendExpireTests extends ExpireTests {
  use RawBackendGeneralTestCaseTrait;

}
