<?php
/**
 * \Useful\Logger\LogFactory class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Logger;

use Useful\Logger;

/**
 * Create named log receivers
 *
 * @uses \Useful\Logger
 */
class LogFactory
{
	//////////////////////////////
	// Public

	/**
	 * Get a PSR-3-compliant object for writing messages to a named log
	 *
	 * Log objects are a convenience wrapper around {@link write}.
	 *
	 * @api
	 * @param string $sLog log name
	 * @return \Useful\Logger\Log
	 */
	public function getLog($sLog)
	{
		return $this->getLogger()->getLog($sLog);
	}

	/**
	 * Get a PSR-3-compliant object for writing messages to a named log
	 *
	 * Log objects are a convenience wrapper around {@link write}.
	 *
	 * This method is similar to {@link getLog} except it will not reuse a previously cached log object.
	 * This is useful for writing to the same log from multiple places with different message formats (see {@link \Useful\Logger\Log::setMessageFormat}).
	 *
	 * @api
	 * @param string $sLog log name
	 * @return \Useful\Logger\Log
	 */
	public function createNewLog($sLog)
	{
		return $this->getLogger()->createNewLog($sLog);
	}

	/**
	 * Get logging system settings
	 *
	 * @api
	 * @return array config info
	 */
	public function getLoggerConfig()
	{
		$this->getLogger()->getConfig();
	}

	/**
	 * Update logging system settings
	 *
	 * See {@link \Useful\Logger::setConfig} for info on settings
	 *
	 * @api
	 * @param array $aConfig new settings
	 * @return void
	 */
	public function setLoggerConfig($aConfig)
	{
		$this->getLogger()->setConfig($aConfig);
	}

	/**
	 * Return logging system used by this factory
	 *
	 * If a logger has been explicitly set using {@link setLogger}, that instance is returned,
	 *
	 * If no logger has been set, returns the default logger, i.e. same as `\Useful\Logger::getLogger()`
	 *
	 * @api
	 * @return \Useful\Logger logger instance
	 */
	public function getLogger()
	{
		if (!$this->oLogger) {
			$this->oLogger = Logger::getLogger();
		}
		return $this->oLogger;
	}

	/**
	 * Set the logging system to be used by this factory
	 *
	 * @api
	 * @param \Useful\Logger $oLogger logger instance
	 * @return void
	 */
	public function setLogger($oLogger)
	{
		$this->oLogger = $oLogger;
	}


	//////////////////////////////
	// Internal

	protected $oLogger;
}
