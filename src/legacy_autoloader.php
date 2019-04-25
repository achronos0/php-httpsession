<?php
/**
 * Autoload Useful classes without namespace support, for PHP 5 < 5.3
 *
 * @link https://github.com/morvren-achronos/useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

// The namespace to autoload
$sNamespace = 'Useful';

// Where the classes live
$sSourceDir = dirname(__FILE__); // ./src
$sCacheDir = dirname($sSourceDir) . DIRECTORY_SEPARATOR . 'lsrc'; // ../lsrc

require_once('Useful_Legacy/Loader.php');
Useful_Legacy_Loader::registerNamespace(
	$sNamespace,
	$sSourceDir . DIRECTORY_SEPARATOR . $sNamespace,
	$sCacheDir . DIRECTORY_SEPARATOR . $sNamespace
);
Useful_Legacy_Loader::registerSplAutoloader();
