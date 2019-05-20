<?php
/**
 * \Useful\ArrayPatterns class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful;

/**
 * Collection of array utility functions
 *
 * @static
 */
class ArrayPatterns
{
	//////////////////////////////
	// Public static

	/**
	 * Merge multi-dimensional arrays
	 *
	 * Scalar values are replaced, and list arrays are replaced, other arrays are merged recursively.
	 *
	 * @param array $aOld
	 * @param array $aNew
	 * @return array merged
	 */
	public static function mergeConfig($aOld, $aNew)
	{
		foreach ($aNew as $sKey => $mValue) {
			if (
				is_string($sKey)
				&& isset($aOld[$sKey])
				&& is_array($mValue)
				&& is_array($aOld[$sKey])
				&& !self::isList($mValue)
				&& !self::isList($aOld[$sKey])
			) {
				$aOld[$sKey] = self::mergeConfig($aOld[$sKey], $mValue);
				continue;
			}
			$aOld[$sKey] = $mValue;
		}
		return $aOld;
	}

	/**
	 * Check whether array is a list (one-dimensional vector array composed of simple values)
	 *
	 * @param mixed $mValue value to test
	 * @return bool
	 */
	public static function isList($mValue)
	{
		if (!is_array($mValue)) {
			return false;
		}
		if (!count($mValue)) {
			return true;
		}
		$iCount = 0;
		foreach ($mValue as $mKey => $mKeyValue) {
			if (
				$mKey !== $iCount
				|| (
					!is_scalar($mKeyValue)
					&& $mKeyValue !== null
				)
			) {
				return false;
			}
			if (++$iCount >= 10) {
				break;
			}
		}
		return true;
	}
}
