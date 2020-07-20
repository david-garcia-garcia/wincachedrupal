<?php

namespace Drupal\wincachedrupal\Tests\Cache;

use Drupal\supercache\Tests\Generic\Cache\BackendExpireTests as ExpireTests;

/**
 * Extends supercache BackendExpireTests.
 *
 * @group wincachedrupal
 */
class BackendExpireTests extends ExpireTests {
  use BackendGeneralTestCaseTrait;

}
