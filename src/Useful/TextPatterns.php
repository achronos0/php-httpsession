<?php
/**
 * \Useful\TextPatterns class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful;

/**
 * Collection of string formatting utility functions
 *
 * @static
 */
class TextPatterns
{
	//////////////////////////////
	// Public static

	/**
	 * Return a variable dump string
	 *
	 * $sMode options:
	 *   `null` regular dump, similar to built-in `var_dump()` (although there are added bells and whistles around var_dump)
	 *   `"pretty"` dump arrays prettily, suitable for end-user viewing
	 *   `"hex"` show hex representation of strings, for binary data
	 *   `"php"` use PHP native function, no custom code
	 *
	 * @param mixed $mVar value to dump
	 * @param bool $bHtml true to format dump string for display as HTML, false to format for console/plain text
	 * @param (string|null) $sMode how to generate dump string, see above
	 * @param int $iMaxPrettyDepth for `prett` mode, max array depth for pretty-printing
	 * @return string representation of variable's contents
	 */
	public static function dump($mVar, $bHtml = false, $mMode = null, $iMaxPrettyDepth = 2)
	{
		static $iPrettyDumpCounter;
		static $iPrettyDepth;

		// Examine value and build dump string
		if ($mMode !== 'php') {
			// Handle custom object dump method
			if (is_object($mVar) && method_exists($mVar, 'dump')) {
				$sObjClass = 'object(' . get_class($mVar) . ')' . (method_exists($mVar, 'count') ? ('(' . $mVar->count() . ')') : '') . ' ';
				$mUseVar = $mVar->dump();
			}
			else {
				$sObjClass = '';
				$mUseVar = $mVar;
			}

			// Output array data nicely
			if ($mMode === 'pretty' && is_array($mUseVar) && $iPrettyDepth < $iMaxPrettyDepth) {
				if (!count($mUseVar)) {
					$sMessage = 'Empty';
				}
				else {
					if ($bHtml) {
						$sMessage = '<table border="0" cellpadding="3" cellspacing="0" align="left" style="margin-left: 20px; margin-right: 20px; float: none; clear: both; text-align: left; border: thin solid black; ">';
					}
					else {
						$sMessage = '';
						$iMaxKeyLength = 0;
						foreach (array_keys($mUseVar) as $sKey) {
							$iLen = strlen($sKey);
							if ($iLen > $iMaxKeyLength) {
								$iMaxKeyLength = $iLen;
							}
						}
					}
					foreach ($mUseVar as $sKey => $mValue) {
						$bDump = (
							(is_string($mValue) && strlen($mValue) < 1024 && strpos($mValue, PHP_EOL) === false)
							|| is_numeric($mValue)
						) ? false : true;
						if ($bHtml) {
							$sKeyContent = htmlspecialchars($sKey);
							if ($bDump) {
								$iPrettyDepth++;
								$sDumpContent = self::dump($mValue, true, 'pretty', $iMaxPrettyDepth);
								$iPrettyDepth--;
								if (strlen($sDumpContent) > 512) {
									$sLinkId = '__log_dump_link_' . (++$iPrettyDumpCounter);
									$sDataId = '__log_dump_data_' . $iPrettyDumpCounter;
									$sKeyContent .= '&nbsp;&nbsp;<a id="' . $sLinkId . '" href="#" onclick="var d = document.getElementById(\'' . $sDataId . '\'); if (d.style.display == \'none\') { this.innerHTML=\'[&ndash;]\'; d.style.display=\'block\'; } else { this.innerHTML=\'[+]\'; d.style.display=\'none\'; } return false; ">[&ndash;]</a>';
									$sDumpContent =
										'<div id="' . $sDataId . '" style="display: block; ">'
										. $sDumpContent
										. '</div>'
									;
								}
							}
							else {
								$sDumpContent = htmlspecialchars($mValue);
							}

							$sMessage .=
								'<tr><td align="left" valign="top">'
								. $sKeyContent
								. '</td><td align="left" valign="top">'
								. $sDumpContent
								. '</div></td></tr>'
							;
						}
						else {
							$sKeyContent = str_pad($sKey . ':', $iMaxKeyLength + 1);
							if ($bDump) {
								$iPrettyDepth++;
								$sDumpContent = PHP_EOL . self::dump($mValue, false, 'pretty', $iMaxPrettyDepth);
								$iPrettyDepth--;
							}
							else {
								$sDumpContent = $mValue;
							}
							$sMessage .= str_repeat('    ', $iPrettyDepth) . $sKeyContent . ' ' . $sDumpContent . PHP_EOL;
						}
					}
					if ($bHtml) {
						$sMessage .= '</table>';
					}
				}
				return $sMessage;
			}

			// Get description of value
			if ($mUseVar === null) {
				$sDump = $sObjClass . 'NULL';
			}
			elseif (is_scalar($mUseVar)) {
				if ($sObjClass) {
					if (is_bool($mUseVar)) {
						$sDump = $sObjClass . ($mUseVar ? 'bool(true)' : 'bool(false)');
					}
					elseif (is_string($mUseVar) && strpos($mUseVar, PHP_EOL) !== false) {
						$sDump = $sObjClass . str_replace(PHP_EOL, PHP_EOL . '  ', $mUseVar);
					}
					else {
						$sDump = $sObjClass . $mUseVar;
					}
				}
				else {
					if (is_bool($mUseVar)) {
						$sDump = $mUseVar ? 'bool(true)' : 'bool(false)';
					}
					elseif (is_int($mUseVar)) {
						$sDump = 'int(' . $mUseVar . ')';
					}
					elseif (is_float($mUseVar)) {
						$sDump = 'float(' . $mUseVar . ')';
					}
					elseif (is_string($mUseVar)) {
						$iLen = strlen($mUseVar);
						$sDump = 'string(' . $iLen . ')';
						if ($mMode === 'hex' && $iLen) {
							$iPos = 0;
							do {
								$sBuf = substr($mUseVar, $iPos, 16);
								$sHex = rtrim(str_pad(
									preg_replace('/([0-9A-F][0-9A-F])/', '$1 ', strtoupper(bin2hex($sBuf))),
									47,
									'.'
								));
								$sBuf = str_pad(preg_replace('/[\x00-\x1F]/', '^', $sBuf), 16, '.');
								$sDump .= PHP_EOL . '  ' . $sHex . '  ' . $sBuf;
								$iPos += 16;
							} while ($iPos < $iLen);
						}
						else {
							$sDump .= ' "' . $mUseVar . '"';
						}
					}
				}
			}
			elseif (is_array($mUseVar)) {
				$sTemp = '{';
				foreach ($mUseVar as $mKey => $mVal) {
					$sTemp .= PHP_EOL
						. '  [' . (is_numeric($mKey) ? $mKey : '"' . $mKey . '"') . '] => '
						. str_replace(PHP_EOL, PHP_EOL . '  ', self::dump($mVal, 0))
					;
				}
				$sTemp .= PHP_EOL . '}';
				$sDump = $sObjClass ?
					($sObjClass . $sTemp)
					: ('array(' . count($mUseVar) . ') ' . $sTemp)
				;
			}
			else {
				$mMode = 'php';
			}
		}

		// Capture value using PHP builtin var_dump
		if ($mMode === 'php') {
			ob_start();
			var_dump($mVar);
			$sDump = preg_replace('/\n+$/', '', ob_get_clean());
		}

		// Recursive call, no formatting
		if ($bHtml === 0) {
			return $sDump;
		}

		// Format for HTML output
		if ($bHtml) {
			$sDump = rtrim($sDump);
			return
				'<pre style="max-width: 800px; margin: 0; padding: 0; overflow: auto; float: none; clear: both; text-align: left; '
				. ((strpos($sDump, PHP_EOL) === false && strlen($sDump) > 100) ? 'height: 40px; ' : '')
				. '">'
				. strtr(
					$sDump,
					array(
						'&' => '&amp;',
						'"' => '&quot;',
						"'" => '&#039;',
						'<' => '&lt;',
						'>' => '&gt;'
					)
				)
				. '</pre>' . PHP_EOL;
		}

		// Format plain-text output
		return $sDump . PHP_EOL;
	}

