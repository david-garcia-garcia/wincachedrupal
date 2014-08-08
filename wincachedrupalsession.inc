<?php

/**
 * @file
 * User session handling functions.
 */

/**
 * Generate a session ID from user id.
 *
 * It is ver nice to have session ID's that actually
 * hint on to what the userID of the owner is, even
 * for anonymous users...
 */
function wincache_session_id($uid) {
  if (empty($uid)) {
    $uid = '0';
  }
  return 'wc_sess_' . $uid . '_' . drupal_random_key();
}

/**
 * Initializes the session handler, starting a session if needed.
 */
function drupal_session_initialize() {
  global $user, $is_https;

  // We use !empty() in the following check to ensure that blank session IDs
  // are not valid.
  if (!empty($_COOKIE[session_name()]) || ($is_https && variable_get('https', FALSE) && !empty($_COOKIE[substr(session_name(), 1)]))) {
    // If a session cookie exists, initialize the session. Otherwise the
    // session is only started on demand in drupal_session_commit(), making
    // anonymous users not use a session cookie unless something is stored in
    // $_SESSION. This allows HTTP proxies to cache anonymous pageviews.
    drupal_session_start();
    // If the session was properly created it will contain this key,
    // otherwise recreate as anonymous. For example the user may
    // have a cookie issued from a non drupal php request to that same
    // server.
    if (!isset($_SESSION['wincachesession'])) {
      drupal_session_regenerate();
    }
    if (!empty($user->uid) || !empty($_SESSION)) {
      drupal_page_is_cacheable(FALSE);
    }
  }
  else {
    // Less random sessions (which are much faster to generate) are used for
    // anonymous users than are generated in drupal_session_regenerate() when
    // a user becomes authenticated.
    session_id(wincache_session_id(NULL));
    // Start the session, if it is anonymous the $user
    // object will get populated.
    drupal_session_start();
  }
  date_default_timezone_set(drupal_get_user_timezone());
}

/**
 * Starts a session forcefully, preserving already set session data.
 *
 * @ingroup php_wrappers
 */
function drupal_session_start() {
  global $user;
  // Command line clients do not support cookies nor sessions.
  if (session_status() == PHP_SESSION_NONE && !drupal_is_cli()) {
    // Save current session data before starting it, as PHP will destroy it.
    $session_data = isset($_SESSION) ? $_SESSION : NULL;

    session_start();

    // Perform account accommodation.
    if (isset($_SESSION['wincachesession']['user'])) {
      try {
        $user = unserialize(gzuncompress($_SESSION['wincachesession']['user']));
      }
      catch(Exception $error) {
      }
    }

    if (empty($user)) {
      $user = drupal_anonymous_user();
    }

    // Restore session data.
    if (!empty($session_data)) {
      $_SESSION += $session_data;
    }
  }
}

/**
 * Commits the current session, if necessary.
 *
 * If an anonymous user already have an empty session, destroy it.
 */
function drupal_session_commit() {
  global $user, $is_https;
  // Max. 85Mb wincache session cache can roughly accommodate 21,000 
  // uncompressed and unserialized sessions. With compression and serialization
  // turned on we get up to 55,000 sessions in the cache. 
  // This is OK for medium to large sites.
  if (session_status() != PHP_SESSION_ACTIVE) {
    return;
  }

  $_SESSION['wincachesession']['user'] = gzcompress(serialize($user), 9);
  session_write_close();

  if (!drupal_save_session()) {
    // We don't have anything to do if we are not allowed to save the session.
    return;
  }

  if (empty($user->uid) && empty($_SESSION)) {
    // There is no session data to store, destroy the session if it was
    // previously started.
    if (session_status() == PHP_SESSION_ACTIVE) {
      session_destroy();
    }
  }
  else {
    // There is session data to store. Start the session if it is not already
    // started.
    if (session_status() == PHP_SESSION_NONE) {
      drupal_session_start();
      if ($is_https && variable_get('https', FALSE)) {
        $insecure_session_name = substr(session_name(), 1);
        $params = session_get_cookie_params();
        $expire = $params['lifetime'] ? REQUEST_TIME + $params['lifetime'] : 0;
        setcookie($insecure_session_name, $_COOKIE[$insecure_session_name], $expire, $params['path'], $params['domain'], FALSE, $params['httponly']);
      }
    }
    // Write the session data.
    session_write_close();
  }
}

/**
 * Called when an anonymous user becomes authenticated or vice-versa.
 *
 * @ingroup php_wrappers
 */
function drupal_session_regenerate() {
  global $user;
  // Nothing to do if we are not allowed to change the session.
  if (!drupal_save_session()) {
    return;
  }

  // Keep track of old session id.
  if (session_status() == PHP_SESSION_ACTIVE) {
    $old_session_id = session_id();
  }
  
  // Now make sure we destroy current session,
  // this does not clear $_SESSION data.
  session_destroy();
  // Remove wincache session information, the rest
  // will be preserved.
  unset($_SESSION['wincachesession']);

  // Assign a new session id.
  session_id(wincache_session_id($user->uid));

  if (isset($old_session_id)) {
    // If we already had a session, update the cookie to point to the new one!
    $params = session_get_cookie_params();
    $expire = $params['lifetime'] ? REQUEST_TIME + $params['lifetime'] : 0;
    setcookie(session_name(), session_id(), $expire, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    drupal_session_start();
  }
  else {
    // Start the session when it doesn't exist yet.
    // Preserve the logged in user, as it will be reset to anonymous
    // by drupal_session_start().
    $account = $user;
    drupal_session_start();
    $user = $account;
  }
  date_default_timezone_set(drupal_get_user_timezone());
}

/**
 * Ends a specific user's session(s).
 *
 * @param $uid
 *   User ID.
 */
function drupal_session_destroy_uid($uid) {
  // Nothing to do if we are not allowed to change the session.
  if (!drupal_save_session()) {
    return;
  }

  // Store current session.
  if (session_status() == PHP_SESSION_ACTIVE) {
    $current_session = session_id();
    session_write_close();
  }

  // In order to destroy we need to load.
  $session_id = wincache_session_id($uid);
  session_id($session_id);
  session_destroy();

  if (!empty($current_session)) {
    session_id($current_session);
    session_start();
  }

}

/**
 * Determines whether to save session data of the current request.
 *
 * This function allows the caller to temporarily disable writing of
 * session data, should the request end while performing potentially
 * dangerous operations, such as manipulating the global $user object.
 * See http://drupal.org/node/218104 for usage.
 *
 * @param $status
 *   Disables writing of session data when FALSE, (re-)enables
 *   writing when TRUE.
 *
 * @return
 *   FALSE if writing session data has been disabled. Otherwise, TRUE.
 */
function drupal_save_session($status = NULL) {
  // PHP session ID, session, and cookie handling happens in the global scope.
  // This value has to persist across calls to drupal_static_reset(), since a
  // potentially wrong or disallowed session would be written otherwise.
  static $save_session = TRUE;
  if (isset($status)) {
    $save_session = $status;
  }
  return $save_session;
}
