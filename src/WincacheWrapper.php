<?php

namespace Drupal\wincachedrupal;

class WincacheWrapper
{
  /**
   * @file
   * Wrapper functions for native wincache functions
   */

  /**
   * Determine if native wincache function exists.
   *
   * @return bool
   *   TRUE when native wincache functions exists.
   */
  public static function wincachedrupal_enabled()
  {
    return function_exists('wincache_ucache_info');
  }

  /**
   * Wraps wincache_scache_info().
   *
   * Retrieves information about files cached in the session cache.
   * See: http://www.php.net/manual/en/function.wincache-scache-info.php .
   *
   * @param bool $summaryonly
   *   Controls whether the returned array will contain information about
   *   individual cache entries along with the session cache summary.
   *
   * @return array|false
   *   Array of meta data about session cache or FALSE on failure.
   */
  public static function wincachedrupal_scache_info($summaryonly = FALSE)
  {
    static $exists = NULL;
    if (empty($exists)) {
      $exists = function_exists('wincache_scache_info');
    }
    if ($exists) {
      return wincache_scache_info($summaryonly);
    }
  }

  /**
   * Wraps wincache_scache_meminfo().
   *
   * Retrieves information about session cache memory usage.
   * See: https://www.php.net/manual/en/function.wincache-scache-meminfo.php.
   *
   * @return array|false
   *   Array of meta data about session cache memory usage or FALSE on failure.
   */
  public static function wincachedrupal_scache_meminfo()
  {
    static $exists = NULL;
    if (empty($exists)) {
      $exists = function_exists('wincache_scache_meminfo');
    }
    if ($exists) {
      return wincache_scache_meminfo();
    }
  }

  /**
   * Wraps wincache_ucache_add().
   *
   * Adds a variable in user cache only if variable does not already exist in the
   * cache.
   * See: http://www.php.net/manual/en/function.wincache-ucache-add.php .
   *
   * @param string|array $key
   *   Store the variable using this key name. If a variable with same key is
   *   already present the function will fail and return FALSE. key is case
   *   sensitive. To override the value even if key is present use
   *   wincache_ucache_set() function instad. key can also take array of
   *   name => value pairs where names will be used as keys. This can be used to
   *   add multiple values in the cache in one operation, thus avoiding race
   *   condition.
   * @param mixed|null $value
   *   Value of a variable to store. Value supports all data types except
   *   resources, such as file handles. This paramter is ignored if first argument
   *   is an array. A general guidance is to pass NULL as value while using array
   *   as key. If value is an object, or an array containing objects, then the
   *   objects will be serialized. See __sleep() for details on serializing
   *   objects.
   * @param int $ttl
   *   Time for the variable to live in the cache in seconds. After the value
   *   specified in ttl has passed the stored variable will be deleted from the
   *   cache. This parameter takes a default value of 0 which means the variable
   *   will stay in the cache unless explicitly deleted by using
   *   wincache_ucache_delete() or wincache_ucache_clear() functions.
   *
   * @return bool
   *   If key is string, the function returns TRUE on success and FALSE on
   *   failure. If key is an array, the function returns: If all the name => value
   *   pairs in the array can be set, function returns an empty array;
   *   If all the name => value pairs in the array cannot be set, function returns
   *   FALSE;
   *   If some can be set while others cannot, function returns an array with
   *   name=>value pair for which the addition failed in the user cache.
   */
  public static function wincachedrupal_ucache_add($key, $value, $ttl = 0)
  {
    static $exists = NULL;
    if (empty($exists)) {
      $exists = function_exists('wincache_ucache_add');
    }
    if ($exists) {
      return wincache_ucache_add($key, $value, $ttl);
    }

    return FALSE;
  }

