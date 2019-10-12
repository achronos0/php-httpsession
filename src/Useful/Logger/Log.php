<?php
/**
 * \Useful\Logger\Log class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Logger;

use Psr\Log\InvalidArgumentException, Psr\Log\LoggerInterface, Useful\Exception, Useful\Logger;

/**
 * Send messages to a named log receiver
 *
 * @uses \Useful\Exception
 * @uses \Useful\Logger
 * @uses \Psr\Log\LoggerInterface
 * @uses \Psr\Log\InvalidArgumentException
 */
class Log implements LoggerInterface
{
	//////////////////////////////
	// Convenience writer methods, and implement \Psr\Log\LoggerInterface

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
		$this->log('emergency', $sMessage, $aContext);
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
		$this->write('alert', $sMessage, $aContext);
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
		$this->write('critical', $sMessage, $aContext);
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
		$this->write('error', $sMessage, $aContext);
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
		$this->write('warning', $sMessage, $aContext);
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
		$this->write('notice', $sMessage, $aContext);
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
		$this->write('info', $sMessage, $aContext);
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
		$this->write('detail', $sMessage, $aContext);
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
		$this->write('debug', $sMessage, $aContext);
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
		$this->write('debug2', $sMessage, $aContext);
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
		$this->write('debug3', $sMessage, $aContext);
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
    public function log($sLevel, $sMessage, $aContext = array())
    {
    	try {
			$this->write($sLevel, $sMessage, $aContext);
		}
		catch (Exception $e) {
			if ($e->getCode() == 1) {
				throw new InvalidArgumentException($e->getMessage());
			}
			throw $e;
		}
    }


	//////////////////////////////
	// Public

	/**
	 * Write a message to this log
	 *
	 * See Logger docs for more information.
	 *
	 * @param (string|int) $mLevel a defined message level, as (string) label or (int) number
	 * @param string $sMessage message to write
	 * @param mixed $mData additional data to store with message; usually an array
	 * @param float $fStartTimer calculate duration using this time offset
	 * @return void
	*/
	public function write($mLevel, $sMessage, $mData = null, $fStartTimer = null)
	{
		$this->oLogger->write($this->sLog, $mLevel, $sMessage, $mData, $fStartTimer);
	}

	/**
	 * Get current time as floating point number
	 *
	 * Use this value as the $fStartTimer argument to {@link write} and others.
	 *
	 * @api
	 * @return float timer
	 */
	public function getTimer()
	{
		return $this->oLogger-getTimer();
	}

	/**
	 * Get config settings for this log
	 *
	 * @return array config settings
	 */
	public function getConfig()
	{
		return $this->oLogger->getLogConfig($this->sLog);
	}

	/**
	 * Update config settings for this log
	 *
	 * @param array $aConfig config settings
	 * @return void
	 */
	public function setConfig($aConfig)
	{
		return $this->oLogger->setLogConfig($this->sLog, $aConfig);
	}

	/**
	 * Get current message level mask for this log
	 *
	 * @return int level mask
	 */
	public function getLevelMask()
	{
		return $this->oLogger->getLogLevelMask($this->sLog);
	}

	/**
	 * Get current message level mask for this log
	 *
	 * @param (int|string) $mMask level mask
	 * @return void
	 */
	public function setLevelMask($mMask)
	{
		$this->oLogger->setLogLevelMask($this->sLog, $mMask);
	}

	/**
	 * Get owner logging system
	 *
	 * @api
	 * @return \Useful\Logger
	 */
	public function getLogger()
	{
		return $this->oLogger;
	}

	/**
	 * Get log name
	 *
	 * @api
	 * @return string
	 */
	public function getLogName()
	{
		return $this->sLog;
	}


	//////////////////////////////
	// Internal

	protected $oLogger;
	protected $sLog;

	/**
	 * Create new named log receiver
	 *
	 * @internal
	 * @param \Useful\Logger $oLogger owner logging system
	 * @param string $sLog log name
	 */
	public function __construct($oLogger, $sLog)
	{
		$this->oLogger = $oLogger;
		$this->sLog = $sLog;
	}
}
