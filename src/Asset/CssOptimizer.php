<?php

namespace Drupal\wincachedrupal\Asset;

use Drupal\wincachedrupal\NetPhp;
use Drupal\Core\Asset\CssOptimizer as CoreOptimizer;

/**
 * Optimizes a CSS asset.
 */
class CssOptimizer extends CoreOptimizer {

  /**
   * Code settings instance for reuse.
   *
   * @var \Drupal\wincachedrupal\NetPhp
   */
  protected $netPhp;

  /**
   * Code settings instance for reuse.
   *
   * @var \NetPhp\Core\NetProxy
   */
  protected $codeSettings;

  /**
   * CSS settings instance for reuse.
   *
   * @var \NetPhp\Core\NetProxy
   */
  protected $cssSettings;

  /**
   * Minifier instance, for reuse.
   *
   * @var \NetPhp\Core\NetProxy
   */
  protected $minifier;

  /**
   * Returns an instance of CssOptimizer.
   *
   * @param \Drupal\wincachedrupal\NetPhp $netphp
   *   NetPhp instance.
   */
  public function __construct(NetPhp $netphp) {
    if ($this->minifier = $netphp->getMinifier()) {
      $this->netPhp = $netphp;
      $runtime = $this->netPhp->getRuntime();
      $this->codeSettings = $runtime->TypeFromName("Microsoft.Ajax.Utilities.CodeSettings")->Instantiate();
      $this->codeSettings->OutputMode = $runtime->TypeFromName("Microsoft.Ajax.Utilities.OutputMode")->Instantiate()->Enum('SingleLine');
      $this->codeSettings->QuoteObjectLiteralProperties = TRUE;
      $this->cssSettings = $runtime->TypeFromName("Microsoft.Ajax.Utilities.CssSettings")->Instantiate();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function processCss($contents, $optimize = FALSE) {
    $contents = parent::processCss($contents, FALSE);
    if ($this->minifier) {
      $contents = $this->minifier->MinifyStyleSheet($contents, $this->cssSettings, $this->codeSettings)->Val();
    }
    return $contents;
  }

}
