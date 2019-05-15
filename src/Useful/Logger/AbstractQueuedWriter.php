<?php
/**
 * \Useful\Logger\AbstractQueuedWriter class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Logger;

use Useful\Logger;

/**
 * Process log messages in batches via internal queueing system
 *
 * Writer settings:
 *     string `queue` - Controls how internal queueing system operates.
 *         Typically this is set in {@link getDefaultConfig} by implementing class.
 *         Supported values:
 *             * `"single"` maintain a single queue for all messages received; {@link processMessages} is called once for all messages, when flush() is called.
 *             * `"log"` maintain a separate queue for each log name; {@link processMessages} is called once per log queue, when flush() is called.
 *             * `null` do not queue messages, call {@link processMessages} immediately each time commit() is called.
 *     int `max_messages` - Maximum number of messages to store (if `queue` is `log` then maximum to store per log).
 *     bool `autoflush` - TRUE to automatically flush (process and dequeue) messages when queue is full; FALSE means excess messages emit a warning message then are discarded.
 *
 * @internal
 * @uses \Useful\Logger
 * @uses \Useful\Logger\AbstractWriter
 */
abstract class AbstractQueuedWriter extends AbstractWriter
{
	//////////////////////////////
	// Abstract and overridable

	/**
	 * Return built-in defaults for writer config settings
	 *
	 * Note, if you redefine this method it _must_ provide a default for `queue`, `max_messages` and `autoflush` settings.
	 *
	 * @api
	 * @return array config settings map
	 */
	protected function getDefaultConfig() {
		return array(
			'queue' => 'single',
			'max_messages' => 100,
			'autoflush' => false,
		);
	}

	/**
	 * Process messages
	 *
	 * @api
	 * @param (string|null) $sQueue log name, or null when writer config queue=single
	 * @param array $aMessageList list of messages, each is message data as returned by {@link Logger::write_prepMessage}
	 * @return void
	 */
	abstract protected function processMessages($sQueue, $aMessageList);


	//////////////////////////////
	// Implement AbstractWriter

	/**
	 * Receive a message from Logger
	 *
	 * @param array $aWriterConfig writer config settings as returned by {@link Logger::getWriterConfig}
	 * @param array $aLogConfig log config settings as returned by {@link Logger::getLogConfig}
	 * @param array $aMessage message data as returned by {@link Logger::write_prepMessage}
	 * @return void
	 */
	public function commit($aWriterConfig, $aLogConfig, $aMessage)
	{
		$this->aConfig = array_merge($this->getDefaultConfig(), $aWriterConfig);
		switch ($this->aConfig['queue']) {
			case 'single':
				$this->enqueueMessage('', $aMessage);
				return;
			case 'log':
				$this->enqueueMessage($aLogConfig['log'], $aMessage);
				return;
		}
		$this->processMessages($aLogConfig['log'], array($aMessage));
	}
	
	/**
	 * Finalize processing of pending messages
	 *
	 * @return void
	 */
	public function flush()
	{
		$aQueues = $this->dequeueMessages();
		if (!$aQueues) {
			return;
		}
		foreach ($aQueues as $sQueue => $aMessageList) {
			$this->processMessages($sQueue ? $sQueue : null, $aMessageList);
		}
	}


	//////////////////////////////
	// Internal

	protected $aQueues = array();
	protected $aConfig;

	/**
	 * Add message to internal writer queue
	 *
	 * Utility for writiers that delay processing messages until flush() is called
	 *
	 * @internal
	 * @param string $sQueue usually either empty string (if writer maintains a single queue), or log name (if writer maintains a separate queue per log)
	 * @param array $aMessage message data as returned by {@link Logger::write_prepMessage}
	 * @return void
	 */
	protected function enqueueMessage($sQueue, $aMessage)
	{
		$bFlush = false;
		if (isset($this->aQueues[$sQueue])) {
			$iCount = count($this->aQueues[$sQueue]);
			if ($iCount > $this->aConfig['max_messages']) {
				return;
			}
			if ($iCount == $this->aConfig['max_messages']) {
				if ($this->aConfig['autoflush']) {
					$bFlush = true;
				}
				else {
					$aMessage = $this->oLogger->write_prepMessage('logger', $this->oLogger->getLevelInt('warn'), 'Too many messages', NULL, NULL);
				}
			}
		}
		else {
			$this->aQueues[$sQueue] = array();
		}
		$this->aQueues[$sQueue][] = $aMessage;

		if ($bFlush) {
			$this->flush();
		}
	}

	/**
	 * Return messages from internal writer queue(s)
	 *
	 * @internal
	 * @return array all queues
	 */
	protected function dequeueMessages()
	{
		$aQueues = $this->aQueues;
		$this->aQueues = array();
		return $aQueues;
	}
}
