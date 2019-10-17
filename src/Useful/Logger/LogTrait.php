<?php
/**
 * \Useful\Logger\LogTrait class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Logger;

use Psr\Log\LoggerTrait, Psr\Log\LogLevel, Useful\Exception, Useful\Logger\Log;

/**
 * Add convenient access to logging methods from within any class instance
 *
 * @uses \Useful\Exception
 * @uses \Useful\Logger\Log
 * @uses \Psr\Log\LoggerTrait
 * @uses \Psr\Log\LogLevel
 */
trait LogTrait
{
	use LoggerTrait;

	//////////////////////////////
	// Convenience writer methods, and implement \Psr\Log\LoggerTrait

	/**
	 * Log a message at named severity level
	 *
	 * System is unusable.
	 *
	 * @param string $sMessage message to write
	 * @param array $aContext additional data to store with message
	 * @param float $fStartTimer calculate duration using this time offset
	 * @return void
	 */
	public function emergency($sMessage, array $aContext = array(), $fStartTimer = null)
	{
		if (!$this->oUsefulLog) {
			throw new Exception('Must call setLog() before writing log messages');
		}
		$this->oUsefulLog->write(LogLevel::EMERGENCY, $sMessage, $aContext, $fStartTimer);
	}

	/**
	 * Log a message at named severity level
	 *
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.
	 *
	 * @param string $sMessage message to write
	 * @param array $aContext additional data to store with message
	 * @param float $fStartTimer calculate duration using this time offset
	 * @return void
	 */
	public function alert($sMessage, array $aContext = array(), $fStartTimer = null)
	{
		if (!$this->oUsefulLog) {
			throw new Exception('Must call setLog() before writing log messages');
		}
		$this->oUsefulLog->write(LogLevel::ALERT, $sMessage, $aContext, $fStartTimer);
	}

	/**
	 * Log a message at named severity level
	 *
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $sMessage message to write
	 * @param array $aContext additional data to store with message
	 * @param float $fStartTimer calculate duration using this time offset
	 * @return void
	 */
	public function critical($sMessage, array $aContext = array(), $fStartTimer = null)
	{
		if (!$this->oUsefulLog) {
			throw new Exception('Must call setLog() before writing log messages');
		}
		$this->oUsefulLog->write(LogLevel::CRITICAL, $sMessage, $aContext, $fStartTimer);
	}

	/**
	 * Log a message at named severity level
	 *
	 * Runtime errors that do not require immediate action but should typically be logged and monitored.
	 *
	 * @param string $sMessage message to write
	 * @param array $aContext additional data to store with message
	 * @param float $fStartTimer calculate duration using this time offset
	 * @return void
	 */
	public function error($sMessage, array $aContext = array(), $fStartTimer = null)
	{
		if (!$this->oUsefulLog) {
			throw new Exception('Must call setLog() before writing log messages');
		}
		$this->oUsefulLog->write(LogLevel::ERROR, $sMessage, $aContext, $fStartTimer);
	}

	/**
	 * Log a message at named severity level
	 *
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
	 *
	 * @param string $sMessage message to write
	 * @param array $aContext additional data to store with message
	 * @param float $fStartTimer calculate duration using this time offset
	 * @return void
	 */
	public function warning($sMessage, array $aContext = array(), $fStartTimer = null)
	{
		if (!$this->oUsefulLog) {
			throw new Exception('Must call setLog() before writing log messages');
		}
		$this->oUsefulLog->write(LogLevel::WARNING, $sMessage, $aContext, $fStartTimer);
	}

	/**
	 * Log a message at named severity level
	 *
	 * Normal but significant events.
	 *
	 * @param string $sMessage message to write
	 * @param array $aContext additional data to store with message
	 * @param float $fStartTimer calculate duration using this time offset
	 * @return void
	 */
	public function notice($sMessage, array $aContext = array(), $fStartTimer = null)
	{
		if (!$this->oUsefulLog) {
			throw new Exception('Must call setLog() before writing log messages');
		}
		$this->oUsefulLog->write(LogLevel::NOTICE, $sMessage, $aContext, $fStartTimer);
	}

