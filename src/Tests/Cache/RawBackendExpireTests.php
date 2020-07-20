<?php

namespace Drupal\wincachedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendExpireTests as ExpireTests;

/**
 * Extends supercache BackendExpireTests.
 *
 * @group wincachedrupal_supercache
 */
class RawBackendExpireTests extends ExpireTests {
  use RawBackendGeneralTestCaseTrait;

}
