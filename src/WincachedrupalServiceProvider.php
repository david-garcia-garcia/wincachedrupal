<?php

namespace Drupal\wincachedrupal;

use Drupal\Core\Asset\CssCollectionOptimizer;
use Drupal\Core\Asset\JsCollectionOptimizer;
use Drupal\Core\Asset\CssOptimizer;
use Drupal\Core\Asset\JsOptimizer;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Class that all service providers must implement.
 *
 * @ingroup container
 */
class WincachedrupalServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {

    // Region Tell Chained fast backend to use Wincache as default.
    if ($container->has('cache.backend.chainedfast')) {
      $definition = $container->getDefinition('cache.backend.chainedfast');
      $args = $definition->getArguments();
      // Add the missing optional arguments.
      if (!isset($args[1])) {
        $args[] = NULL;
      }
      if (!isset($args[2])) {
        $args[] = 'cache.backend.wincache';
      }
      $definition->setArguments($args);
    }
    // Endregion
    // Region Tell Chained fast backend to use Wincache as default.
    if ($container->has('cache.backend.superchainedfast')) {
      $definition = $container->getDefinition('cache.backend.superchainedfast');
      $args = $definition->getArguments();
      // Add the missing optional arguments.
      if (!isset($args[1])) {
        $args[] = NULL;
      }
      if (!isset($args[2])) {
        $args[] = 'cache.backend.wincache';
      }
      $definition->setArguments($args);
    }
    // Endregion
    // Region Tell Raw Chained fast backend to use Wincache as default.
    if ($container->has('cache.rawbackend.chainedfast')) {
      $definition = $container->getDefinition('cache.rawbackend.chainedfast');
      $args = $definition->getArguments();
      // Add the missing optional arguments.
      if (!isset($args[1])) {
        $args[] = NULL;
      }
      if (!isset($args[2])) {
        $args[] = 'cache.rawbackend.wincache';
      }
      $definition->setArguments($args);
    }
    // Endregion.
    $definition = $container->getDefinition('asset.js.optimizer');
    if ($definition->getClass() == JsOptimizer::class) {
      $couchbase_definition = $container->getDefinition('wincache.js.optimizer');
      $definition->setClass($couchbase_definition->getClass());
      $definition->setArguments($couchbase_definition->getArguments());
    }

    $definition = $container->getDefinition('asset.css.optimizer');
    if ($definition->getClass() == CssOptimizer::class) {
      $couchbase_definition = $container->getDefinition('wincache.css.optimizer');
      $definition->setClass($couchbase_definition->getClass());
      $definition->setArguments($couchbase_definition->getArguments());
    }

    $definition = $container->getDefinition('asset.js.collection_optimizer');
    if ($definition->getClass() == JsCollectionOptimizer::class) {
      $couchbase_definition = $container->getDefinition('wincache.asset.js.collection_optimizer');
      $definition->setClass($couchbase_definition->getClass());
      $definition->setArguments($couchbase_definition->getArguments());
    }

    $definition = $container->getDefinition('asset.css.collection_optimizer');
    if ($definition->getClass() == CssCollectionOptimizer::class) {
      $couchbase_definition = $container->getDefinition('wincache.asset.css.collection_optimizer');
      $definition->setClass($couchbase_definition->getClass());
      $definition->setArguments($couchbase_definition->getArguments());
    }

  }

}
