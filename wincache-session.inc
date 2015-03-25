<?php

/**
 * @file
 * A wincache based implementation of the session backend.
 */
require_once dirname(__FILE__) . '/drupal_win_cache.inc';

// Check if wincache is available - if not include default session handler.
$session_file = dirname(__FILE__) . '/wincache-session-code.inc';

// Test WinCache session is enabled. There is no real way of doing this, so
// we do a regular check to see if wincache is enabled.
$wincache_session_enabled = (function_exists('wincache_ucache_info') && ($cache = @wincache_scache_info(TRUE)));

// Check we are running at least 5.4
$phpver_ok = version_compare(PHP_VERSION, '5.4.0') >= 0;

// If something is wrong, back to the default session handler.
if (!$wincache_session_enabled || !$phpver_ok || drupal_is_cli()) {
  $session_file = DRUPAL_ROOT . '/includes/session.inc';
  ini_set('session.save_handler', 'files');
}

require_once $session_file;
