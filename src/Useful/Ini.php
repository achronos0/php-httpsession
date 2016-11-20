<?php
/**
* Parse and generate INI-format text data
*
* @link https://github.com/achronos0/useful
* @copyright Ky Patterson 2016, licensed under Apache 2.0
*/

namespace Useful;

class Ini
{
	//////////////////////////////
	// Public static

	/**
	* Read file into an array
	*
	* Read all data from an INI text file and return as a PHP array.
	*
	* @param string $sFilePath file path
	* @param array $this->aOptions configuration settings, see docs
	* @return array parsed data, or false on error
	* @throws \Useful\Exception
	*/
	public static function read($sFilePath, $aOptions = array())
	{
		// Check whether file exists
		$sFilePath = realpath($sFilePath);
		if (!file_exists($sFilePath))
			throw new Exception('Path does not exist');
		if (!is_file($sFilePath))
			throw new Exception('Path is not a file');
		if (!is_readable($sFilePath))
			throw new Exception('Path is not readable');

		// Read file
		$sContent = @file_get_contents($sFilePath);
		if ($sContent == false)
			throw new Exception('Cannot read from file');

		// Parse and return content
		$oParser = new static();
		return $oParser->runParser($sContent, $aOptions);
	}

	/**
	* Parse string into an array
	*
	* Convert INI text content into a PHP array.
	*
	* @param string $sContent delimited text content
	* @param array $this->aOptions configuration settings, see docs
	* @return array parsed data, or false on error
	*/
	public static function parse($sContent, $aOptions = array())
	{
		// Parse and return content
		$oParser = new static();
		return $oParser->runParser($sContent, $aOptions);
	}

	/**
	* Write file from an array
	*
	* Write an INI text file using data from a PHP array.
	*
	* If file exists it will be overwritten.
	*
	* @param string $sFilePath file path
	* @param array $aData data to write
	* @param array $this->aOptions configuration settings, see docs
	* @return void
	* @throws \Useful\Exception
	*/
	public static function write($sFilePath, $aData, $aOptions = array())
	{
		// Check whether file is writable
		if (file_exists($sFilePath)) {
			$sFilePath = realpath($sFilePath);
			if (!is_file($sFilePath))
				throw new Exception('Path is not a file');
			if (!is_writable($sFilePath))
				throw new Exception('Path is not writable');
		}
		elseif (!is_writeable(dirname($sFilePath)))
			throw new Exception('Path is not writable');

		// Generate content
		$oParser = new static();
		$sContent = $oParser->runGenerator($aData, $aOptions);

		// Write file
		if (file_put_contents($sFilePath, $sContent) === false)
			throw new Exception('Cannot write to file');
	}

	/**
	* Generate string from array
	*
	* Generate INI text content using data from a PHP array.
	*
	* @param array $aData data to write
	* @param array $this->aOptions configuration settings, see docs
	* @return string generated INI text
	*/
	public static function generate($aData, $aOptions = array())
	{
		// Generate and return content
		$oParser = new static();
		return $oParser->runGenerator($aData, $aOptions);
	}


	//////////////////////////////
	// Internal

	protected $aOptions; // both
	protected $aRegexes; // parser
	protected $aLines; // parser
	protected $sCurrentLine; // parser

