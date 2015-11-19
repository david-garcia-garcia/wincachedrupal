<?php

namespace Drupal\wincachedrupal;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

class WincacheServiceProvider implements ServiceProviderInterface {
  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    #region Tell Chained fast backend to use Wincache as default
    $definition  = $container->getDefinition('cache.backend.chainedfast');
    $args = $definition->getArguments();
    // Add the missins optional arguments
    if (!isset($args[1])) {
      $args[] = NULL;
    }
    if (!isset($args[2])) {
      $args[] = 'cache.backend.wincache';
    }
    $definition->setArguments($args);
    #endregion
  }
}
