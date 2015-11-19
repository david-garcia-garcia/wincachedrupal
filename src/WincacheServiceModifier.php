<?php

namespace Drupal\wincachedrupal;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

class WincacheServiceModifier implements ServiceModifierInterface {
  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
  }
}