	// Parse INI content
	protected function runParser($sContent, $aOptions)
	{
		// Set default options
		$this->aOptions = array_merge(
			array(
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
			),
			$aOptions
		);

		// Prepare regular expressions
		$this->aRegex = array();
		$this->aRegex['element_separator'] = $this->parser_getTokenRegex(
			$this->aOptions['element_separator'],
			'@@TOKEN@@'
		);
		$this->aRegex['pair'] = $this->parser_getTokenRegex(
			$this->aOptions['pair_separator'],
			'^\s*(.*?)\s*(?:@@TOKEN@@\s*(.*?)\s*)?$'
		);
		$this->aRegex['empty_line'] = '/^\s*$/';
		$this->aRegex['comment_line'] =
			$this->aOptions['comments']
			? static::parser_getTokenRegex($this->aOptions['comment_start'], '^\s*@@TOKEN@@')
			: null
		;
		$this->aRegex['multiline_comment'] =
			$this->aOptions['multiline_comments']
			? static::parser_getBlockRegex(
				$this->aOptions['multiline_comment_open'],
				$this->aOptions['multiline_comment_close']
			)
			: array()
		;
		$this->aRegex['hierarchy_separator'] =
			$this->aOptions['hierarchy']
			? static::parser_getTokenRegex(
				$this->aOptions['hierarchy_separator'],
				'@@TOKEN@@'
			)
			: array()
		;
		$this->aRegex['list_value'] =
			$this->aOptions['lists']
			? static::parser_getBlockRegex(
				$this->aOptions['list_open'],
				$this->aOptions['list_close']
			)
			: array()
		;

		// Prepare data handler
		if ($this->aOptions['data_handler_call']) {
			$aResultData = null;
			$xDataHandler = $this->aOptions['data_handler_call'];
		}
		elseif ($this->aOptions['hierarchy']) {
			$aResultData = array();
			$aHierarchyRegex = $this->aRegex['hierarchy_separator'];
			$xDataHandler =
				function($sSection, $sKey, $mValue) use ($aHierarchyRegex, &$aResultData)
				{
					// Split key into hierarchy
					$aParts = preg_split($aHierarchyRegex, $sKey);

					// Prepend section hierarchy
					if ($sSection !== null && $sSection !== '')
						$aParts = array_merge(preg_split($aHierarchyRegex, $sSection), $aParts);

					// Locate target array for this key
					$aTarget =& $aResultData;
					while (count($aParts) > 1) {
						$sPart = array_shift($aParts);

						// Create hierarchy level if it doesn't exist
						if (!isset($aTarget[$sPart]))
							$aTarget[$sPart] = array();

						// Convert non-array to array
						elseif (!is_array($aTarget[$sPart]))
							$aTarget[$sPart] = array( $aTarget[$sPart] );

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
			;
		}
		else {
			$aResultData = array();
			$xDataHandler =
				function($sSection, $sKey, $mValue) use (&$aResultData)
				{
					if ($sSection === null || $sSection === '') {
						$aResultData[$sKey] = $mValue;
						return;
					}
					if (!isset($aResultData[$sSection]))
						$aResultData[$sSection] = array();
					$aResultData[$sSection][$sKey] = $mValue;
				}
			;
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
			if ($this->parser_empty())
				continue;

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
			if (!preg_match($this->aRegex['pair'], $this->sCurrentLine, $aMatches))
				continue;
			$sKey = $aMatches[1];
			$mValue = isset($aMatches[2]) ? $aMatches[2] : null;

			// Ignore empty pair
			if ($sKey === '' && ($mValue === '' || $mValue === null))
				continue;

			// Parse value
			$mValue = $this->parser_rhs($mValue, $this->aLines, $this->aOptions);

			// Store key/value
			if (call_user_func($xDataHandler, $sCurrentSection, $sKey, $mValue) === false)
				break;
		}

		// Return results
		return $aResultData;
	}

	// Parser - Check for empty line or comment
	protected function parser_empty()
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
		if ($this->parser_block('multiline_comment', $this->sCurrentLine) !== false)
			return true;

		// Meaningful content
		return false;
	}

	// Parser - Convert RHS of INI pair to PHP value
	protected function parser_rhs($sValue)
	{
		// Empty value
		if ($sValue === '')
			return null;

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
				return $this->parser_scalar(
					substr($sValue, 1, -1),
					$sFirstChar == '"' ? 2 : 1,
					$this->aOptions
				);
			}
		}

