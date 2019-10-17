<?php
/**
 * \Useful\Logger\Log class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Logger;

use Psr\Log\InvalidArgumentException, Psr\Log\LoggerInterface, Psr\Log\LogLevel, Useful\Exception, Useful\Logger;

/**
 * Send messages to a named log receiver
 *
 * @uses \Useful\Exception
 * @uses \Useful\Logger
 * @uses \Psr\Log\InvalidArgumentException
 * @uses \Psr\Log\LoggerInterface
 * @uses \Psr\Log\LogLevel
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
		$this->write(LogLevel::EMERGENCY, $sMessage, $aContext, $fStartTimer);
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
		$this->write(LogLevel::ALERT, $sMessage, $aContext, $fStartTimer);
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
		$this->write(LogLevel::CRITICAL, $sMessage, $aContext, $fStartTimer);
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
		$this->write(LogLevel::ERROR, $sMessage, $aContext, $fStartTimer);
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
		$this->write(LogLevel::WARNING, $sMessage, $aContext, $fStartTimer);
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
		$this->write(LogLevel::NOTICE, $sMessage, $aContext, $fStartTimer);
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
		$this->write(LogLevel::INFO, $sMessage, $aContext, $fStartTimer);
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
		$this->write('detail', $sMessage, $aContext, $fStartTimer);
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
		$this->write(LogLevel::DEBUG, $sMessage, $aContext, $fStartTimer);
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
		$this->write('debug2', $sMessage, $aContext, $fStartTimer);
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
		$this->write('debug3', $sMessage, $aContext, $fStartTimer);
	}

    /**
     * Logs message with an arbitrary severity level.
     *
     * This is an alias of {@link write} tweaked to be PSR-3 compliant.
     * If you are using Useful's logger on purpose you can just call {@link write}.
     *
     * @param string $sLevel severity level
     * @param string $sMessage message to write
     * @param array $aContext additional data to store with message
     * @return void
     * @throws \Psr\Log\InvalidArgumentException on invalid level
     */
    public function log($sLevel, $sMessage, array $aContext = array())
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
		if ($mData === null) {
			$mData = array();
		}
		elseif (!is_array($mData)) {
			$mData = array('data' => $mData);
		}
		if ($this->aAdditionalData) {
			$mData = array_merge($this->aAdditionalData, $mData);
		}
		if ($this->sMessageFormat) {
			$mData['msg_format'] = $this->sMessageFormat;
		}
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

	/**
	 * Set custom message format
	 *
	 * This format is used as a wrapper to add a prefix and/or suffix to the original log message.
	 *
	 * If the format includes token "{msg}", that token is where the original message is inserted.
	 * If the format does not include that token, the format is treated as a prefix; the original message is appended at the end of the format string.
	 *
	 * The format may also include tokens to be replaced with elements from context data
	 *
	 * @param string $sFormat message format
	 * @return void
	 */
	public function setMessageFormat($sFormat)
	{
		$this->sMessageFormat = $sFormat;
	}

	/**
	 * Get custom message format
	 *
	 * @return string message format
	 */
	public function getMessageFormat()
	{
		return $this->sMessageFormat;
	}

	/**
	 * Get custom message format as reference
	 *
	 * This allows for on-the-fly changes to the message format.
	 *
	 * @return &string message format as reference
	 */
	public function &getMessageFormatAsRef()
	{
		return $this->sMessageFormat;
	}

	/**
	 * Set additional data to be included in the context data of every message
	 *
	 * @param array $aData
	 * @return void
	 */
	public function setAdditionalData($aData)
	{
		$this->aAdditionalData = is_array($aData) ? $aData : array();
	}

	/**
	 * Get additional data
	 *
	 * @return array
	 */
	public function getAdditionalData($aData)
	{
		return $this->aAdditionalData;
	}

	/**
	 * Get additional data as reference
	 *
	 * @return &array
	 */
	public function &getAdditionalDataAsRef($aData)
	{
		return $this->aAdditionalData;
	}

	/**
	 * Create a duplicate of this object
	 *
	 * This is equivalent to calling `$oNewLog = clone $oOldLog`.
	 *
	 * @return \Useful\Logger\Log
	 */
	public function clone()
	{
		return clone $this;
	}

	
	//////////////////////////////
	// Internal

	protected $oLogger;
	protected $sLog;
	protected $sMessageFormat = '';
	protected $aAdditionalData = array();

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
