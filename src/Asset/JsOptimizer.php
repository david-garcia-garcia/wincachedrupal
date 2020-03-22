<?php

namespace Drupal\wincachedrupal\Asset;

use Drupal\wincachedrupal\NetPhp;
use Drupal\Core\Asset\JsOptimizer as CoreOptimizer;

/**
 * Optimizes a JavaScript asset.
 */
class JsOptimizer extends CoreOptimizer {

  /**
   * Code settings instance for reuse.
   *
   * @var \NetPhp\Core\NetProxy
   */
  protected $codeSettings;

  /**
   * Minifier instance, for reuse.
   *
   * @var \NetPhp\Core\NetProxy
   */
  protected $minifier;

  /**
   * Returns an instance of JsOptimizer.
   *
   * @param \Drupal\wincachedrupal\NetPhp $netphp
   *   NetPhp instance.
   */
  public function __construct(NetPhp $netphp) {
    if ($this->minifier = $netphp->getMinifier()) {
      $runtime = $netphp->getRuntime();
      $this->codeSettings = $runtime->TypeFromName("Microsoft.Ajax.Utilities.CodeSettings")->Instantiate();
      $this->codeSettings->OutputMode = $runtime->TypeFromName("Microsoft.Ajax.Utilities.OutputMode")->Instantiate()->Enum('SingleLine');
      $this->codeSettings->QuoteObjectLiteralProperties = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function optimize(array $js_asset) {
    // The core implementation actually does no optimization at all...
    $data = parent::optimize($js_asset);
    if ($this->minifier) {
      $data = $this->minifier->MinifyJavaScript($data, $this->codeSettings)->Val();
    }
    return $data;
  }

}
