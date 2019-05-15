<?php
/**
 * \Useful\Logger\AbstractWriter class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Logger;

use Useful\Logger;

/**
 * Process log messages generated via {@link Logger}
 *
 * All logger writers must extend AbstractWriter.
 *
 * @internal
 * @uses \Useful\Logger
 */
abstract class AbstractWriter
{
	//////////////////////////////
	// Abstract

	/**
	 * Receive a message from Logger
	 *
	 * @api
	 * @param array $aWriterConfig writer config settings as returned by {@link Logger::getWriterConfig}
	 * @param array $aLogConfig log config settings as returned by {@link Logger::getLogConfig}
	 * @param array $aMessage message data as returned by {@link Logger::write_prepMessage}
	 * @return void
	 */
	abstract public function commit($aWriterConfig, $aLogConfig, $aMessage);
	
	/**
	 * Finalize processing of pending messages
	 *
	 * @api
	 * @return void
	 */
	abstract public function flush();


	//////////////////////////////
	// Public

	/**
	 * Get Logger instance that this writer is attached to
	 *
	 * @api
	 * @return \Useful\Logger
	 */
	public function getLogger()
	{
		return $this->oLogger;
	}

	/**
	 * Get writer name under which this writer is registered in Logger instance
	 *
	 * @api
	 * @return string
	 */
	public function getWriterName()
	{
		return $this->sWriter;
	}


	//////////////////////////////
	// Internal

	protected $oLogger;
	protected $sWriter;

	/**
	 * Construct new writer
	 *
	 * @internal
	 * @param \Useful\Logger $oLogger
	 * @param string $sWriter name
	 */
	public function __construct($oLogger = null, $sWriter = null)
	{
		$this->oLogger = $oLogger;
		$this->sWriter = $sWriter;
	}

	/**
	 * Change Logger instance that this writer is attached to
	 *
	 * Note this is only intended to be called internally by {@link Logger} itself.
	 *
	 * @internal
	 * @param \Useful\Logger $oLogger
	 * @return void
	 */
	public function setLogger($oLogger)
	{
		$this->oLogger = $oLogger;
	}

	/**
	 * Change writer name under which this writer is registered in Logger instance
	 *
	 * Note this is only intended to be called internally by {@link Logger} itself.
	 *
	 * @internal
	 * @param string $sWriter
	 * @return void
	 */
	public function setWriterName($sWriter)
	{
		$this->sWriter = $sWriter;
	}
}
