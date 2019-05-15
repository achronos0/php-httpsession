<?php
/**
 * \Useful\Logger\Writer\Display class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Logger\Writer;

use Useful\Logger\AbstractWriter;

/**
 * Output messages immediately to console or page
 *
 * Writer config settings:
 *     (bool|null) `html` - TRUE to output messages with HTML formatting; FALSE to output plain-text messages; NULL to use plain text for command-line, HTML otherwise.
 *
 * @uses \Useful\Logger
 * @uses \Useful\Logger\AbstractWriter
 */
class Display extends AbstractWriter
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
		$aFormat['html'] = isset($aWriterConfig['html']) ? $aWriterConfig['html'] : (PHP_SAPI != 'cli');
		echo $this->oLogger->formatMessage($aMessage, $aFormat);
		flush();
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
