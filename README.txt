/*****************
 * Using Wincache Extension to speed up your site
 ****************/
 This module provides a cache backend for Drupal 7, and a module that
 shows some statistics about wincache backend usage. There is NO need
 to enable the module in order to use the cache backend, this is manually
 setup in settings.php. Correctly setting up Wincache takes some time, but
 it is worth the effort.

 This README file also contains additional instructions on how to get the
 most of wincache, that can be used as (each one of these requires individual
 configuration).

 - 1. Cache Backend (AKA "User Cache")
 - 2. File system operation storage accelerator (file system function rerouting)
 - 3. File System Cache + Resolve Path Cache
 - 4. Opcode Cache (not needed anymore with PHP >= 5)
 - 5. Session Storage Handler

 Recommended readings:

 http://php.net/manual/es/book.wincache.php
 http://www.iis.net/learn/application-frameworks/install-and-configure-php-on-iis/use-the-windows-cache-extension-for-php
 http://forums.iis.net/t/1201106.aspx?WinCache+1+3+5+release+on+SourceForge

/*****************
 * 0. Installing and configuring the Wincache PHP Extension
 ****************/
 For all this to work you need to install the Wincache PHP Extension. I recommend
 downloading this extension from:
 
 http://windows.php.net/downloads/pecl/releases/
 
 These are officially compiled by the PHP.net team.
 
 Like with all other extensions, copy the one you need to the extensions folder
 of you PHP installation and enable it in PHP.ini
 
 [PHP_WINCACHE]
 extension=php_wincache.dll
 
 Restart IIS (or the application pool) to make these changes effective. You can
 check if the extension is enabled running phpinfo() or inside Drupal:
 
 http://yoursite/admin/reports/status/php
 
 Further in this document you will find details for the wincache ini settings,
 but for now this is a recommended configuration:
 
 ; Wincache ini configuration
 ; Disable Opcode cache (only for PHP >= 5, otherwise use 1)
 wincache.ocenabled=0
 ; Enable file cache
 wincache.fcenabled=1
 ; Enable user cache 
 wincache.ucenabled=1

 wincache.fcachesize = 255
 wincache.maxfilesize = 2048
 wincache.ocachesize = 255
 wincache.filecount = 8000
 wincache.ttlmax = 2000
 wincache.ucachesize = 85
 wincache.scachesize = 128

 ; NOTE: Documentation for wincache extension is unproperly pointing to max values
 ; for cache sizes. For ucachesize you can go all the way up to 2048Mb.
 
 ; Configure the file system funcion acces reroutes
 ; Make sure file exists and the IIS application has permissions to read.
 wincache.rerouteini = "C:\Program Files (x86)\PHP\php_5_5\reroute.ini"
 
 [Session]
 ; Handler used to store/retrieve data.
 ; http://php.net/session.save-handler
 session.save_handler = wincache
 ; Where to store sessions if wincache is the session handler.
 ; Make sure the folder exists and that the identity of you PHP process
 ; has read/write permissions.
 session.save_path = "C:\Program Files (x86)\PHP\php5_5_sessions\"

 ;Session data is not persisted by Wincache session handler if your appPool name has periods.
 ;If your app-pool name has periods (.) in it, change them to underscores (_). 
 ;So an app-pool named www.somesite.com should be renamed to www_somesite_com.
 
 To make sure these settings are working, check you extension settings in phpinfo()
 and access the Wincache statistics page, on you first run you will need to customize
 the username and password of the wincache.php access by directly editing the file.
 
 http://yoursite/sites/all/modules/drupalwincache/misc/wincache.php
 
/*****************
 * 1. Cache Backend (AKA "User Cache")
 ****************/
 Wincache uses in memory storage wich makes it way faster than memcache
 and other backend storage systems. It is a replacement for APC cache in
 windows systems. The drawback is that wincache has severe memory limitations
 so unless you have a small site, you should not be using it as your main
 cache backend, or be very carefull as to what cache tables you are redirecting
 to wincache. On small sites you can use Wincache as your default backend,
 on medium to large sites just use the most frequently accesed caches
 such as bootstrap, menu and/or variable. Maximum documented size for this
 cache is 85Mb but you can go all the way up to 2048Mb without problems.

 Adjust the cache size to fit your Drupal site, recomended to start with 128Mb.

 CAREFUL: Exhaustion of usercache memory will slow down your site, keep an eye
 every once in a while on the statistics page to make sure you have memory
 overhead, and if don't, just increase ucache size.
 
 Copy the drupalwincache module into you site's module folder:
 
 sites/all/modules/drupalwincache/
 
 In your site's settings.php (sites/default/settings.php): 
 
 // Register the new cache backend
 $conf['cache_backends'][0] = array('sites/all/modules/drupalwincache/drupal_win_cache.inc');

 // If you have more than one cache Backend at the same time, use this:
 $conf['cache_backends'][0] = 'sites/all/modules/memcache/memcache.inc';
 $conf['cache_backends'][1] = 'sites/all/modules/drupalwincache/drupal_win_cache.inc';

 // Tell Drupal what cache types this cache backend
 // should be used with. Wincache should used for non
 // persistent cache storage with low size requirements.
 $conf['cache_class_cache'] = 'DrupalWinCache';
 $conf['cache_class_cache_bootstrap'] = 'DrupalWinCache';
 $conf['cache_class_cache_menu'] = 'DrupalWinCache';
 $conf['cache_class_cache_variable'] = 'DrupalWinCache';

 // If you have a small site then just set wincache as the default backend
 // and forget about memcache.
 $conf['cache_default_class'] = 'DrupalWinCache';
 
 Once this settings are up and running, navigate you website for the cache
 contents to be warmed up and then visit the Wincache Statistics Page and make
 sure the User Cache is being used by visiting the User Cache tab.

