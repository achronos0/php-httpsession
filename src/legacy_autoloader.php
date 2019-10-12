<?php
/**
 * Autoload Useful classes without namespace support, for PHP 5 < 5.3
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

/**
 * Configure autoloader
 *
 * Config keys are:
 *     array ns - Map of namespace prefixes and corresponding classpaths.
 *     string root_dir - Project root directory. Relative paths are resolved from here. Defaults to the directory that contains composer.json.
 *     string cache_dir - Root directory to store legacy classfiles. Must be writeable by PHP. Defaults to `root_dir` + "/legacy_src"
 *
 * @example
 *     $LEGACY_AUTOLOADER = array(
 *         'ns' => array(
 *             'Acme\\Things' => 'vendor/acme/things/src/Acme/Things',
 *         ),
 *     );
 *
 * @var array
 */
global $LEGACY_AUTOLOADER;

// Get Useful lib root directory
$sUsefulDir = dirname(dirname(__FILE__));

// Set defaults
if (!is_array($LEGACY_AUTOLOADER)) {
	$LEGACY_AUTOLOADER = array();
}
if (empty($LEGACY_AUTOLOADER['root_dir'])) {
	$LEGACY_AUTOLOADER['root_dir'] = $sUsefulDir;
	$sDir = dirname($sUsefulDir);
	while (strlen($sDir) > 2) {
		if (file_exists("$sDir/composer.json")) {
			$LEGACY_AUTOLOADER['root_dir'] = $sDir;
			break;
		}
		$sDir = dirname($sDir);
	}
}
if (empty($LEGACY_AUTOLOADER['cache_dir'])) {
	$LEGACY_AUTOLOADER['cache_dir'] = "$LEGACY_AUTOLOADER[root_dir]/legacy_src";
}
if (empty($LEGACY_AUTOLOADER['ns'])) {
	$LEGACY_AUTOLOADER['ns'] = array();
}
if (empty($LEGACY_AUTOLOADER['ns']['Useful'])) {
	$LEGACY_AUTOLOADER['ns']['Useful'] = "$sUsefulDir/src/Useful";
}
if (empty($LEGACY_AUTOLOADER['ns']['Psr\\Log'])) {
	$LEGACY_AUTOLOADER['ns']['Psr\\Log'] = "$LEGACY_AUTOLOADER[root_dir]/vendor/psr/log/Psr/Log";
}

// Include class Useful_Legacy_Loader
require_once("$sUsefulDir/src/Useful_Legacy/Loader.php");

// Register namespaces
foreach ($LEGACY_AUTOLOADER['ns'] as $sNamespace => $sSourceDir) {
	if (substr($sNamespace, 0, 1) == '\\') {
		$sNamespace = substr($sNamespace, 1);
	}
	if (substr($sSourceDir, 0, 1) != '/') {
		$sSourceDir = "$LEGACY_AUTOLOADER[root_dir]/$sSourceDir";
	}
	Useful_Legacy_Loader::registerNamespace(
		$sNamespace,
		str_replace('\\', '/', $sSourceDir),
		str_replace('\\', '/', "$LEGACY_AUTOLOADER[cache_dir]/$sNamespace")
	);
}

// Install PHP autoloader function
if (function_exists('spl_autoload_register')) {
	// Install SPL autoloader (PHP 5.1+)
	Useful_Legacy_Loader::registerSplAutoloader();
}
else {
	// Install __autoload (PHP 5.0)
	require_once("$sUsefulDir/src/legacy_autoloader_50.php");
}

// Remove global var
$LEGACY_AUTOLOADER = null;
unset($LEGACY_AUTOLOADER);
