<?php
/**
 * \Useful\ErrorPatterns class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful;

/**
 * Collection of PHP error-related utility functions
 *
 * @static
 */
class ErrorPatterns
{
	//////////////////////////////
	// Public static

	/**
	 * Get PHP call stack dump
	 *
	 * This is a friendly wrapper around {@link \debug_backtrace()}.
	 *
	 * If `$bDetail` is TRUE then each frame is returned as an array:
	 *     string `trace` text description of call frame
	 *     string `caller` file path and line number of calling code
	 *     string `called` called function class and method name
	 *     string `file` file path of calling code
	 *     string `line` line number of calling code
	 *     string `function` called function name
	 *     string `class` called class name
	 *     string `type` call type ( "=>", "::", null )
	 *
	 * @param bool $bDetail TRUE to return array of data for each frame; FALSE to return string description for each frame
	 * @param int $iSkip skip this many frames back in trace
	 * @param int $iLimit only return at most this many frames
	 * @return array stack trace, each element represents one frame in current call stack
	 */
	public static function getTrace($bDetail = false, $iSkip = 0, $iLimit = null)
	{
		if (defined('DEBUG_BACKTRACE_IGNORE_ARGS')) {
			$aRawTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		}
		else {
			$aRawTrace = debug_backtrace(false);
		}
		
		$aTrace = array();
		$iCount = 0;
		for ($i = 0; $i < count($aRawTrace); $i++) {
			if ($i < $iSkip) {
				continue;
			}
			if ($iLimit && $i >= $iSkip + $iLimit) {
				break;
			}

			$aFrame = array_merge(
				array(
					'trace' => '',
					'caller' => '',
					'called' => '',
					'file' => 'unknown',
					'line' => null,
					'function' => 'unknown',
					'class' => null,
					'type' => null,
				),
				$aRawTrace[$i]
			);

			$aFrame['caller'] = $aFrame['file'];
			if ($aFrame['line']) {
				$aFrame['caller'] .= ':' . $aFrame['line'];
			}

			$aFrame['called'] = '';
			if ($aFrame['class']) {
				$aFrame['called'] .= $aFrame['class'];
			}
			if ($aFrame['type']) {
				$aFrame['called'] .= $aFrame['type'];
			}
			$aFrame['called'] .= $aFrame['function'];

			$aFrame['trace'] = "$aFrame[called] called at $aFrame[caller]";

			$aTrace[] = $bDetail ? $aFrame : $aFrame['trace'];
		}
		return $aTrace;
	}

	/**
	 * Return text description of most recently raised PHP error
	 *
	 * NOTE requires PHP 5 >= 5.2.0
	 *
	 * @return (string|bool) description of error, or FALSE if there has not been any PHP error
	 * @throws \Useful\Exception if not supported
	 */
	public static function getLastPhpError()
	{
		if (!function_exists('error_get_last')) {
			throw new Exception('Not supported by this version of PHP');
		}
		$aError = error_get_last();
		return
			$aError
			? self::formatPhpError($aError['type'], $aError['message'], $aError['file'], $aError['line'])
			: false
		;
	}

	/**
	 * Return text description for a PHP error
	 *
	 * @param int $iType PHP error type (e.g. E_ERROR)
	 * @param string $sMessage Error message
	 * @param string $sFile File path
	 * @param int $iLine Line number
	 * @return string description of error
	 */
	public static function formatPhpError($iType, $sMessage, $sFile, $iLine)
	{
		return
			self::getErrorLabel($iType)
			. ': '
			. htmlspecialchars_decode($sMessage, ENT_QUOTES)
			. ' at '
			. $sFile
			. ' line '
			. $iLine
		;
	}

	/**
	 * Return human-readable description for a PHP error level integer
	 *
	 * @param int $iType PHP error type (e.g. E_ERROR)
	 * @return string error description, e.g. `"PHP runtime fatal error (E_ERROR)"`
	 */
	public static function getErrorLabel($iType)
	{
		static $aErrorLabelMap = array(
			E_ERROR              => 'PHP runtime fatal error (E_ERROR)',
			E_RECOVERABLE_ERROR  => 'PHP runtime recoverable error (E_RECOVERABLE_ERROR)',
			E_WARNING            => 'PHP runtime warning (E_WARNING)',
			E_NOTICE             => 'PHP runtime notice (E_NOTICE)',
			E_PARSE              => 'PHP parser fatal error (E_PARSE)',
			E_CORE_ERROR         => 'PHP core fatal error (E_CORE_ERROR)',
			E_CORE_WARNING       => 'PHP core warning (E_CORE_WARNING)',
			E_COMPILE_ERROR      => 'PHP compiler fatal error (E_COMPILE_ERROR)',
			E_COMPILE_WARNING    => 'PHP compiler warning (E_COMPILE_WARNING)',
			E_USER_ERROR         => 'PHP user-generated fatal error (E_USER_ERROR)',
			E_USER_WARNING       => 'PHP user-generated warning (E_USER_WARNING)',
			E_USER_NOTICE        => 'PHP user-generated notice (E_USER_NOTICE)',
			E_STRICT             => 'PHP bad code notice (E_STRICT)',
			E_DEPRECATED         => 'PHP deprecated notice (E_DEPRECATED)'
		);
		return
			isset($aErrorLabelMap[$iType])
			? $aErrorLabelMap[$iType]
			: ("unknown severity $iType")
		;
	}

	/**
	 * Return RFC 5424 severity level name for PHP error level integer
	 *
	 * @param int $iType PHP error type (e.g. E_ERROR)
	 * @return string severity level name, e.g. "critical"
	 */
	public static function getErrorSeverity($iType)
	{
		static $aErrorSeverityMap = array(
			E_ERROR              => 'critical',
			E_RECOVERABLE_ERROR  => 'error',
			E_WARNING            => 'error',
			E_NOTICE             => 'warning',
			E_PARSE              => 'critical',
			E_CORE_ERROR         => 'critical',
			E_CORE_WARNING       => 'error',
			E_COMPILE_ERROR      => 'critical',
			E_COMPILE_WARNING    => 'error',
			E_USER_ERROR         => 'error',
			E_USER_WARNING       => 'warning',
			E_USER_NOTICE        => 'notice',
			E_STRICT             => 'warning',
			E_DEPRECATED         => 'warning',
		);
		return isset($aErrorSeverityMap[$iType])
			? $aErrorSeverityMap[$iType]
			: 'error'
		;
	}
}