	/**
	 * Log a message at named severity level
	 *
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $sMessage message to write
	 * @param array $aContext additional data to store with message
	 * @param float $fStartTimer calculate duration using this time offset
	 * @return void
	 */
	public function info($sMessage, array $aContext = array(), $fStartTimer = null)
	{
		if (!$this->oUsefulLog) {
			throw new Exception('Must call setLog() before writing log messages');
		}
		$this->oUsefulLog->write(LogLevel::INFO, $sMessage, $aContext, $fStartTimer);
	}

	/**
	 * Log a message at named severity level
	 *
	 * Operational checkpoints.
	 *
	 * Example: Reading file, calling component.
	 *
	 * Note: this is not a standard PSR-3 logging level.
	 *
	 * @param string $sMessage message to write
	 * @param array $aContext additional data to store with message
	 * @param float $fStartTimer calculate duration using this time offset
	 * @return void
	 */
	public function detail($sMessage, array $aContext = array(), $fStartTimer = null)
	{
		if (!$this->oUsefulLog) {
			throw new Exception('Must call setLog() before writing log messages');
		}
		$this->oUsefulLog->write('detail', $sMessage, $aContext, $fStartTimer);
	}

	/**
	 * Log a message at named severity level
	 *
	 * Detailed debug information.
	 *
	 * @param string $sMessage message to write
	 * @param array $aContext additional data to store with message
	 * @param float $fStartTimer calculate duration using this time offset
	 * @return void
	 */
	public function debug($sMessage, array $aContext = array(), $fStartTimer = null)
	{
		if (!$this->oUsefulLog) {
			throw new Exception('Must call setLog() before writing log messages');
		}
		$this->oUsefulLog->write(LogLevel::DEBUG, $sMessage, $aContext, $fStartTimer);
	}

	/**
	 * Log a message at named severity level
	 *
	 * Very detailed debug information.
	 *
	 * Note: this is not a standard PSR-3 logging level.
	 *
	 * @param string $sMessage message to write
	 * @param array $aContext additional data to store with message
	 * @param float $fStartTimer calculate duration using this time offset
	 * @return void
	 */
	public function debug2($sMessage, array $aContext = array(), $fStartTimer = null)
	{
		if (!$this->oUsefulLog) {
			throw new Exception('Must call setLog() before writing log messages');
		}
		$this->oUsefulLog->write('debug2', $sMessage, $aContext, $fStartTimer);
	}

	/**
	 * Log a message at named severity level
	 *
	 * Extremely detailed debug information.
	 *
	 * Note: this is not a standard PSR-3 logging level.
	 *
	 * @param string $sMessage message to write
	 * @param array $aContext additional data to store with message
	 * @param float $fStartTimer calculate duration using this time offset
	 * @return void
	 */
	public function debug3($sMessage, array $aContext = array(), $fStartTimer = null)
	{
		if (!$this->oUsefulLog) {
			throw new Exception('Must call setLog() before writing log messages');
		}
		$this->oUsefulLog->write('debug3', $sMessage, $aContext, $fStartTimer);
	}

    /**
     * Logs message with an arbitrary severity level.
     *
     * @param string $sLevel severity level
     * @param string $sMessage message to write
     * @param array $aContext additional data to store with message
     * @return void
     * @throws \Psr\Log\InvalidArgumentException on invalid level
     */
    public function log($sLevel, $sMessage, array $aContext = array())
    {
		if (!$this->oUsefulLog) {
			throw new Exception('Must call setLog() before writing log messages');
		}
		$this->oUsefulLog->log($sMessage, $aContext, $fStartTimer);
    }


	//////////////////////////////
	// Public

	/**
	 * Return the log object used by this instance
	 *
	 * @api
	 * @return \Useful\Logger\Log $oLog
	 */
	public function getLog()
	{
		return $this->oUsefulLog;
	}

	/**
	 * Set the log object to be used by this instance
	 *
	 * @api
	 * @param \Useful\Logger\Log $oLog
	 * @return void
	 */
	public function setLog(\Useful\Logger\Log $oLog)
	{
		$this->oUsefulLog = $oLog;
	}


	//////////////////////////////
	// Internal

	protected $oUsefulLog = null;
}