		// List value
		$sListContent = $this->parser_block('list_value', $sValue);
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
					? $this->parser_scalar($aMatch[4], 0, $this->aOptions)
					: (
						isset($aMatch[3])
						? $this->parser_scalar($aMatch[3], 2, $this->aOptions)
						: $this->parser_scalar($aMatch[2], 1, $this->aOptions)
					)
				;
				if ($aMatch[1] != '')
					$aValues[$aMatch[1]] = $mVal;
				else
					$aValues[] = $mVal;
			}

			// Return finalized value list
			return $aValues;
		}

		// Normal unquoted value
		return $this->parser_scalar($sValue, 0, $this->aOptions);
	}

	// Parser - Return block of content matching an open/close token pair
	protected function parser_block($sRegexKey, $sCurrentContent)
	{
		foreach ($this->aRegex[$sRegexKey] as $aBlock) {
			if (!preg_match($aBlock[0], $sCurrentContent, $aMatches))
				continue;

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

	// Parser - Convert text to appropriate PHP value
	// mode: 0: plain text, 1: single-quote, 2: double-quote
	protected function parser_scalar($sValue, $iMode)
	{
		// Handle quoted string
		if ($iMode == 2)
			return stripcslashes($sValue);
		if ($iMode == 1)
			return str_replace('\\\'', '\'', $sValue);

		// Handle numeric value
		if (is_numeric($sValue)) {
			if (strpos($sValue, '.') !== false)
				return floatval($sValue);
			return intval($sValue, 0);
		}

		// Handle special named values
		$sTest = strtoupper($sValue);
		if (array_key_exists($sTest, $this->aOptions['special_values']))
			return $this->aOptions['special_values'][$sTest];

		// Normal string, return value unchanged
		return $sValue;
	}

	// Return array of regexes matching paired open/close tokens
	protected function parser_getBlockRegex($mOpenTokens, $mCloseTokens)
	{
		if (!is_array($mOpenTokens))
			$mOpenTokens = array( $mOpenTokens );
		if (!is_array($mCloseTokens))
			$mCloseTokens = array( $mCloseTokens );
		$iCount = count($mOpenTokens);
		if ($iCount != count($mCloseTokens))
			return array();
		$aRegexList = array();
		for ($iIndex = 0; $iIndex < $iCount; $iIndex++) {
			$aRegexList[] = array(
				$this->parser_getTokenRegex($mOpenTokens[$iIndex], '^\s*@@TOKEN@@(.*)$'),
				$this->parser_getTokenRegex($mCloseTokens[$iIndex], '^(.*)@@TOKEN@@\s*$'),
			);
		}
		return $aRegexList;
	}

	// Return regex matching token(s) in a line
	protected function parser_getTokenRegex($mToken, $sRegexPattern)
	{
		// Multiple tokens
		if (is_array($mToken) && $mToken) {
			// Sort by length, longest to shortest
			usort(
				$mToken,
				function ($a, $b)
				{
					return strlen($b) - strlen($a);
				}
			);

			// Quote regex special chars
			foreach ($mToken as &$sVal)
				$sVal = preg_quote($sVal, '/');
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

	// Generate INI content
	protected function runGenerator($aData, $aOptions)
	{
		// Set default options
		$this->aOptions = array_merge(
			array(
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
			),
			$aOptions
		);

		// Generate and return content
		return $this->generator_array($aData, '');
	}

	// Return INI text for associative array
	protected function generator_array($aData, $sKeyPrefix)
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
					. $this->generator_scalar($mValue, false, $this->aOptions)
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
			if ($this->aOptions['lists'] && $this->generator_isList($mValue)) {
				$aValues = array();
				foreach ($mValue as $mSubValue)
					$aValues[] = $this->generator_scalar($mSubValue, true, $this->aOptions);
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
			$sContent .= $this->generator_array($mValue, $sKey);
		}

		// Return content
		return $sContent;
	}

	// Return INI text for simple (scalar/null) value
	protected function generator_scalar($mValue, $bQuoteWhitespace)
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

	// Check whether array is a list (one-dimensional vector array composed of simple values)
	protected function generator_isList($aValue)
	{
		if (!count($aValue))
			return false;
		foreach ($aValue as $mKey => $mValue) {
			if (
				!is_numeric($mKey)
				|| (!is_scalar($mValue) && $mValue !== null)
			) {
				return false;
			}
		}
		return true;
	}
}

if (!class_exists('Useful\\Exception', false)) {
	class Exception extends \Exception {};
}
