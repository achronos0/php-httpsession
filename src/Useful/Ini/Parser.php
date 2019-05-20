<?php
/**
 * \Useful\Ini\Parser class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Ini;

use Useful\ArrayPatterns, Useful\Exception;

/**
 * Parse INI-format text into PHP array
 *
 * @uses \Useful\ArrayPatterns
 * @uses \Useful\Exception
 */
class Parser
{
	//////////////////////////////
	// Public

	/**
	 * Create INI parser
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
	 * Read INI file into an array
	 *
	 * @param string $sFilePath file path
	 * @return array parsed data
	 * @throws \Useful\Exception
	 */
	public function parseFile($sFilePath)
	{
		if (!file_exists($sFilePath)) {
			throw new Exception('Path does not exist');
		}
		if (!is_file($sFilePath)) {
			throw new Exception('Path is not a file');
		}
		if (!is_readable($sFilePath)) {
			throw new Exception('Path is not readable');
		}

		$sContent = file_get_contents($sFilePath);
		if ($sContent === false) {
			throw new Exception('Cannot read from file');
		}

		return $this->parseString($sContent);
	}

	/**
	 * Parse INI string into an array
	 *
	 * @param string $sContent delimited text content
	 * @return array parsed data
	 */
	public function parseString($sContent)
	{
		$this->prepRegex();

		// Prepare data handler
		if ($this->aOptions['data_handler_call']) {
			$xDataHandler = $this->aOptions['data_handler_call'];
		}
		elseif ($this->aOptions['hierarchy']) {
			$this->aResultData = array();
			$xDataHandler = array($this, '_storeHierarchy');
		}
		else {
			$this->aResultData = array();
			$xDataHandler = array($this, '_storeBasic');
		}

		// Prepare input lines
		$this->aLines = preg_split($this->aRegex['element_separator'], $sContent);

		// Track current section name
		$sCurrentSection = '';

		// Handle each line
		while ($this->aLines) {
			// Get next line
			$this->sCurrentLine = array_shift($this->aLines);

			// Comment or empty line
			if ($this->lineIsEmpty()) {
				continue;
			}

			// Begin section
			if (
				$this->aOptions['sections']
				&& preg_match('/^\s*\[\s*([^]]*?)\s*\]\s*$/', $this->sCurrentLine, $aMatches)
			) {
				$sCurrentSection = $aMatches[1];
				if (strtolower($sCurrentSection) == $this->aOptions['section_top_name'])
					$sCurrentSection = '';
				continue;
			}

			// Get key/value pair
			if (!preg_match($this->aRegex['pair'], $this->sCurrentLine, $aMatches)) {
				continue;
			}
			$sKey = $aMatches[1];
			$mValue = isset($aMatches[2]) ? $aMatches[2] : null;

			// Ignore empty pair
			if ($sKey === '' && ($mValue === '' || $mValue === null)) {
				continue;
			}

			// Parse value
			$mValue = $this->parseRhs($mValue, $this->aLines, $this->aOptions);

			// Store key/value
			if (call_user_func($xDataHandler, $sCurrentSection, $sKey, $mValue) === false) {
				break;
			}
		}

		// Get results
		$aResults = $this->aResultData;

		// Reset runtime
		$this->aLines = null;
		$this->sCurrentLine = null;
		$this->aResultData = null;


		// Return results
		return $aResults;
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
		$this->aRegex = null;
	}


	//////////////////////////////
	// Internal

