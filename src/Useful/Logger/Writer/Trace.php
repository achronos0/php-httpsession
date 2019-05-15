<?php
/**
 * \Useful\Logger\Writer\Trace class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Logger\Writer;

use Useful\Logger\AbstractQueuedWriter;

/**
 * Display log messages in an HTML comment block
 *
 * Writer settings:
 *     int `max_messages` - Maximum number of messages to store. Default is 1000.
 *     bool `autoflush` - TRUE to automatically flush (process and dequeue) messages when queue is full; FALSE means excess messages cause a warning then are discarded. Default is FALSE.
 *
 * @uses \Useful\Logger
 * @uses \Useful\Logger\AbstractQueuedWriter
 */
class Trace extends AbstractQueuedWriter
{
	//////////////////////////////
	// Implement AbstractQueuedWriter

	/**
	 * Return built-in defaults for writer config settings
	 *
	 * @api
	 * @return array config settings map
	 */
	public function getDefaultConfig()
	{
		return array(
			'format' => array(
				'html' => false,
			),
			'queue' => 'single',
			'max_messages' => 1000,
			'autoflush' => false,
		);
	}
	
	/**
	 * Display queued messages in an HTML (also SGML, XML) comment
	 *
	 * @api
	 * @param (string|null) $sQueue log name, or null when writer config queue=single
	 * @param array $aMessageList list of messages, each is message data as returned by {@link Logger::write_prepMessage}
	 * @return void
	 */
	protected function processMessages($sQueue, $aMessageList)
	{
		echo PHP_EOL . '<!--' . PHP_EOL;
		foreach ($aMessageList as $aMessage) {
			echo $this->oLogger->formatMessage($aMessage, $this->aConfig['format']);
		}
		echo '-->' . PHP_EOL;
	}
}
