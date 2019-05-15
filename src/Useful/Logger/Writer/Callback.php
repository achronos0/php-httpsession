<?php
/**
 * \Useful\Logger\Writer\Callback class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Logger\Writer;

use Useful\Logger\AbstractQueuedWriter;

/**
 * Send messages to externally provided callback function
 *
 * Writer settings:
 *     * callable `call` - Function to call to process messages. Required.
 *         Signature: function callback($sQueue, $aWriterConfig, $aMessageList): void
 *         Arguments:
 *             * (string|null) $sQueue log name, or null when writer config queue=single
 *             * array $aWriterConfig writer config settings
 *             * array $aMessageList list of messages, each is message data as returned by {@link Logger::write_prepMessage}
 *     * string `queue` - Controls how internal queueing system operates.
 *         Default for this writer is `false`, which disables queueing.
 *     * int `max_messages` - Maximum number of messages to store. Default is 100.
 *     * bool `autoflush` - TRUE to automatically flush (process and dequeue) messages when queue is full; FALSE means excess messages emit a warning message then are discarded.
 *         Default for this writer is FALSE, messages over limit are discarded.
 * See {@link \Useful\Logger\AbstractQueuedWriter} for more details on `queue`, `max_messages` and `autoflush` queueing options.
 *
 * @uses \Useful\Logger\AbstractQueuedWriter
 */
class Callback extends AbstractQueuedWriter
{
	//////////////////////////////
	// Implement AbstractQueuedWriter

	/**
	 * Return built-in defaults for writer config settings
	 *
	 * @api
	 * @return array config settings map
	 */
	public function getDefaultConfig() {
		return array(
			'call' => null,
			'queue' => 'single',
			'max_messages' => 100,
			'autoflush' => false,
		);
	}

	/**
	 * Send queued messages to external callback function
	 *
	 * @api
	 * @param (string|null) $sQueue log name, or null when writer config queue=single
	 * @param array $aMessageList list of messages, each is message data as returned by {@link Logger::write_prepMessage}
	 * @return void
	 */
	protected function processMessages($sQueue, $aMessageList)
	{
		call_user_func($this->aConfig['call'], $sQueue, $this->aConfig, $aMessageList);
	}
}