	/**
	 * Return "human friendly" byte size
	 *
	 * @param int $iSize exact size in bytes
	 * @param int $iDecimals number of decimal places to return
	 * @return string formatted size e.g. "1.2G"
	 */
	public static function formatFileSize($iSize, $iDecimals = 1)
	{
		$aSizes = array('','K','M','G','T','P','E','Z','Y');
		$iFactor = floor((strlen($iSize) - 1) / 3);
		return sprintf("%.{$iDecimals}f", $iSize / pow(1024, $iFactor)) . @$aSizes[$iFactor];
	}

	/**
	 * Format a number for display
	 *
	 * Format specifier can be:
	 *   int number of decimal digits
	 *       Number will be formatted with thousands separators: "#,###,###.##"
	 *   string sprintf format string, or special format name
	 *   array detailed format; supported keys are:
	 *       int decimals - number of decimal digits
	 *       string dec_point c- haracter to use as decimal point, default "."
	 *       string thousands_sep - character to use as thousands separator, default ","
	 *       string prefix - prepend this to formatted number
	 *       string suffix - append this to formatted number
	 *       string sprintf - run sprintf using this format string (should have a single placeholder)
	 *       string special - name of special format function to use. Currently supports only: filesize
	 *
	 * Note:
	 * * to run sprintf without formatting number first, include sprintf and do not include decimals
	 * * if decimals and sprintf are both included, number is formatted first (including prefix and suffix) and then passed to sprintf; so sprintf placeholder must be "%s" for string
	 *
	 * @param (int|float|string) $mValue number to format
	 * @param (array|string|int) $mFormat format specifier
	 * @return string formatted number
	*/
	public static function formatNumber($mValue, $mFormat)
	{
		$aSpecialFormats = array( 'filesize' );

		// Get format array
		if (is_numeric($mFormat)) {
			$mFormat = array( 'decimals' => intval($mFormat) );
		}
		elseif (is_string($mFormat)) {
			$mFormat =
				in_array($mFormat, $aSpecialFormats)
				? array( 'special' => $mFormat )
				: array( 'sprintf' => $mFormat )
			;
		}
		elseif (!is_array($mFormat)) {
			$mFormat = array( 'decimals' => 2 );
		}

		// Format number using special format
		if (isset($mFormat['special'])) {
			switch ($mFormat['special']) {
				case 'filesize':
					$mValue = self::formatFileSize($mValue);
					break;
			}
		}
		// Format number using number_format
		elseif (isset($mFormat['decimals']) && is_numeric($mFormat['decimals'])) {
			// Get decimal point and thousands separator characters
			if (
				!isset($mFormat['dec_point'])
				|| $mFormat['dec_point'] === null
				|| $mFormat['dec_point'] === true
			) {
				$mFormat['dec_point'] = '.';
			}
			elseif ($mFormat['dec_point'] === false)
				$mFormat['dec_point'] = '';
			if (
				!isset($mFormat['thousands_sep'])
				|| $mFormat['dec_point'] === null
				|| $mFormat['thousands_sep'] === true
			) {
				$mFormat['thousands_sep'] = ',';
			}
			elseif ($mFormat['dec_point'] === false)
				$mFormat['thousands_sep'] = '';

			// Run number_format
			$mValue = number_format(
				$mValue,
				$mFormat['decimals'],
				$mFormat['dec_point'],
				$mFormat['thousands_sep']
			);
		}

		// Add additional text
		if (
			isset($mFormat['prefix'])
			&& $mFormat['prefix'] !== null
			&& !is_bool($mFormat['prefix'])
		) {
			$mValue = $mFormat['prefix'] . $mValue;
		}
		if (
			isset($mFormat['suffix'])
			&& $mFormat['suffix'] !== null
			&& !is_bool($mFormat['suffix'])
		) {
			$mValue .= $mFormat['suffix'];
		}

		// Format number using sprintf
		if (isset($mFormat['sprintf']) && is_string($mFormat['sprintf'])) {
			$mValue = sprintf($mFormat['sprintf'], $mValue);
		}

		// Return final value
		return $mValue;
	}