/*****************
 * 2. File system operation storage accelerator 
 * http://php.net//manual/es/wincache.reroutes.php
 ****************/
 
 WinCache extension includes Windows-optimized implementation of PHP file 
 functions that may improve performance of PHP applications in 
 cases when PHP has to access files on network shares.
 
 To enable this function routing, change your php.ini:
 
 wincache.rerouteini = C:\PHP\reroute.ini
 
 If WinCache functions reroutes are enabled it is recommended
 to increase the WinCache file cache size in php.ini:
 
 wincache.fcachesize = 255
 
/*****************
 * 3. File System Cache + Resolve Path Cache
 ****************/
 
 You can use Wincache to cache file system files and path resolutions.
 To do this simply enable it in php.ini:
 
 ; Enable file cache
 wincache.fcenabled=1
 
/*****************
 * 4. Opcode Cache (not needed anymore with PHP >= 5)
 ****************/
 One of the most important uses of Wincache prior to PHP 5 whas as an opcode cache.
 
 ## If you are using PHP < 5:
 
 To enable use this in php.ini:
 
 wincache.ocenabled=0
 
 Make sure to increase the size of the cache to catter your site's needs:
 
 wincache.ocachesize = 255
 
 Now acces your site for the cache to heat up and verify that the Opcode cache
 is up and running in the Wincache Statistics page.
 
 ## If you are using PHP > 5:
 
 Disable wincache opcode cache:
 
 wincache.ocenabled=0
 
 Make sure you enable Zend OPCACHE:
 
 [opcache]
 ; Determines if Zend OPCache is enabled 
 opcache.enable=1
 zend_extension=php_opcache.dll

/*****************
 * 5. Session Storage Handler
 ****************/
 
 Wincache has a session handling implementation that is faster the php's native one.
 
 To enable in php.ini:
 
 session.save_handler = wincache
 
 Tell wincache where to store session data (persistent):
 
 ; Where to store sessions if wincache is the session handler.
 ; Make sure the folder exists and that the identity of you PHP process
 ; has read/write permissions.
 session.save_path = "C:\Program Files (x86)\PHP\php5_5_sessions\"
 
 Make sure you set an appropriate size depending on you site's usage:
 
 ; 85 Mb
 wincache.ucachesize = 85

 NOTE: Using this configuration will not tell Drupal to use wincache session
 handler because Drupal implements it's own session handling system that must
 be overriden within Drupal itself. Check this link for additional information:

 http://drupal.stackexchange.com/questions/39065/understanding-drupals-session-management-and-user-authentication

 EXPERIMENTAL: There is an experimental implementation of the wincache session
 handler for Drupal. Besides all the previous configuration, in your settings.php:

 $conf['session_inc'] = 'sites/all/modules/wincachedrupal/wincachedrupalsession.inc';

 This experimental version has not been tested under HTTPS (secure) sites.
 
/*****************
 * 6. Optional - Speed up anonymous page cache
 ****************/
 
 When using DrupalWinCache as default or manually caching the 'cache_page' bin
 in your settings file you do not need to start the database because Drupal can
 use the WinCache for pages. Add the following code to your settings.php file
 to do so:

 $conf['page_cache_without_database'] = TRUE;
 $conf['page_cache_invoke_hooks'] = FALSE;

 https://www.drupal.org/node/1176856
 
/*****************
 * 7. Optional - Enabling the module
 ****************/
 
 Along with the Wincache backend this package contains a Drupal module.
 This module has some tests to make sure that Wincache is properly running
 and also provides some UI statistics.

 The module does 3 things:

 - Adds information message to the status page.
 - Shows Wincache usage statistics when user has appropiate permissions
 and wincache_show_debug is set to true (in settings.php):
 $conf['wincache_show_debug'] = TRUE;
 - Provide a set of tests.