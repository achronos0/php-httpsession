# Useful_Legacy_Loader

This class loads namespaced classes into a non-namespace-suppporting PHP, i.e. PHP 5 < 5.3.

## Usage

```php
require_once('WHEREVER_YOU_PUT_VENDOR_CODE/useful/src/Useful_Legacy/Loader.php');

$sCacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'legacy_class_cache';

Useful_Legacy_Loader::registerNamespace(
	'\Foo\Bar_Baz',
	'WHEREVER_YOU_PUT_VENDOR_CODE/my_library/src/Foo/Bar_Baz/',
	$sCacheDir
);

$oMyBat = new Foo_Bar_Baz_Bat();
```

## Autoloader

Useful itself includes a legacy autoloader to allow it to be used in PHP 5.1 projects.

That's at `src/legacy_autoloader.php`.

You can copy that and replace the relevant bits for your project.
