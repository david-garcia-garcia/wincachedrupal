<?php

namespace Drupal\wincachedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendSavingTests as SavingTests;

/**
 * Extends supercache BackendSavingTests.
 *
 * @group wincachedrupal
 */
class BackendSavingTests extends SavingTests {
  use BackendGeneralTestCaseTrait;

}
