# useful

Useful is a PHP library collection with a focus on simplicity. Take what you want, ignore the rest.

Goals:

*Utility*

Useful provides convenience that is not easily found anywhere else.

It solves common challenges that are not well addressed by the PHP language, common frameworks or popular libraries.

*Simplicity*

Useful can be used quickly without lots of reading and training.

Class hierarchy is flat. Each class does one thing. All classes are immediately useful to application developers.

*Easy integration*

Useful does not _require_ you to do anything in one particular way.

It has few dependencies, and dependencies are clearly marked. Every class stands on its own and can be used on its own.

## Requirements

PHP 5 >= 5.4 or PHP 7; or PHP 5 >= 5.0 with special handling (see _No namespaces_ below).

Most classes should work anywhere that PHP does. Exceptions are clearly marked.


## Installation

### Composer

Useful supports Composer. If you do too, great, install Useful using it and go.

Example:

### Manual

You can clone from Github, or just copy in the files.

Clone from Github:

```sh
git clone https://github.com/morvren-achronos/php-useful.git WHEREVER_YOU_PUT_VENDOR_CODE/useful
```

You can even paste in a single class; Useful classes have no interdependencies, so if you only want one just take it and use it.

## Usage

### Composer autoload

If you use Composer then Useful code will autoload automatically. All classes are in the `\Useful` namespace.

Example:

```php
// Create a Useful Date object
$oDate = \Useful\Date::create('1999-12-31');
```

### Include Useful's autoloader

```php
// Load Useful autoloader directly
require_once('WHEREVER_YOU_PUT_VENDOR_CODE/useful/src/autoloader.php');

// Create a Useful Date object
$oDate = \Useful\Date::create('1999-12-31');
```

It's a simple PSR-4 autoloader for the `\Useful` namespace.

### No namespaces: Legacy loader

Useful supports PHP 5 back to (in theory) 5.0 via its "legacy loading" system.

Note, the test suite requires PHP 7. Author has used parts of Useful on PHP 5.1. Your mileage may vary.

#### PHP 5.1-compatible SPL autoloader

```php
// Load Useful legacy autoloader
require_once('WHEREVER_YOU_PUT_VENDOR_CODE/useful/src/legacy_autoloader.php');

// Create a Useful Date object
$oDate = Useful_Date::create('1999-12-31');
```

The legacy autoloader uses a classname prefix (`Useful_Foo`) instead of namespace (`\Useful\Foo`).

#### PHP 5.0 loader

PHP 5.0 does not have `spl_autoload_register()`. That's ok. The legacy loader can be used to load a class directly without namespace.

```php
// Load Useful_Legacy_Loader class
require_once('WHEREVER_YOU_PUT_VENDOR_CODE/useful/src/Useful_Legacy/Loader.php');
Useful_Legacy_Loader::registerNamespace(
	'\Useful',
	'WHEREVER_YOU_PUT_VENDOR_CODE/useful/src/Useful',
	'WRITABLE_TEMP_DIRECTORY_FOR_CODE_CACHING/'
);

function __autoload($sClass)
{
	// Try Useful legacy loader, it will return FALSE if not handled.
	// You could also just call this method directly for each class you want, without an autoloader.
	if (Useful_Legacy_Loader::loadClass($sClass)) {
		return;
	}

	// ... whatever else you need to autoload
}

// Create a Useful Date object
$oDate = Useful_Date::create('1999-12-31');
```

## Tests

Uses PHPUnit. Test coverage is [2019-04] @TODO very incomplete for some classes.
