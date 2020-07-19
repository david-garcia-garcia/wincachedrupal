<?php

namespace Drupal\wincachedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendClearTests as ClearTests;

/**
 * Extends supercache BackendClearTests.
 *
 * @group wincachedrupal
 */
class BackendClearTests extends ClearTests {
  use BackendGeneralTestCaseTrait;

}
