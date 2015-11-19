#Wincache integration with Drupal

This Drupal modules provides implementations for several storage backends to leverage the power of Wincache.

##Motivation

Drupal and symfony heavily rely on in-memory storage to make things fast. Components such as the ClassLoader need in-memory storage to have an acceptable performance.

Actualy, PHP on Windows is not production ready without the use of Wincache. When enabled, Wincache alters some low level PHP behaviour - such as file system manipulation functions - so that it performs properly on windows.

Recommended readings:

 http://php.net/manual/es/book.wincache.php
 http://www.iis.net/learn/application-frameworks/install-and-configure-php-on-iis/use-the-windows-cache-extension-for-php
 http://forums.iis.net/t/1201106.aspx?WinCache+1+3+5+release+on+SourceForge

##Installation

Download the Wincache PHP extension:

 http://windows.php.net/downloads/pecl/releases/

Enable it in your PHP.ini and configure it:
```
[PHP_WINCACHE]
extension=php_wincache.dll

wincache.ocenabled=1
wincache.fcenabled=1
wincache.ucenabled=1
wincache.fcachesize = 500
wincache.maxfilesize = 5000
wincache.ocachesize = 200
wincache.filecount = 50000
wincache.ttlmax = 2000
wincache.ucachesize = 150
wincache.scachesize = 85
wincache.ucenabled=On
wincache.srwlocks=1
wincache.reroute_enabled=Off
```

Add this to your settings.php file:

```php
if ($settings['hash_salt']) {
  $prefix = 'drupal.' . hash('sha256', 'drupal.' . $settings['hash_salt']);
  $loader = new \Symfony\Component\ClassLoader\WincacheClassLoader($prefix, $class_loader);
  unset($prefix);
  $class_loader->unregister();
  $loader ->register();
  $class_loader = $loader ;
}

$settings['file_cache']['default'] = [
    'class' => '\Drupal\Component\FileCache\FileCache',
    'cache_backend_class' => \Drupal\wincachedrupal\Cache\WincacheFileCacheBackend::class,
    'cache_backend_configuration' => [],
  ];
```
