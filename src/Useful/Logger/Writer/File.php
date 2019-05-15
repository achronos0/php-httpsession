<?php
/**
 * \Useful\Logger\Writer\File class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Logger\Writer;

use Useful\Csv, Useful\Logger\AbstractQueuedWriter, Useful\TextPatterns;

/**
 * Write messages to CSV file
 *
 * Writer settings:
 *     string `path` - Filepath to write log messages to.
 *         The provided path may contain placeholders:
 *             `"{log}"` - Replaced by the message's log name. If queue=single this is the string `"combined"`.
 *             `"{date}"` - Replaced by the current date, in YYYYMMDD format.
 *             `"{hour}"` - Replaced by the current two-digit hour in 24-hour format.
 *             `"{minute}"` - Replaced by the current two-digit minute.
 *         The directory will be created if it does not exist.
 *         The file will be created if it does not exist, or appended to if it does exist.
 *         Default is `"./logs/{log}.csv"`
 *     string `queue` - Controls how internal queueing system operates.
 *         Default for this writer is `log`, which maintains a separate queue for each log name.
 *         If you want to combine all messages into a single file regardless of source, change to queue=single
 *     int `max_messages` - Maximum number of messages to store per queue. Default is 100.
 *     bool `autoflush` - TRUE to automatically flush (process and dequeue) messages when queue is full; FALSE means excess messages cause a warning then are discarded.
 *         Default for this writer is TRUE, when a queue is full it is flushed to disk.
 * See {@link \Useful\Logger\AbstractQueuedWriter} for more details on `queue`, `max_messages` and `autoflush` queueing options.
 *
 * @uses \Useful\Csv
 * @uses \Useful\Logger
 * @uses \Useful\Logger\AbstractQueuedWriter
 * @uses \Useful\TextPatterns
 */
class File extends AbstractQueuedWriter
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
			'path' => './logs/{log}.csv',
			'queue' => 'log',
			'max_messages' => 100,
			'autoflush' => true,
		);
	}
	
	/**
	 * Write queued messages to file
	 *
	 * @param (string|null) $sQueue log name, or null when writer config queue=single
	 * @param array $aMessageList list of messages, each is message data as returned by {@link Logger::write_prepMessage}
	 * @return void
	 */
	protected function processMessages($sQueue, $aMessageList)
	{
		global $argv;
		$sPath = TextPatterns::interpolate(
			$this->aConfig['path'],
			array(
				'log' => $sQueue ? $sQueue : 'combined',
				'date' => date('Ymd', $aMessageList[0]['time']),
				'hour' => date('H', $aMessageList[0]['time']),
				'minute' => date('i', $aMessageList[0]['time']),
			)
		);

		$aColumns = array(
			'date',
			'time',
			'log',
			'level',
			'message',
			'timer',
			'request_uri',
			'request_id',
			'pid',
			'data'
		);
		$aRecords = array();
		foreach ($aMessageList as $aMessage) {
			$aRecords[] = array(
				date('Y-m-d', $aMessage['time']),
				date('H:i:s', $aMessage['time']),
				$aMessage['log'],
				$this->oLogger->getLevelLabel($aMessage['level']),
				$aMessage['msg'],
				$aMessage['timer'] ? $aMessage['timer'] : '',
				(PHP_SAPI == 'cli') ? ('cli:' . implode(' ', $argv)) : $_SERVER['REQUEST_URI'],
				$this->oLogger->getSessionId(),
				getmypid(),
				$aMessage['data']
					? (
						(
							is_string($aMessage['data'])
							|| is_numeric($aMessage['data'])
						)
						? $aMessage['data']
						: TextPatterns::dump($aMessage['data'], false, 'pretty')
					)
					: ''
					,
			);
		}

		$sDir = dirname($sPath);
		if (!file_exists($sDir)) {
			mkdir($sDir, 0777, true);
		}

		Csv::append(
			$sPath,
			$aRecords,
			array(
				'associative' => false,
				'column_names' => $aColumns,
			)
		);
	}
}