	/**
	 * Format multiline plain-text block for console display
	 *
	 * @param string $sText plain text block
	 * @param int $iMinLineLen don't make lines shorter than this number of characters, even if the console width is less
	 * @return string
	 */
	public static function formatPlainTextBlock($sText, $iMinLineLen)
	{

		$iMaxLineLen = max(intval(exec('tput cols')) - 1, $iMinLineLen);
		$sText = preg_replace('/^\s*\n/s', '', $sText);
		if (preg_match('/^[ \t]+/', $sText, $aMatches)) {
			$sText = preg_replace('/^' . $aMatches[0] . '/m', '', $sText);
		}
		if ($iMaxLineLen) {
			$aLines = explode("\n", $sText);
			foreach ($aLines as &$sLine) {
				if (strlen($sLine) >= $iMaxLineLen) {
					$aNewLines = array();
					if (preg_match('/^.*  /s', $sLine, $aMatches)) {
						$sSpacer = str_repeat(' ', strlen($aMatches[0]));
						if (strlen($sSpacer) > $iMaxLineLen - 20) {
							$sSpacer = substr($sSpacer, 0, $iMaxLineLen - 20);
						}
					}
					else {
						$sSpacer = '';
					}
					do {
						if (preg_match('/^.+[\s\-\.\?\)\]\},;!%]/s', substr($sLine, 0, $iMaxLineLen), $aMatches)) {
							$i = strlen($aMatches[0]);
							if ($i < $iMaxLineLen - 10) {
								$i = $iMaxLineLen - 1;
							}
						}
						else {
							$i = $iMaxLineLen - 1;
						}
						$aNewLines[] = substr($sLine, 0, $i);
						$sLine = $sSpacer . substr($sLine, $i);
						if (count($aNewLines) > 20) {
							break;
						}
					} while(strlen($sLine) >= $iMaxLineLen);
					$sLine = implode("\n", $aNewLines) . "\n" . $sLine;
				}
			}
			unset($sLine);
			$sText = implode("\n", $aLines);
		}
		return $sText;
	}

	/**
	 * Simple string variable interpolation
	 *
	 * Finds `{placeholder}` strings in message and replaces them with matching keys from data array.
	 *
	 * @param string $sTemplate string containing placeholder values
	 * @param array $aData array replacement values
	 * @return string
	 */
	public static function interpolate($sTemplate, $aData)
	{
		foreach ($aData as $sKey => $mValue) {
			if ($mValue === null) {
				$mValue = '';
			}
			if (is_scalar($mValue)) {
				$sTemplate = str_replace('{' . $sKey . '}', $mValue, $sTemplate);
			}
		}
		return $sTemplate;
	}
}
