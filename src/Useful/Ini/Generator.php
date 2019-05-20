<?php
/**
 * \Useful\Ini\Generator class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Ini;

use Useful\ArrayPatterns, Useful\Exception;

/**
 * Generate INI-format text from PHP array
 *
 * @uses \Useful\ArrayPatterns
 * @uses \Useful\Exception
 */
class Generator
{
	//////////////////////////////
	// Public

	/**
	 * Create INI generator
	 *
	 * @param array $aOptions set options
	 */
	public function __construct($aOptions = array())
	{
		if ($aOptions) {
			$this->setOptions($aOptions);
		}
	}

	/**
	 * Write INI file from an array
	 *
	 * If file exists it will be overwritten.
	 *
	 * @param string $sFilePath file path
	 * @param array $aData data to write
	 * @return void
	 * @throws \Useful\Exception
	 */
	public function generateFile($sFilePath, $aData)
	{
		if (file_exists($sFilePath)) {
			if (!is_file($sFilePath)) {
				throw new Exception('Path is not a file');
			}
			if (!is_writable($sFilePath)) {
				throw new Exception('Path is not writable');
			}
		}
		elseif (!is_writable(dirname($sFilePath))) {
			throw new Exception('Path is not writable');
		}

		$sContent = $this->generateString($aData);

		if (file_put_contents($sFilePath, $sContent) === false) {
			throw new Exception('Cannot write to file');
		}
	}

	/**
	 * Generate INI string from array
	 *
	 * @param array $aData data to write
	 * @param array $aOptions configuration settings, see docs
	 * @return string generated INI text
	 */
	public function generateString($aData)
	{
		return $this->handleArray($aData, '');
	}

	/**
	 * Return currently configured options
	 *
	 * @return array options
	 */
	public function getOptions()
	{
		return $this->aOptions;
	}

	/**
	 * Apply updated configuration options
	 *
	 * @param array $aOptions new configuration settings
	 * @return void
	 */
	public function setOptions($aOptions)
	{
		$this->aOptions = ArrayPatterns::mergeConfig($this->aOptions, $aOptions);
	}


	//////////////////////////////
	// Internal

	protected $aOptions = array(
		'element_separator' => "\x0a",
		'pair_separator' => '=',
		'hierarchy_separator' => '.',
		'lists' => true,
		'list_separator' => ' ',
		'list_open' => '[',
		'list_close' => ']',
		'null_value' => 'NOTHING',
		'true_value' => 'YES',
		'false_value' => 'NO',
		'empty_array_value' => '[]',
		'special_values' => array(
			'TRUE' => true,
			'ON' => true,
			'YES' => true,
			'Y' => true,
			'FALSE' => false,
			'OFF' => false,
			'NO' => false,
			'N' => false,
			'NULL' => null,
			'NONE' => null,
			'NOTHING' => null,
		),
	);

	// Return INI text for associative array
	protected function handleArray($aData, $sKeyPrefix)
	{
		$sContent = '';
		foreach ($aData as $sKey => $mValue) {
			// Add key prefix for nested array
			if ($sKeyPrefix != '')
				$sKey = $sKeyPrefix . $this->aOptions['hierarchy_separator'] . $sKey;

			// Simple value, write as key=value
			if (!is_array($mValue)) {
				$sContent .=
					$sKey
					. $this->aOptions['pair_separator']
					. $this->handleScalar($mValue, false, $this->aOptions)
					. $this->aOptions['element_separator']
				;
				continue;
			}

			// Empty array, write as key=[]
			if (!count($mValue)) {
				$sContent .=
					$sKey
					. $this->aOptions['pair_separator']
					. $this->aOptions['empty_array_value']
					. $this->aOptions['element_separator']
				;
				continue;
			}

			// List, write as key=[value value ...]
			if ($this->aOptions['lists'] && ArrayPatterns::isList($mValue)) {
				$aValues = array();
				foreach ($mValue as $mSubValue)
					$aValues[] = $this->handleScalar($mSubValue, true, $this->aOptions);
				$sContent .=
					$sKey
					. $this->aOptions['pair_separator']
					. $this->aOptions['list_open']
					. implode($this->aOptions['list_separator'], $aValues)
					. $this->aOptions['list_close']
					. $this->aOptions['element_separator']
				;
				continue;
			}

			// Nested array
			$sContent .= $this->handleArray($mValue, $sKey);
		}

		// Return content
		return $sContent;
	}

	// Return INI text for simple (scalar/null) value
	protected function handleScalar($mValue, $bQuoteWhitespace)
	{
		// NULL
		if ($mValue === null)
			return $this->aOptions['null_value'];

		// Boolean
		if ($mValue === true)
			return $this->aOptions['true_value'];
		if ($mValue === false)
			return $this->aOptions['false_value'];

		// Int, float or something exotic
		if (!is_string($mValue))
			return strval($mValue);

		// Empty text
		if ($mValue === '')
			return '""';

		// Quoted text
		if (
			// contains newline
			strpos($mValue, $this->aOptions['element_separator']) !== false
			// contains whitespace, and we're inside a list value
			|| (
				$bQuoteWhitespace
				&& preg_match('/\s/', $mValue)
			)
			// same text as a special value, so must be quoted
			|| isset($this->aOptions['special_values'][strtoupper($mValue)])
			// starts or ends with whitespace
			|| preg_match('/^\s+/', $mValue)
			|| preg_match('/\s+$/', $mValue)
		) {
			return '"' . str_replace('\\', '\\\\', $mValue) . '"';
		}

		// Plain text
		return $mValue;
	}
}
