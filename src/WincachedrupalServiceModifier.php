<?php

namespace Drupal\wincachedrupal;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Interface that service providers can implement to modify services.
 *
 * @ingroup container
 */
class WincachedrupalServiceModifier implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
  }

}
