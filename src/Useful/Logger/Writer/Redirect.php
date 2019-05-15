<?php
/**
 * \Useful\Logger\Writer\Redirect class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Logger\Writer;

use Useful\Logger\AbstractWriter;

/**
 * Send messages to other named logs
 *
 * Writer config settings:
 *     array `redirect_logs` list of log names to re-post message to. Messages are handled per each of those logs' settings.
 *
 * @uses \Useful\Logger
 * @uses \Useful\Logger\AbstractWriter
 */
class Redirect extends AbstractWriter
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
		if (empty($aWriterConfig['redirect_logs'])) {
			return;
		}
		foreach ($aWriterConfig['redirect_logs'] as $sRedirectLog) {
			$aRedirectLogConfig = $this->oLogger->getLogConfig($sRedirectLog);
			$aWriters = $this->oLogger->write_prepWriters($aRedirectLogConfig, $aMessage['level']);
			if (!$aWriters)
				return;
			$this->oLogger->write_commit($aRedirectLogConfig, $aWriters, $aMessage);
		}
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