	protected $aOptions = array(
		'element_separator' => array( "\x0d\x0a", "\x0d", "\x0a" ),
		'pair_separator' => '=',
		'sections' => true,
		'section_top_name' => 'general',
		'hierarchy' => true,
		'hierarchy_separator' => '.',
		'comments' => true,
		'comment_start' => array( '#', '//' ),
		'multiline_comments' => true,
		'multiline_comment_open' => '/*',
		'multiline_comment_close' => '*/',
		'quotes' => true,
		'lists' => true,
		'list_open' => array( '[', '{' ),
		'list_close' => array( ']', '}' ),
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
			'EMPTYLIST' => array(),
		),
		'data_handler_call' => null,
	);
	protected $aRegex;
	protected $aLines;
	protected $sCurrentLine;
	protected $aResultData;

	/**
	 * Check for empty line or comment
	 *
	 * @return bool
	 */
	protected function lineIsEmpty()
	{
		// Empty line
		if (preg_match($this->aRegex['empty_line'], $this->sCurrentLine))
			return true;

		// Single-line comment
		if (
			$this->aRegex['comment_line']
			&& preg_match($this->aRegex['comment_line'], $this->sCurrentLine)
		) {
			return true;
		}

		// Multi-line comment
		if ($this->parseBlock('multiline_comment', $this->sCurrentLine) !== false)
			return true;

		// Meaningful content
		return false;
	}

	/**
	 * Convert RHS of INI pair to PHP value
	 *
	 * @param string $sValue
	 * @return mixed
	 */
	protected function parseRhs($sValue)
	{
		// Empty value
		if ($sValue === '') {
			return null;
		}

		if ($this->aOptions['quotes']) {
			// Empty quoted value
			if ($sValue == '""' || $sValue == '\'\'')
				return '';

			// Quoted value
			$sFirstChar = substr($sValue, 0, 1);
			if ($sFirstChar == '\'' || $sFirstChar == '"') {
				$sRegex = '/(?<!\\\\)' . $sFirstChar . '\s*$/';
				if (!preg_match($sRegex, $sValue)) {
					while ($this->aLines) {
						$this->sCurrentLine = array_shift($this->aLines);
						$sValue .= "\x0a" . $this->sCurrentLine;
						if (preg_match($sRegex, $this->sCurrentLine))
							break;
					}
				}
				return $this->parseScalar(
					substr($sValue, 1, -1),
					$sFirstChar == '"' ? 2 : 1,
					$this->aOptions
				);
			}
		}

		// List value
		$sListContent = $this->parseBlock('list_value', $sValue);
		if ($sListContent !== false) {
			// Parse list content into separate values
			preg_match_all(
				'/(?:([\w\.]+)\s*[:=]+\s*)?(?:\'((?:\\\\\'|[^\'])*)\'|"((?:\\\\"|[^"])*)"|([^,;\s]+))[,;\s]*/m',
				$sListContent,
				$aMatches,
				PREG_SET_ORDER
			);

			// Parse individual values in list
			$aValues = array();
			foreach ($aMatches as $aMatch) {
				$mVal =
					isset($aMatch[4])
					? $this->parseScalar($aMatch[4], 0, $this->aOptions)
					: (
						isset($aMatch[3])
						? $this->parseScalar($aMatch[3], 2, $this->aOptions)
						: $this->parseScalar($aMatch[2], 1, $this->aOptions)
					)
				;
				if ($aMatch[1] != '') {
					$aValues[$aMatch[1]] = $mVal;
				}
				else {
					$aValues[] = $mVal;
				}
			}

			// Return finalized value list
			return $aValues;
		}

		// Normal unquoted value
		return $this->parseScalar($sValue, 0);
	}

	/**
	 * Parser - Return RHS block of content matching an open/close token pair
	 *
	 * @param string $sRegexKey
	 * @param string $sCurrentContent
	 * @return (string|false)
	 */
	protected function parseBlock($sRegexKey, $sCurrentContent)
	{
		foreach ($this->aRegex[$sRegexKey] as $aBlock) {
			if (!preg_match($aBlock[0], $sCurrentContent, $aMatches)) {
				continue;
			}

			// Add content from opening line
			$sBlockContent = $aMatches[1];

			// Opening line is also closing line
			if (preg_match($aBlock[1], $sBlockContent, $aMatches)) {
				// Return only content between open and close tokens
				return $aMatches[1];
			}

			// Consume lines until closing line is found
			while ($this->aLines) {
				// Get next line
				$sCurrentContent = $this->sCurrentLine = array_shift($this->aLines);

				// Closing line found
				if (preg_match($aBlock[1], $sCurrentContent, $aMatches)) {
					// Return content up to close token
					return $sBlockContent . "\x0a" . $aMatches[1];
				}

				// Add entire line to content
				$sBlockContent .= "\x0a" . $sCurrentContent;
			}

			// We ran out of content, block is unclosed
			return $sBlockContent;
		}
		return false;
	}

	/**
	 * Parser - Convert RHS text to appropriate PHP value
	 *
	 * @param string $sValue
	 * @param int $iMode 0: plain text, 1: single-quote, 2: double-quote
	 * @return mixed parsed value
	 */
	protected function parseScalar($sValue, $iMode)
	{
		// Handle quoted string
		if ($iMode == 2) {
			return stripcslashes($sValue);
		}
		if ($iMode == 1) {
			return str_replace('\\\'', '\'', $sValue);
		}

		// Handle numeric value
		if (is_numeric($sValue)) {
			if (strpos($sValue, '.') !== false) {
				return floatval($sValue);
			}
			return intval($sValue, 0);
		}

		// Handle special named values
		$sTest = strtoupper($sValue);
		if (array_key_exists($sTest, $this->aOptions['special_values'])) {
			return $this->aOptions['special_values'][$sTest];
		}

		// Normal string, return value unchanged
		return $sValue;
	}

	/**
	 * Store parsed INI data, heirarchical mode
	 *
	 * @internal
	 * @param string $sSection
	 * @param string $sKey
	 * @param mixed $mValue
	 * @return void
	 */
	public function _storeHierarchy($sSection, $sKey, $mValue)
	{
		// Split key into hierarchy
		$aParts = preg_split($this->aRegex['hierarchy_separator'], $sKey);

		// Prepend section hierarchy
		if ($sSection !== null && $sSection !== '') {
			$aParts = array_merge(preg_split($this->aRegex['hierarchy_separator'], $sSection), $aParts);
		}

		// Locate target array for this key
		$aTarget =& $this->aResultData;
		while (count($aParts) > 1) {
			$sPart = array_shift($aParts);

			// Create hierarchy level if it doesn't exist
			if (!isset($aTarget[$sPart])) {
				$aTarget[$sPart] = array();
			}

			// Convert non-array to array
			elseif (!is_array($aTarget[$sPart])) {
				$aTarget[$sPart] = array( $aTarget[$sPart] );
			}

			// Descend to next hierarchy level
			$aTarget =& $aTarget[$sPart];
		}
		$sName = $aParts[0];

		// Determine add mode: set, append or merge
		$sMode = 'set';
		if ($sName === '') {
			// Empty name is equivalent to append
			$sMode = 'a';
			/*
				[2016-11 kp] this:
					[a]
					=1
					=2
				is equivalent to this:
					a[]=1
					a[]=2
			*/
		}
		elseif (substr($sName, -2) == '[]') {
			// Append: "a[]=1"
			$sName = substr($sName, 0, -2);
			$sMode = 'a';
		}
		elseif (substr($sName, -1) == '+') {
			// Merge: "a+={1 2 3}"
			$sName = substr($sName, 0, -1);
			$sMode = 'm';

			// Merge of a simple value is just an append
			if (!is_array($mValue))
				$sMode = 'a';
		}

		// Add value
		switch ($sMode) {
			// Append
			case 'a':
				// Empty name append: append to parent array
				if ($sName == '') {
					/*
						[2016-11 kp] this:
							[a]
							[]=1
							[]=2
						is equivalent to this:
							a[]=1
							a[]=2
					*/
					$aTarget[] = $mValue;
					break;
				}

				// Append to empty
				if (!isset($aTarget[$sName])) {
					$aTarget[$sName] = array( $mValue );
					break;
				}

				// Append to scalar (convert to array)
				if (!is_array($aTarget[$sName])) {
					$aTarget[$sName] = array( $aTarget[$sName], $mValue );
					break;
				}

				// Append to array
				$aTarget[$sName][] = $mValue;
				break;

			// Merge
			case 'm':
				// Empty name merge: merge to parent array
				if ($sName == '') {
					/*
						[2016-11 kp] this:
							[a]
							+=[ 1 2 3 ]
							+=[ 4 5 6 ]
						is equivalent to this:
							a+=[ 1 2 3 ]
							a+=[ 4 5 6 ]
					*/
					$aTarget = array_merge($aTarget, $mValue);
					break;
				}

				// Merge to empty key
				if (!isset($aTarget[$sName])) {
					$aTarget[$sName] = $mValue;
					break;
				}

				// Merge to scalar (convert to array)
				if (!is_array($aTarget[$sName])) {
					$aTarget[$sName] = array_merge(
						array( $aTarget[$sName] ),
						$mValue
					);
					break;
				}

				// Merge to array
				$aTarget[$sName] = array_merge($aTarget[$sName], $mValue);
				break;

			// Set
			default:
				$aTarget[$sName] = $mValue;
				break;
		}
	}

	/**
	 * Store parsed INI data, non-heirarchical mode
	 *
	 * @internal
	 * @param string $sSection
	 * @param string $sKey
	 * @param mixed $mValue
	 * @return void
	 */
	public function _storeBasic($sSection, $sKey, $mValue)
	{
		if ($sSection === null || $sSection === '') {
			$this->aResultData[$sKey] = $mValue;
			return;
		}
		if (!isset($this->aResultData[$sSection]))
			$this->aResultData[$sSection] = array();
		$this->aResultData[$sSection][$sKey] = $mValue;
	}

	/**
	 * Prepare regular expressions
	 *
	 * @return void
	 */
	protected function prepRegex()
	{
		if ($this->aRegex) {
			return;
		}
		
		$this->aRegex = array();
		$this->aRegex['element_separator'] = $this->getTokenRegex(
			$this->aOptions['element_separator'],
			'@@TOKEN@@'
		);
		$this->aRegex['pair'] = $this->getTokenRegex(
			$this->aOptions['pair_separator'],
			'^\s*(.*?)\s*(?:@@TOKEN@@\s*(.*?)\s*)?$'
		);
		$this->aRegex['empty_line'] = '/^\s*$/';
		$this->aRegex['comment_line'] =
			$this->aOptions['comments']
			? $this->getTokenRegex(
				$this->aOptions['comment_start'],
				'^\s*@@TOKEN@@'
			)
			: null
		;
		$this->aRegex['multiline_comment'] =
			$this->aOptions['multiline_comments']
			? $this->getBlockRegex(
				$this->aOptions['multiline_comment_open'],
				$this->aOptions['multiline_comment_close']
			)
			: array()
		;
		$this->aRegex['hierarchy_separator'] =
			$this->aOptions['hierarchy']
			? $this->getTokenRegex(
				$this->aOptions['hierarchy_separator'],
				'@@TOKEN@@'
			)
			: array()
		;
		$this->aRegex['list_value'] =
			$this->aOptions['lists']
			? $this->getBlockRegex(
				$this->aOptions['list_open'],
				$this->aOptions['list_close']
			)
			: array()
		;
	}

	/**
	 * Return array of regexes matching paired open/close tokens
	 *
	 * @param (string|array) $mOpenTokens
	 * @param (string|array) $mCloseTokens
	 * @return array
	 */
	protected function getBlockRegex($mOpenTokens, $mCloseTokens)
	{
		if (!is_array($mOpenTokens)) {
			$mOpenTokens = array( $mOpenTokens );
		}
		if (!is_array($mCloseTokens)) {
			$mCloseTokens = array( $mCloseTokens );
		}
		$iCount = count($mOpenTokens);
		if ($iCount != count($mCloseTokens)) {
			return array();
		}
		$aRegexList = array();
		for ($iIndex = 0; $iIndex < $iCount; $iIndex++) {
			$aRegexList[] = array(
				$this->getTokenRegex($mOpenTokens[$iIndex], '^\s*@@TOKEN@@(.*)$'),
				$this->getTokenRegex($mCloseTokens[$iIndex], '^(.*)@@TOKEN@@\s*$'),
			);
		}
		return $aRegexList;
	}

	/**
	 * Return regex matching token(s) in a line
	 *
	 * @param (string|array) $mToken
	 * @param string $sRegexPattern regex with `"@@TOKEN@@"` placeholder
	 * @return string regex
	 */
	protected function getTokenRegex($mToken, $sRegexPattern)
	{
		// Multiple tokens
		if (is_array($mToken) && $mToken) {
			// Sort by length, longest to shortest
			usort($mToken, array( $this, '_sort_strlen'));

			// Quote regex special chars
			foreach ($mToken as &$sVal) {
				$sVal = preg_quote($sVal, '/');
			}
			unset($sVal);

			// Assemble regex alternatives list
			$sToken = '(?:' . implode('|', $mToken) . ')';
		}

		// Single token
		elseif (is_string($mToken)) {
			// Quote regex special chars
			$sToken = preg_quote($mToken, '/');
		}

		// Empty/invalid token
		else {
			return null;
		}

		// Return finalized regex
		return '/' . str_replace('@@TOKEN@@', $sToken, $sRegexPattern) . '/';
	}

	/**
	 * Token sort routine
	 *
	 * @internal
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	public function _sort_strlen($a, $b)
	{
		return strlen($b) - strlen($a);
	}

}