  /**
   * Wraps wincache_ucache_clear().
   *
   * Deletes entire content of the user cache.
   * See: http://www.php.net/manual/en/function.wincache-ucache-clear.php .
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  public static function wincachedrupal_ucache_clear()
  {
    static $exists = NULL;
    if (empty($exists)) {
      $exists = function_exists('wincache_ucache_clear');
    }
    if ($exists) {
      return wincache_ucache_clear();
    }

    return TRUE;
  }

  /**
   * Wraps wincache_ucache_delete().
   *
   * Deletes variables from the user cache.
   * See: http://www.php.net/manual/en/function.wincache-ucache-delete.php .
   *
   * @param string|array $key
   *   The key that was used to store the variable in the cache. key is case
   *   sensitive. key can be an array of keys.
   *
   * @return bool
   *   If key is an array then the function returns FALSE if every element of the
   *   array fails to get deleted from the user cache, otherwise returns an array
   *   which consists of all the keys that are deleted.
   */
  public static function wincachedrupal_ucache_delete($key)
  {
    static $exists = NULL;
    if (empty($exists)) {
      $exists = function_exists('wincache_ucache_delete');
    }
    if ($exists) {
      return wincache_ucache_delete($key);
    }

    return TRUE;
  }

  /**
   * Wrap wincache_ucache_exists().
   *
   * Checks if a variable exists in the user cache.
   * See: http://www.php.net/manual/en/function.wincache-ucache-exists.php .
   *
   * @param string $key
   *   The key that was used to store the variable in the cache. key is case
   *   sensitive.
   *
   * @return bool
   *   Returns TRUE if variable with the key exitsts, otherwise returns FALSE.
   */
  public static function wincachedrupal_ucache_exists($key)
  {
    static $exists = NULL;
    if (empty($exists)) {
      $exists = function_exists('wincache_ucache_exists');
    }
    if ($exists) {
      return wincache_ucache_exists($key);
    }

    return FALSE;
  }

  /**
   * Wraps wincache_ucache_get().
   *
   * Gets a variable stored in the user cache.
   * See: http://www.php.net/manual/en/function.wincache-ucache-get.php .
   *
   * @param string|array $key
   *   The key that was used to store the variable in the cache. key is case
   *   sensitive. key can be an array of keys. In this case the return value will
   *   be an array of values of each element in the key array. If an object, or an
   *   array containing objects, is returned, then the objects will be
   *   unserialized.
   * @param bool $success
   *   Will be set to TRUE on success and FALSE on failure.
   *
   * @return mixed|array
   *   If key is a string, the function returns the value of the variable stored
   *   with that key. The success is set to TRUE on success and to FALSE on
   *   failure.
   *   The key is an array, the parameter success is always set to TRUE.
   *   The returned array (name => value pairs) will contain only those
   *   name => value pairs for which the get operation in user cache was
   *   successful. If none of the keys in the key array finds a match in the user
   *   cache an empty array will be returned.
   */
  public static function wincachedrupal_ucache_get($key, &$success = FALSE)
  {
    static $exists = NULL;
    if (empty($exists)) {
      $exists = function_exists('wincache_ucache_get');
    }
    if ($exists) {
      return wincache_ucache_get($key, $success);
    }
    if (is_array($key)) {
      $success = TRUE;
      return [];
    } else {
      $success = FALSE;
    }
  }

  /**
   * Wraps wincache_ucache_inc().
   *
   * Increments the value associated with the key.
   * See: http://www.php.net/manual/en/function.wincache-ucache-inc.php .
   *
   * @param string|array $key
   *   The key that was used to store the variable in the cache. key is case
   *   sensitive.
   * @param int $inc_by
   *   The value by which the variable associated with the key will get
   *   incremented. If the argument is a floating point number it will be
   *   truncated to nearest integer. The variable associated with the key should
   *   be of type long, otherwise the function fails and returns FALSE.
   * @param bool $success
   *   Will be set to TRUE on success and FALSE on failure.
   *
   * @return int|false
   *   Returns the incremented value on success and FALSE on failure.
   */
  public static function wincachedrupal_ucache_inc($key, $inc_by = 1, &$success = FALSE)
  {
    static $exists = NULL;
    if (empty($exists)) {
      $exists = function_exists('wincache_ucache_inc');
    }
    if ($exists) {
      return wincache_ucache_inc($key, $inc_by, $success);
    }
    $success = FALSE;

    return FALSE;
  }

