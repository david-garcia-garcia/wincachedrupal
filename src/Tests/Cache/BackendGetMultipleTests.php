<?php

namespace Drupal\wincachedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendGetMultipleTests as GetMultipleTests;

/**
 * Extends supercache BackendGetMultipleTests.
 *
 * @group wincachedrupal
 */
class BackendGetMultipleTests extends GetMultipleTests {
  use BackendGeneralTestCaseTrait;

}
