<?php

/**
 * @file
 * Contains \Drupal\wincachedrupal\Asset\CssCollectionOptimizer.
 */

namespace Drupal\wincachedrupal\Asset;

use Drupal\Core\Asset\AssetDumperInterface;
use Drupal\Core\Asset\AssetOptimizerInterface;
use Drupal\Core\Asset\AssetCollectionGrouperInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\wincachedrupal\NetPhp;

/**
 * Optimizes CSS assets.
 */
class CssCollectionOptimizer extends \Drupal\Core\Asset\CssCollectionOptimizer {

  /**
   * Code settings instance for reuse.
   *
   * @var \Drupal\wincachedrupal\NetPhp
   */
  protected $netPhp;

  /**
   * Constructs a CssCollectionOptimizer.
   *
   * @param \Drupal\Core\Asset\AssetCollectionGrouperInterface $grouper
   *   The grouper for CSS assets.
   * @param \Drupal\Core\Asset\AssetOptimizerInterface $optimizer
   *   The optimizer for a single CSS asset.
   * @param \Drupal\Core\Asset\AssetDumperInterface $dumper
   *   The dumper for optimized CSS assets.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\wincachedrupal\NetPhp $netphp
   *   NetPhp instance.
   */
  public function __construct(AssetCollectionGrouperInterface $grouper, AssetOptimizerInterface $optimizer, AssetDumperInterface $dumper, StateInterface $state, FileSystemInterface $file_system, NetPhp $netphp) {
    parent::__construct($grouper, $optimizer, $dumper, $state, $file_system);
    $this->netPhp = $netphp;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    if (!($this->netPhp->hasNetPhpSupport() === TRUE)) {
      return parent::deleteAll();
    }

    $this->state->delete('drupal_css_cache_files');

    $runtime = $this->netPhp->getRuntime();
    $runtime->RegisterNetFramework4();
    $d = $runtime->TypeFromName("System.IO.DirectoryInfo")->Instantiate($this->fileSystem->realpath('public://css'));
    if (!$d->Exists()->Val()) {
      return;
    }
    $search_option = $runtime->TypeFromName("System.IO.SearchOption")->Enum("AllDirectories");
    $threshold = \Drupal::config('system.performance')->get('stale_file_threshold');
    /** @var \NetPhp\Core\NetProxy */
    $files = $d->GetFiles("*.*", $search_option);
    foreach ($files->AsIterator() as $file) {
      $uri = $file->FullName->Val();
      if (REQUEST_TIME - filemtime($uri) > $threshold) {
        $file->Delete();
      }
    }
  }

}
