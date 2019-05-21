<?php
/**
 * Autoload Useful classes without namespace support, for PHP 5 < 5.3
 *
 * @link https://github.com/morvren-achronos/useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

// Files are relative to here (Useful package root)
$sRootDir = dirname(dirname(__FILE__));

// Where to put cached class files
$sCacheDir = "$sRootDir/lsrc";

// The namespaces to autoload, and where to find the classes
$aNamespaceList = array(
	'Useful' => "$sRootDir/src/Useful",
	'Psr\\Log' => "$sRootDir/vendor/psr/log/Psr/Log",
);


////////////////////

// Include Useful_Legacy_Loader class
require_once('Useful_Legacy/Loader.php');

// Register namespaces for loading
foreach ($aNamespaceList as $sNamespace => $sSourceDir) {
	Useful_Legacy_Loader::registerNamespace($sNamespace, $sSourceDir, "$sCacheDir/$sNamespace");
}
Useful_Legacy_Loader::registerSplAutoloader();