  /**
   * Wraps wincache_ucache_info().
   *
   * Retrieves information about data stored in the user cache.
   * See: http://www.php.net/manual/en/function.wincache-ucache-info.php .
   *
   * @param bool $summaryonly
   *   Controls whether the returned array will contain information about
   *   individual cache entries along with the user cache summary.
   * @param string|null $key
   *   The key of an entry in the user cache. If specified then the returned array
   *   will contain information only about that cache entry. If not specified and
   *   summaryonly is set to FALSE then the returned array will contain
   *   information about all entries in the cache.
   *
   * @return array|false
   *   Array of meta data about user cache or FALSE on failure
   *   The array returned by this function contains the following elements:
   */
  public static function wincachedrupal_ucache_info($summaryonly = FALSE, $key = NULL)
  {
    static $exists = NULL;
    if (empty($exists)) {
      $exists = function_exists('wincache_ucache_info');
    }
    if ($exists) {
      if (empty($key)) {
        return wincache_ucache_info($summaryonly);
      } else {
        return wincache_ucache_info($summaryonly, $key);
      }
    }

    return FALSE;
  }

  /**
   * Wrap wincache_ucache_meminfo().
   *
   * Retrieves information about user cache memory usage.
   * See: http://www.php.net/manual/en/function.wincache-ucache-meminfo.php .
   *
   * @return array|false
   *   Array of meta data about user cache memory usage or FALSE on failure
   */
  public static function wincachedrupal_ucache_meminfo()
  {
    static $exists = NULL;
    if (empty($exists)) {
      $exists = function_exists('wincache_ucache_meminfo');
    }
    if ($exists) {
      return wincache_ucache_meminfo();
    }

    return FALSE;
  }

  /**
   * Wraps wincache_ucache_set().
   *
   * Adds a variable in user cache and overwrites a variable if it already exists
   * in the cache.
   * See: http://www.php.net/manual/en/function.wincache-ucache-set.php .
   *
   * @param string|array $key
   *   Store the variable using this key name. If a variable with same key is
   *   already present the function will overwrite the previous value with the new
   *   one. key is case sensitive. key can also take array of name => value pairs
   *   where names will be used as keys. This can be used to add multiple values
   *   in the cache in one operation, thus avoiding race condition.
   * @param mixed|null $value
   *   Value of a variable to store. Value supports all data types except
   *   resources, such as file handles. This paramter is ignored if first argument
   *   is an array. A general guidance is to pass NULL as value while using array
   *   as key. If value is an object, or an array containing objects, then the
   *   objects will be serialized. See __sleep() for details on serializing
   *   objects.
   * @param int $ttl
   *   Time for the variable to live in the cache in seconds. After the value
   *   specified in ttl has passed the stored variable will be deleted from the
   *   cache. This parameter takes a default value of 0 which means the variable
   *   will stay in the cache unless explicitly deleted by using
   *   wincache_ucache_delete() or wincache_ucache_clear() functions.
   *
   * @return bool
   *   If key is string, the function returns TRUE on success and FALSE on
   *   failure. If key is an array, the function returns:
   *   If all the name => value pairs in the array can be set, function returns an
   *   empty array;
   *   If all the name => value pairs in the array cannot be set, function returns
   *   FALSE;
   *   If some can be set while others cannot, function returns an array with
   *   name=>value pair for which the addition failed in the user cache.
   */
  public static function wincachedrupal_ucache_set($key, $value, $ttl = 0)
  {
    static $exists = NULL;
    if (empty($exists)) {
      $exists = function_exists('wincache_ucache_set');
    }

    if ($exists) {
      return wincache_ucache_set($key, $value, $ttl);
    }

    return FALSE;
  }
}
