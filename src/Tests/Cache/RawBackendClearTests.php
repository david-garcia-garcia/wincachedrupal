<?php

namespace Drupal\wincachedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendClearTests as ClearTests;

/**
 * Extends supercache BackendClearTests.
 *
 * @group wincachedrupal_supercache
 */
class RawBackendClearTests extends ClearTests {
  use RawBackendGeneralTestCaseTrait;

}
