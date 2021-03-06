<?php

namespace Drupal\wincachedrupal;

use NetPhp\Core\NetPhpRuntime;

/**
 * Class to retain a single .Net runtime.
 */
class NetPhp {

  /**
   * The NetPhp Manager.
   *
   * @var \NetPhp\Core\NetPhpRuntime
   */
  protected $runtime;

  /**
   * The NetPhp Manager.
   *
   * @var \NetPhp\Core\NetProxy
   */
  protected $minifier = FALSE;

  /**
   * Is AjaxMin Supported.
   *
   * @var string|bool
   */
  protected $ajaxminSupport = FALSE;

  /**
   * Load status.
   *
   * @var array
   */
  protected $status = [
    'com_enabled' => FALSE,
    'netphp' => FALSE,
  ];

  /**
   * Returns if COM enabled.
   *
   * @return bool
   *   Is COM enabled.
   */
  public function hasComSupport() {
    return $this->status['com_enabled'];
  }

  /**
   * Determines if NetPhp is suppoerted.
   *
   * @return true|string
   *   The error message obtained while trying to access the NetPhp instance.
   */
  public function hasNetPhpSupport() {
    return $this->status['netphp'];
  }

  /**
   * Gets a NetPhp instance.
   */
  public function __construct() {

    // Check if extension loaded.
    if (!extension_loaded('com_dotnet')) {
      return;
    }

    // Check if COM and DOTNET exists.
    if (!class_exists('COM') && !class_exists('DOTNET')) {
      return;
    }

    // Up to here we have COM support.
    $this->status['com_enabled'] = TRUE;

    try {
      if (!class_exists(NetPhpRuntime::class)) {
        $this->status['netphp'] = 'NetPhpRuntime class not found. Make sure you run composer drupal-update.';
        return;
      }
      $this->runtime = new NetPhpRuntime('COM', '{2BF990C8-2680-474D-BDB4-65EEAEE0015F}');
      $this->runtime->Initialize();
      $this->status['netphp'] = TRUE;
    }
    catch (\Exception $e) {
      $this->status['netphp'] = $e->getMessage();
      return;
    }
  }

  /**
   * Summary of getRuntime.
   *
   * @return \NetPhp\Core\NetPhpRuntime
   *   NetPhp runtime.
   */
  public function getRuntime() {
    return $this->runtime;
  }

  /**
   * Determines if AjaxMin is supported.
   *
   * @return true|string
   *   The error string if AjaxMin is not supported.
   */
  public function hasAjaxMinSupport() {
    $this->getMinifier();
    return $this->ajaxminSupport;
  }

  /**
   * Get the path where the AjaxMin library should be deployed.
   *
   * @return string
   *   The real path to teh AjaxMin.dll.
   */
  public function ajaxMinPath() {
    return 'libraries/_bin/ajaxmin/AjaxMin.dll';
  }

  /**
   * Makes minifier part of the main service.
   *
   * Asset optimizations depend on the minifier.
   */
  public function getMinifier() {

    if ($this->minifier !== FALSE) {
      return $this->minifier;
    }

    $this->minifier = NULL;

    if (!($this->hasComSupport() === TRUE && $this->hasNetPhpSupport() === TRUE)) {
      $this->ajaxminSupport = 'NetPhp runtime missing.';
      return NULL;
    }

    $path = \Drupal::service('file_system')->realpath($this->ajaxMinPath());

    // The file must be in libraries/_bin/ajaxmin.
    if ($path == FALSE) {
      $this->ajaxminSupport = "File not found: {$this->ajaxMinPath()}";
      return NULL;
    }

    try {
      $this->runtime->RegisterAssemblyFromFile($path, 'AjaxMin');
      $this->minifier = $this->runtime->TypeFromName("Microsoft.Ajax.Utilities.Minifier")->Instantiate();
    }
    catch (\Exception $e) {
      $this->ajaxminSupport = $e->getMessage();
      return NULL;
    }

    $this->ajaxminSupport = TRUE;
    return $this->minifier;
  }

}
