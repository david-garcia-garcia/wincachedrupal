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
function wincache_session_id($uid, $onlyprefix = FALSE) {
  if (empty($uid)) {
    $uid = '0';
  }
  $prefix = 'wc_sess_' . $uid . '_';
  if ($onlyprefix) {
    return $prefix;
  }
  return $prefix . drupal_random_key();
}

/**
 * Initializes the session handler, starting a session if needed.
 */
function drupal_session_initialize() {
  global $user;

  // We use !empty() in the following check to ensure that blank session IDs
  // are not valid.
  if (!empty($_COOKIE[session_name()])) {
    // If a session cookie exists, initialize the session. Otherwise the
    // session is only started on demand in drupal_session_commit(), making
    // anonymous users not use a session cookie unless something is stored in
    // $_SESSION. This allows HTTP proxies to cache anonymous pageviews.
    drupal_session_start(TRUE);
    if (!empty($user->uid) || !wincachedrupal_session_empty($_SESSION)) {
      drupal_page_is_cacheable(FALSE);
    }
  }
  else {
    // Set a session identifier for this request. This is necessary because
    // we lazily start sessions at the end of this request, and some
    // processes (like drupal_get_token()) needs to know the future
    // session ID in advance.
    $user = drupal_anonymous_user();
    // Less random sessions (which are much faster to generate) are used for
    // anonymous users than are generated in drupal_session_regenerate() when
    // a user becomes authenticated.
    session_id(wincache_session_id(NULL));
  }
  date_default_timezone_set(drupal_get_user_timezone());
}

/**
 * Returns whether a session has been started.
 */
function drupal_session_started() {
  return session_status() == PHP_SESSION_ACTIVE;
}

/**
 * Starts a session forcefully, preserving already set session data.
 *
 * @ingroup php_wrappers
 */
function drupal_session_start($initializing = FALSE) {
  global $user;
  // Command line clients do not support cookies nor sessions.
  if (session_status() == PHP_SESSION_NONE && !drupal_is_cli()) {
    // Save current session data before starting it, as PHP will destroy it.
    $session_data = isset($_SESSION) ? $_SESSION : NULL;

    session_start();

    if($initializing && !isset($_SESSION['wincachesession'])) {
      // If the session was properly created it will contain this key,
      // otherwise recreate as anonymous. For example the user may
      // have a cookie issued from a non drupal php request to that same
      // server, that would not be a valid Drupal Session.
      $user = drupal_anonymous_user();
      drupal_session_regenerate();
      return;
    }

    // Perform account accommodation.
    if (isset($_SESSION['wincachesession']['user'])) {
      try {
        $user = unserialize(gzuncompress($_SESSION['wincachesession']['user']));
      }
      catch(Exception $error) {
      }
    }

    // We found the client's session record and they are an authenticated,
    // active user.
    if ($user && $user->uid > 0 && $user->status == 1) {
      // This is done to unserialize the data member of $user.
      //$user->data = unserialize($user->data);

      // Add roles element to $user.
      // This always needs to be fresh for
      // security reasons.
      $user->roles = array();
      $user->roles[DRUPAL_AUTHENTICATED_RID] = 'authenticated user';
      $user->roles += db_query("SELECT r.rid, r.name FROM {role} r INNER JOIN {users_roles} ur ON ur.rid = r.rid WHERE ur.uid = :uid", array(':uid' => $user->uid))->fetchAllKeyed(0, 1);
    }
    elseif ($user) {
      // The user is anonymous or blocked. Only preserve two fields from the
      // {sessions} table.
      $user = drupal_anonymous_user();
    }
    else {
      // The session has expired.
      $user = drupal_anonymous_user();
    }

    // Restore session data.
    if (!empty($session_data)) {
      $_SESSION += $session_data;
    }
  }
}

/**
 * Check if the session can be considered to have no
 * data.
 */
function wincachedrupal_session_empty($session) {
  unset($session['wincachesession']);
  return empty($session);
}

/**
 * Commits the current session, if necessary.
 *
 * If an anonymous user already have an empty session, destroy it.
 */
function drupal_session_commit() {
  global $user;

  if (!drupal_save_session()) {
    // We don't have anything to do if we are not allowed to save the session.
    return;
  }

  if (empty($user->uid) && (!isset($_SESSION) || wincachedrupal_session_empty($_SESSION))) {
    // There is no session data to store, destroy the session if it was
    // previously started.
    if (drupal_session_started()) {
      drupal_session_destroy();
    }
  }
  else {
    // Save user info into session.
    $_SESSION['wincachesession']['user'] = gzcompress(serialize($user), 9);
    // There is session data to store. Start the session if it is not already
    // started.
    if (!drupal_session_started()) {
      drupal_session_start();
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
  if (drupal_session_started()) {
    $old_session_id = session_id();
  }

  // Now make sure we destroy current session,
  // this does not clear $_SESSION data.
  if (drupal_session_started()) {
    session_destroy();
  }

  // Remove wincache session information, the rest
  // will be preserved.
  if (isset($_SESSION)) {
    unset($_SESSION['wincachesession']);
  }

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

  // In order to destroy we need to load that
  // particular session, but session ID's are random....
  // Need to iterate through all sessions!
  $scache_info = wincache_scache_info();
  $affected_sessions = array();
  $affected_prefix = wincache_session_id($uid, TRUE);
  $affected_length = strlen($affected_prefix);
  foreach ($scache_info['scache_entries'] as $sid) {
  	if (substr($sid['key_name'], 0, $affected_length) == $affected_prefix) {
      $affected_sessions[] = $sid['key_name'];
    }
  }
  _wincachedrupal_session_destroy($affected_sessions);
}

function _wincachedrupal_session_destroy(array $sids) {
  // Store current session.
  if (drupal_session_started()) {
    $current_session = session_id();
    session_write_close();
  }

  // Now clear all those sessions.
  foreach ($sids as $sid) {
    if ($current_session ==  $sid) {
      // We are clearing our own session...
      unset($current_session);
    }
    session_id($sid);
    session_start();
    session_destroy();
    session_write_close();
  }

  // Restore current user session.
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

/**
 * Call this instead of session_destroy()
 *
 * Cleans up a specific session.
 *
 * @param $sid
 *   Session ID.
 */
function drupal_session_destroy($sid = NULL) {
  global $user;

  // Nothing to do if we are not allowed to change the session.
  if (!drupal_save_session()) {
    return;
  }

  session_destroy();

  // Reset $_SESSION and $user to prevent a new session from being started
  // in drupal_session_commit().
  $_SESSION = array();
  $user = drupal_anonymous_user();

  // Unset the session cookies.
  _drupal_session_delete_cookie(session_name());
}

/**
 * Deletes the session cookie.
 *
 * @param $name
 *   Name of session cookie to delete.
 * @param boolean $secure
 *   Force the secure value of the cookie.
 */
function _drupal_session_delete_cookie($name) {
  if (isset($_COOKIE[$name])) {
    $params = session_get_cookie_params();
    setcookie($name, '', REQUEST_TIME - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    unset($_COOKIE[$name]);
  }
}
