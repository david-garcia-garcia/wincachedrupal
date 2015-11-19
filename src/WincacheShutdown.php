<?php

namespace Drupal\wincachedrupal;

class WincacheShutdown {
  public static function shutdown() {
    $user_cache_available = function_exists('wincache_ucache_info') && !strcmp(ini_get('wincache.ucenabled'), "1");
    if ($user_cache_available) {
      $ucache_mem_info = wincache_ucache_meminfo();
      // Under some situations WincacheDrupal will fail to report
      // any data through wincache_ucache_meminfo().
      if (empty($ucache_mem_info)) {
        // This watchdog has been commented out for performance reasons, the fact
        // that $ucache_mem_info is not working has been proved not to be an issue
        // or indicator that the overall cache is failing in any way.
        // watchdog('WincacheDrupal', 'User cache is improperly reporting memory usage statistics: @stats', array('@stats' => json_encode($ucache_mem_info)), WATCHDOG_DEBUG);
        return;
      }
      $ucache_available_memory = $ucache_mem_info['memory_total'] - $ucache_mem_info['memory_overhead'];
      $free_memory_ratio = $ucache_mem_info['memory_free'] / $ucache_available_memory;
      // If free memory is below 10% of total
      // do a cache wipe!
      if ($free_memory_ratio < 0.10) {
        $params = array();
        $params["@free"] = round($ucache_mem_info['memory_free'] / 1024, 0);
        $params["@total"] = round($ucache_mem_info['memory_total'] / 1024, 0);
        $params["@avail"] = round($ucache_available_memory / 1024, 0);
        wincache_log_warning('WincacheDrupal', 'Usercache threshold limit reached. @free Kb free out of @avail Kb available from a total of @total Kb. Cache cleared.', $params, WATCHDOG_CRITICAL);
        wincache_ucache_clear();
      }
    }
    else {
      // TODO: Check that the filemap directory exists!!
    }
  }
}
