<?php
/**
 * \Useful\Logger\Writer\Stderr class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Logger\Writer;

use Useful\Logger\AbstractWriter;

/**
 * Output messages immediately to console STDERR
 *
 * Writer config settings: (none)
 *
 * @uses \Useful\Logger
 * @uses \Useful\Logger\AbstractWriter
 */
class Stderr extends AbstractWriter
{
	//////////////////////////////
	// Implement AbstractWriter

	/**
	 * Display a Logger message immediately
	 *
	 * @api
	 * @param array $aWriterConfig writer config settings as returned by {@link Logger::getWriterConfig}
	 * @param array $aLogConfig log config settings as returned by {@link Logger::getLogConfig}
	 * @param array $aMessage message data as returned by {@link Logger::write_prepMessage}
	 * @return void
	 */
	public function commit($aWriterConfig, $aLogConfig, $aMessage)
	{
		$aFormat = isset($aWriterConfig['format']) ? $aWriterConfig['format'] : array();
		fputs(STDERR, $this->oLogger->formatMessage($aMessage, $aFormat));
	}
	
	/**
	 * No-op for this writer
	 *
	 * @api
	 * @return void
	 */
	public function flush() {
		/* do nothing */
	}
}
