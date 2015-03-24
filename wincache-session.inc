<?php

/**
 * @file
 * A wincache based implementation of the session backend.
 */
require_once dirname(__FILE__) . '/drupal_win_cache.inc';

// Check if wincache is available - if not include default session handler.
$session_file = dirname(__FILE__) . '/wincache-session-code.inc';

// Test WinCache exists.
$wincache_enabled = (function_exists('wincache_ucache_info') && ($cache = @wincache_ucache_info()));

// Check we are running at least 5.4
$phpver_ok = version_compare(PHP_VERSION, '5.4.0') >= 0;

// If something is wrong, back to the default session handler.
if (!$wincache_enabled || !$phpver_ok) {
  $session_file = DRUPAL_ROOT . '/includes/session.inc';
  ini_set('session.save_handler', 'files');
}

require_once $session_file;
