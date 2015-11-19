<?php

namespace Drupal\wincachedrupal;

use Drupal\Core\StringTranslation\StringTranslationTrait;

class Installer {

  use StringTranslationTrait;

  public function requirements($phase) {
    $requirements = array();

    // Test WinCache.
    $wincache_ucache_enabled = (function_exists('wincache_ucache_info') && ($cache = @wincache_ucache_info(TRUE)));
    
    $wincache_version = phpversion('wincache');
    $requirements['wincache'] = array(
      'title' => $this->t('WinCache version'),
      'value' => $wincache_ucache_enabled ? $wincache_version : $this->t('Not available'),
      'severity' => $wincache_ucache_enabled ? REQUIREMENT_OK : REQUIREMENT_ERROR,
      'description' => $wincache_ucache_enabled ? NULL : $this->t('The wincachedrupal module needs the wincache extension see: !link.', array('!link' => l('http://php.net/manual/en/book.wincache.php', '')))
    );

    if ($wincache_ucache_enabled) {

      /** @var \Drupal\Core\Datetime\DateFormatterInterface */
      $formatter =  \Drupal::service('date.formatter');

      $ucache_meminfo = wincache_ucache_meminfo();

      $requirements['wincache_ucache'] = array(
        'title' => $this->t('WinCache user cache'),
        'value' => $this->t('Enabled. Memory total: !total. Used !used%.', array(
          '!total' => format_size($ucache_meminfo['memory_total']),
          '!used' => round(100 - (($ucache_meminfo['memory_free'] / $ucache_meminfo['memory_total']) * 100), 2),
        )),
        'severity' => $wincache_ucache_enabled ? REQUIREMENT_OK : REQUIREMENT_ERROR,
      );


      $requirements['wincache_ucache']['description'] = $this->t('WinCache has been running for !duration. Currently caching !num_entries entries. Hit rate !hitrate%.',
        array(
          '!duration' => $formatter->formatInterval($cache['total_cache_uptime']),
          '!num_entries' => $cache['total_item_count'],
          '!hitrate' => round(($cache['total_hit_count'] / ($cache['total_hit_count'] + $cache['total_miss_count'])) * 100, 2) ,
        )
      );
    }

    // Check for opcache size configuration.
    if ($phase == 'runtime') {
      $options = ini_get_all('wincache', true);
      // 1.3.7.2 What's new:
      // If you disable the WinCache opcache in the php.ini (wincache.ocenabled=0),
      // you should no longer see a shared memory mapping for the opcache.
      // Also, you won't be able to turn it on in either a .user.ini or in a php script.
      if (version_compare($wincache_version, '1.3.7.2', '<')) {
        if ($options['wincache.ocenabled']['local_value'] == 0) {
          $ocachesize = $options['wincache.ocachesize']['local_value'];
          $requirements['wincache_oc_size'] = array(
            'title' => $this->t('Wincache opcode cache size'),
            'value' => $this->t('Opcode caching is disabled and current cache size is !sizeMb', array('!size' => $ocachesize)),
            'severity' => ($ocachesize > 15) ? REQUIREMENT_ERROR : REQUIREMENT_OK,
            'description' => $this->t('When opcode caching is disabled, reduce memory pressure on the server by setting wincache.ocachesize to the minimum value allowed (15Mb).')
          );
        }
      }

      // Zend OPCACHE is UNSTABLE on Windows and.... no one cares.
      // Is this a windows server?
      // Probably yes, because this is the WincacheDriver!
      $is_windows = strncasecmp(PHP_OS, 'WIN', 3) == 0;
      if ($is_windows) {
        // Make sure that we are using Wincache OPCODE cache.
        $opcode_ok = $options['wincache.ocenabled']['local_value'] == 1;
        $opcode_info = wincache_ocache_meminfo();
        $requirements['wincache_oc'] = array(
          'title' => $this->t('Wincache Opcode cache'),
          'value' => $opcode_ok ? $this->t('Opcode cache is being handled by Wincache.') : $this->t('The Wincache Opcode cache should be enabled. Do not rely on ZEND_OPCACHE, it is unstable on windows platforms.'),
          'severity' => $opcode_ok ? REQUIREMENT_OK : REQUIREMENT_ERROR,
        );

        //  Why is there a wincache.apppoolid setting?
        //  A: For debugging purposes only.  It should never be explicitly set in production environments.
        //  Q: Has WinCache been tested with custom application pool identities?  e.g. NetworkService, LocalSystem, LocalService, or "Custom account" in the App Pool | Advanced Settings | Application Pool Identity dialog of inetmgr.exe
        //  A: No, it has not.  It's very possible that it won't work for anything other than ApplicationPoolIdentity.
        //  Q: What happens when wincache.apppoolid is not set?
        //  A: When IIS launches php-cgi.exe, it adds an environment variable (APP_POOL_ID), and that's what wincache will use if the apppoolid setting is not set.  The variable will contain the account name under the IIS APPPOOL domain to use for the app pool.
        $apppool_ok = empty($options['wincache.apppoolid']['local_value']);
        if (!$apppool_ok) {
          $requirements['wincache_apppoolid'] = array(
            'title' => $this->t('Wincache wincache.apppoolid ini setting'),
            'value' =>  $this->t('wincache.apppoolid should never be used in production environments!'),
            'severity' => REQUIREMENT_WARNING,
          );
        }
      }
    }

    return $requirements;
  }
}
