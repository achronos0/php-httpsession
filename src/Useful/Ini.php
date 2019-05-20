<?php
/**
 * \Useful\Ini class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful;

use Useful\Ini\Generator, Useful\Ini\Parser;

/**
 * Parse and generate INI-format text data
 *
 * @uses \Useful\Exception
 * @uses \Useful\Ini\Generator
 * @uses \Useful\Ini\Parser
 */
class Ini
{
	//////////////////////////////
	// Public static

	/**
	 * Read INI file into an array
	 *
	 * @param string $sFilePath file path
	 * @param array $aOptions configuration settings, see docs
	 * @return array parsed data, or false on error
	 * @throws \Useful\Exception
	 */
	public static function read($sFilePath, $aOptions = array())
	{
		$oParser = new Parser($aOptions);
		return $oParser->parseFile($sFilePath);
	}

	/**
	 * Parse INI string into an array
	 *
	 * @param string $sContent delimited text content
	 * @param array $aOptions configuration settings, see docs
	 * @return array parsed data
	 */
	public static function parse($sContent, $aOptions = array())
	{
		$oParser = new Parser($aOptions);
		return $oParser->parseString($sContent);
	}

	/**
	 * Write INI file from an array
	 *
	 * If file exists it will be overwritten.
	 *
	 * @param string $sFilePath file path
	 * @param array $aData data to write
	 * @param array $aOptions configuration settings, see docs
	 * @return void
	 * @throws \Useful\Exception
	 */
	public static function write($sFilePath, $aData, $aOptions = array())
	{
		$oGenerator = new Generator($aOptions);
		return $oGenerator->generateFile($sFilePath, $aData);
	}

	/**
	 * Generate INI string from array
	 *
	 * @param array $aData data to write
	 * @param array $aOptions configuration settings, see docs
	 * @return string generated INI text
	 */
	public static function generate($aData, $aOptions = array())
	{
		$oGenerator = new Generator($aOptions);
		return $oGenerator->generateString($aData);
	}
}
