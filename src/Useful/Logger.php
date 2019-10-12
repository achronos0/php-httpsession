<?php
/**
 * \Useful\Logger class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful;

/**
 * Message logging
 *
 * @uses \Useful\Exception
 * @uses \Useful\Logger\Log
 * @uses \Useful\Logger\AbstractWriter
 * @uses \Useful\Logger\Writer\*
 * @uses \Useful\TextPatterns
 * @uses \Psr\Log\LoggerInterface
 */
class Logger
{
	//////////////////////////////
	// Public static

	/**
	 * Return default logging system
	 *
	 * @api
	 * @param array $aConfig apply config settings to default logging system
	 * @return \Useful\Logger logger instance
	 */
	public static function getLogger($aConfig = array())
	{
		if (!self::$oDefaultLogger) {
			self::$oDefaultLogger = new self();
		}
		if ($aConfig) {
			self::$oDefaultLogger->setConfig($aConfig);
		}
		return self::$oDefaultLogger;
	}


	//////////////////////////////
	// Public

	/**
	 * Create a new logging system
	 *
	 * You probably only need one logging system, in which case just call `Logger::getLogger()`.
	 * Only use this constructor if you need more than one.
	 *
	 * @api
	 * @param array $aConfig configuration information, see {@link setConfig}.
	 */
	public function __construct($aConfig = array())
	{
		// Start timer
		$this->fSessionTimer = $this->getTimer();

		// Create session id
		$this->sSessionId = uniqid();

		// Set config
		if ($aConfig) {
			$this->setConfig($aConfig);
		}
	}

	/**
	 * Write a message to the specified named log
	 *
	 * The log may write the message to a file, display it, ignore it, etc., depending on its configuration.
	 * (If no other configuration is given, it will be written to a file in the log directory using the log name.)
	 *
	 * Each log message includes information besides the message text itself, including
	 *   * a timestamp
	 *   * a message level
	 *   * additional data passed to this function in $mData
	 * See Logger docs for more information.
	 *
	 * @api
	 * @param string $sLog log name
	 *   If the named log does not exist yet, it is created automatically using default settings.
	 * @param (string|int) $mLevel a defined message level, as (string) label or (int) number
	 * @param string $sMessage message to write
	 * @param mixed $mData additional data to store with message
	 * @param float $fStartTimer calculate duration using this time offset
	 *   If specified, task duration is automatically calculated and included with the message.
	 *   $fStartTimer value should be set by {@link getTimer}.
	 * @return void
	 * @throws \Useful\Exception on invalid level or writer
	*/
	public function write($sLog, $mLevel, $sMessage, $mData = null, $fStartTimer = null)
	{
		$aLogConfig = $this->getLogConfig($sLog);
		$iLevel = $this->getLevelInt($mLevel);
		$aWriters = $this->write_prepWriters($aLogConfig, $iLevel);
		if (!$aWriters)
			return;
		$aMessage = $this->write_prepMessage($aLogConfig, $iLevel, $sMessage, $mData, $fStartTimer);
		$this->write_commit($aLogConfig, $aWriters, $aMessage);
	}

	/**
	 * Process pending log data
	 *
	 * This is automatically called before the request ends.
	 * For long-running operations and/or those that you expect to generate a lot of messages, you can call `flush` yourself (to write messages to disk, display messages, etc.).
	 *
	 * @api
	 * @param (string|null) $sWriter name of writer to flush, or null to flush all writers
	 * @return void
	 * @throws \Useful\Exception on invalid writer
	 */
	public function flush($sWriter = null)
	{
		// Flush single writer
		if ($sWriter) {
			if (!isset($this->aWriters[$sWriter])) {
				throw new Exception("Invalid log writer $sWriter");
			}
			$this->aWriters[$sWriter]->flush();
			return;
		}

		foreach ($this->aWriters as $oWriter) {
			$oWriter->flush();
		}
	}

	/**
	 * Get an object for writing messages to a named log
	 *
	 * The log is created if it does not already exist.
	 *
	 * Log objects are a convenience wrapper around {@link write}.
	 *
	 * @api
	 * @param string $sLog name of log
	 * @return object log receiver object
	 * @uses \Useful\Logger\Log
	 */
	public function getLog($sLog)
	{
		$sLog = strtolower($sLog);
		if (!isset($this->aLogs[$sLog])) {
			$sClassName = $this->aConfig['log_class'];
			if (!class_exists($sClassName, true)) {
				throw new Exception("Invalid log class $sClassName");
			}
			$this->aLogs[$sLog] = new $sClassName($this, $sLog);
		}
		return $this->aLogs[$sLog];
	}

	/**
	 * Get current time as floating point number
	 *
	 * Use this value as the $fStartTimer argument to {@link write}.
	 *
	 * @api
	 * @return float timer
	 */
	public function getTimer()
	{
		return microtime(true);
	}

	/**
	 * Get logger start time as floating point number
	 *
	 * @api
	 * @return float start time
	 */
	public function getSessionTimer()
	{
		return $this->fSessionTimer;
	}

	/**
	 * Get logger unique identifier
	 *
	 * @api
	 * @return string identifier, in form of `uniqid()`
	 */
	public function getSessionId()
	{
		return $this->sSessionId;
	}

	/**
	 * Get logging system settings
	 *
	 * @api
	 * @return array config info
	 */
	public function getConfig()
	{
		return $this->aConfig;
	}

	/**
	 * Update logging system settings
	 *
	 * Settings are:
	 *   logs
	 *   writers
	 *   default_log_config
	 *   default_writer_config
	 *   level_numbers
	 *   level_masks
	 *   level_display
	 *
	 * @api
	 * @param array $aConfig new settings
	 * @return void
	 */
	public function setConfig($aConfig)
	{
		$aConfig = $this->prepConfig($aConfig);
		$this->aConfig = $this->mergeConfig($this->aConfig, $aConfig);
	}

	/**
	 * Get config settings for named log
	 *
	 * @api
	 * @param string $sLog
	 * @return array log config
	 */
	public function getLogConfig($sLog)
	{
		$sLog = strtolower($sLog);
		$aLogConfig = array_merge(
			array(
				'log' => $sLog,
				'mask' => 0,
				'writers' => array(),
			),
			$this->aConfig['default_log_config'],
			isset($this->aConfig['logs'][$sLog])
				? $this->aConfig['logs'][$sLog]
				: array()
		);
		return $aLogConfig;
	}

	/**
	 * Update config settings for named log
	 *
	 * @api
	 * @param string $sLog
	 * @param array $aConfig log config
	 * @return void
	 */
	public function setLogConfig($sLog, $aConfig)
	{
		$this->setConfig(array(
			'logs' => array(
				$sLog => $aConfig,
			)
		));
	}

	/**
	 * Get message level mask for named log
	 *
	 * @api
	 * @param string $sLog log name
	 * @return int level mask
	 */
	public function getLogLevelMask($sLog)
	{
		$aConfig = $this->getLogConfig($sLog);
		return $aConfig['mask'];
	}

	/**
	 * Set message level mask for named log
	 *
	 * @api
	 * @param string $sLog log name
	 * @param (int|string) $mMask level mask
	 * @return void
	 */
	public function setLogLevelMask($sLog, $mMask)
	{
		$this->setConfig(array(
			'logs' => array(
				$sLog => array(
					'mask' => $mMask,
				),
			)
		));
	}

	/**
	 * Return config array for writer
	 *
	 * @api
	 * @param string $sWriter
	 * @param array $aAdditionalConfig
	 * @return array writer config
	 */
	public function getWriterConfig($sWriter, $aAdditionalConfig = array())
	{
		return array_merge(
			array(
				'enabled' => false,
				'mask' => 0,
			),
			$this->aConfig['default_writer_config'],
			isset($this->aConfig['writers'][$sWriter])
				? $this->aConfig['writers'][$sWriter]
				: array()
				,
			$aAdditionalConfig
		);
	}

	/**
	 * Update config settings for writer
	 *
	 * @api
	 * @param string $sWriter
	 * @param array $aConfig log config
	 * @return void
	 */
	public function setWriterConfig($sWriter, $aConfig)
	{
		$this->setConfig(array(
			'writers' => array(
				$sWriter => $aConfig,
			)
		));
	}


	//////////////////////////////
	// Internal static

	protected static $oDefaultLogger;


	//////////////////////////////
	// Internal

	protected $aConfig = array(
		'logs' => array(),
		'writers' => array(),
		'default_log_config' => array(
			'mask' => 0x003F, // important
			'writers' => array('file'),
		),
		'default_writer_config' => array(
			'enabled' => false,
			'mask' => 0x07FF, // all
		),
		'level_numbers' => array(
			// problems
			'emergency' => 0x0001,
			'alert'     => 0x0002,
			'critical'  => 0x0004,
			'error'     => 0x0008,
			'warning'   => 0x0010,
			// information
			'notice'    => 0x0020, // important /\
			'info'      => 0x0040,
			'detail'    => 0x0080, // verbose /\
			// debugging
			'debug'     => 0x0100,
			'debug2'    => 0x0200,
			'debug3'    => 0x0400,
		),
		'level_masks' => array(
			// level groups
			'problems'      => 0x001F, // emergency, alert, critical, error, warning
			'information'   => 0x00E0, // notice, info, detail
			'debugging'     => 0x0700, // debug, debug2, debug3
			// verbosity
			'none'          => 0x0000,
			'important'     => 0x003F, // emergency, alert, critical, error, warning, notice
			'verbose'       => 0x00FF, // emergency, alert, critical, error, warning, notice, info, detail
			'all'           => 0x07FF,
		),
		'level_display' => array(
			'emergency' => '+++Error',
			'alert'     => '++Error',
			'critical'  => '+Error',
			'error'     => 'Error',
			'warning'   => 'Warning',
			'notice'    => 'Notice',
			'info'      => '+Notice',
			'detail'    => '++Notice',
			'debug'     => 'Debug',
			'debug2'    => '+Debug',
			'debug3'    => '++Debug',
			'problems'  => 'Problems',
			'messages'  => 'Messages',
			'verbose'   => 'Verbose',
			'none'      => 'None',
			'important' => 'Important',
			'all'       => 'All',
		),
		'log_class' => '\\Useful\\Logger\\Log',
	);
	protected $iMaxLevelMask = 0x07FF;
	protected $aLogs = array();
	protected $aWriters = array();
	protected $fSessionTimer;
	protected $sSessionId;

	/**
	 * Destructor
	 *
	 * @internal
	 * @return void
	 */
	public function __destruct()
	{
		$this->flush();
	}

	/**
	 * Finalize config array settings
	 *
	 * @internal
	 * @param array $aConfig input config settings
	 * @return array finalized config settings
	 */
	public function prepConfig($aConfig)
	{
		foreach ($aConfig as $sKey => &$mValue) {
			if ($sKey === 'mask') {
				$mValue = $this->getLevelMaskInt($mValue);
			}
			elseif ($sKey === 'level_numbers') {
				$this->iMaxLevelMask = 0;
				foreach ($mValue as &$iLevel) {
					$iLevel = intval($iLevel);
					$this->iMaxLevelMask |= $iLevel;
				}
				unset($iLevel);
			}
			elseif (is_string($sKey) && is_array($mValue)) {
				$mValue = $this->prepConfig($mValue);
			}
		}
		unset($mValue);
		return $aConfig;
	}

	/**
	 * Merge config arrays
	 *
	 * @internal
	 * @param array $aOld
	 * @param array $aNew
	 * @return array merged
	 */
	protected function mergeConfig($aOld, $aNew)
	{
		foreach ($aNew as $sKey => $mValue) {
			if (
				isset($aOld[$sKey])
				&& is_array($aOld[$sKey])
				&& is_array($mValue)
				&& !isset($aOld[$sKey][0])
				&& !isset($aOld[$sKey][1])
			) {
				$aOld[$sKey] = $this->mergeConfig($aOld[$sKey], $mValue);
				continue;
			}
			$aOld[$sKey] = $mValue;
		}
		return $aOld;
	}

	/**
	 * Check whether a log level is reportable for a level bitmask
	 *
	 * @internal
	 * @param int $iLevel as returned by {@link getLevelInt}
	 * @param int $iMask as returned by {@link getLevelMaskInt}
	 * @return bool true to report, false to ignore
	 */
	public function checkLevel($iLevel, $iMask)
	{
		return ($iLevel && ($iLevel & $iMask) == $iLevel);
	}

	/**
	 * Convert level label to level number
	 *
	 * @internal
	 * @param (string|int) $mLevel string level label or int level number
	 * @return int
	 * @throws \Useful\Exception on invalid level
	 */
	public function getLevelInt($mLevel)
	{
		if (is_string($mLevel)) {
			$mLevel = strtolower($mLevel);
			if (isset($this->aConfig['level_numbers'][$mLevel])) {
				return $this->aConfig['level_numbers'][$mLevel];
			}
			throw new Exception("Invalid log level $mLevel", 1);
		}
		if (is_int($mLevel) && in_array($mLevel, $this->aConfig['level_numbers'])) {
			return $mLevel;
		}
		if ($mLevel === true) {
			return min($this->aConfig['level_numbers']);
		}
		if ($mLevel === false) {
			return max($this->aConfig['level_numbers']);
		}
		throw new Exception("Invalid log level $mLevel", 1);
	}

	/**
	 * Get label for a message level
	 *
	 * @internal
	 * @param (string|int) $mLevel a defined message level, as (string) label or (int) number
	 * @return string label
	 * @throws \Useful\Exception on invalid level
	 */
	public function getLevelLabel($mLevel)
	{
		$iLevel = $this->getLevelInt($mLevel);
		return array_search($iLevel, $this->aConfig['level_numbers']);
	}

	/**
	 * Get display text for a message level
	 *
	 * @internal
	 * @param (string|int) $mLevel a defined message level, as (string) label or (int) number
	 * @return string display name
	 * @throws \Useful\Exception on invalid level
	 */
	public function getLevelDisplay($mLevel)
	{
		$sLabel = $this->getLevelLabel($mLevel);
		return
			isset($this->aConfig['level_display'][$sLabel])
			? $this->aConfig['level_display'][$sLabel]
			: $sLabel
		;
	}

	/**
	 * Convert mask label(s) to mask number
	 *
	 * @internal
	 * @param (string|int|array) $mMask string mask label or level label, int mask number, or array of same
	 * @return int bitmask
	 * @throws \Useful\Exception on invalid mask
	 */
	public function getLevelMaskInt($mMask)
	{
		if (is_int($mMask) && $mMask >= 0 && $mMask <= $this->iMaxLevelMask) {
			return $mMask;
		}

		if (is_array($mMask) && count($mMask) > 0) {
			$iFinalMask = 0;
			foreach ($mMask as $mValue) {
				$iFinalMask |= $this->getLevelMaskInt($mValue);
			}
			return $iFinalMask;
		}

		if (is_string($mMask)) {
			if (isset($this->aConfig['level_masks'][$mMask])) {
				return $this->aConfig['level_masks'][$mMask];
			}

			if (isset($this->aConfig['level_numbers'][$mMask])) {
				return ($this->getLevelInt($mMask) << 1) - 1;
			}

			if (preg_match('/[,; ]+/', $mMask)) {
				return $this->getLevelMaskInt(preg_split('/[,; ]+/', $mMask, -1, PREG_SPLIT_NO_EMPTY));
			}

			if (preg_match('/(=)|(\++)(\d*)|(-+)(\d*)/', $mMask, $aMatches)) {
				$mMask = str_replace($aMatches[0], '', $mMask);
				if (isset($this->aConfig['level_masks'][$mMask])) {
					$mMask = $this->aConfig['level_masks'][$mMask];
				}
				elseif (isset($this->aConfig['level_numbers'][$mMask])) {
					$mMask = $this->getLevelInt($mMask);
					if (!empty($aMatches[1])) {
						return $mMask;
					}
					$mMask = ($mMask << 1) - 1;
				}
				if (is_int($mMask)) {
					if (!empty($aMatches[1])) {
						return $iMask;
					}
					if (!empty($aMatches[2])) {
						$iBits = strlen($aMatches[2]);
						if (!empty($aMatches[3])) {
							$iBits += intval($aMatches[3]) - 1;
						}
						return min($this->iMaxLevelMask, (($mMask + 1) << $iBits) - 1);
					}
					if (!empty($aMatches[4])) {
						$iBits = strlen($aMatches[4]);
						if (!empty($aMatches[5])) {
							$iBits += intval($aMatches[5]) - 1;
						}
						return max(0, $mMask >> $iBits);
					}
				}
			}
		}

		throw new Exception("Invalid log level mask $mMask", 1);
	}

	/**
	 * Get label for a level mask
	 *
	 * @internal
	 * @param (string|int|array) $mMask string mask label or level label, int mask number, or array of same
	 * @return array list of labels
	 * @throws \Useful\Exception on invalid mask
	 */
	public function getLevelMaskLabel($mMask)
	{
		$iFinalMask = $this->getLevelMaskInt($mMask);
		$aLabelList = array();
		foreach (
			array_merge(
				$this->aConfig['level_masks'],
				$this->aConfig['level_numbers']
			) as $sLabel => $iLevel
		) {
			if ($this->checkLevel($iLevel, $iFinalMask)) {
				$aLabelList[] = $sLabel;
				$iFinalMask ^= $iLevel;
			}
		}
		return $aLabelList;
	}

	/**
	 * Get display text for a level mask
	 *
	 * @internal
	 * @param (string|int|array) $mMask string mask label or level label, int mask number, or array of same
	 * @return string display name
	 * @throws \Useful\Exception on invalid mask
	 */
	public function getLevelMaskDisplay($mMask)
	{
		$aLabelList = $this->getLevelMaskLabel($mMask);
		$aDisplayList = array();
		foreach ($aLabelList as $sLabel) {
			$aDisplayList[] =
				isset($this->aConfig['level_display'][$sLabel])
				? $this->aConfig['level_display'][$sLabel]
				: $sLabel
			;
		}
		return implode(', ', $aDisplayList);
	}

	/**
	 * Get an object for processing log messages
	 *
	 * The writer is created if it does not already exist.
	 *
	 * @internal
	 * @param string $sWriter name of writer
	 * @return \Useful\Logger\AbstractWriter log writer object
	 * @throws \Useful\Exception
	 * @uses \Useful\Logger\AbstractWriter
	 * @uses \Useful\Logger\Writer\*
	 */
	public function getWriter($sWriter)
	{
		$sWriter = strtolower($sWriter);
		if (!isset($this->aWriters[$sWriter])) {
			$aWriterConfig = $this->getWriterConfig($sWriter);

			if (isset($aWriterConfig['obj'])) {
				$oWriter = $aWriterConfig['obj'];
				unset($aWriterConfig['obj']);
				$oWriter->setLogger($this);
				$oWriter->setWriterName($sWriter);
			}
			else {
				if (isset($aWriterConfig['class'])) {
					$sClassName = $aWriterConfig['class'];
					unset($aWriterConfig['class']);
				}
				else {
					$sClassName = '\\Useful\\Logger\\Writer\\' . ucfirst($sWriter);
				}
				if (!class_exists($sClassName, true)) {
					throw new Exception("Invalid log writer class $sClassName");
				}
				$oWriter = new $sClassName($this, $sWriter);
			}
			$this->aWriters[$sWriter] = $oWriter;
		}
		return $this->aWriters[$sWriter];
	}

	/**
	 * Determine where to send a particular message
	 *
	 * @internal
	 * @param array $aLogConfig as returned by {@link getLogConfig}
	 * @param int $iLevel as returned by {@link getLevelInt}
	 * @return array writers => writer config
	 */
	public function write_prepWriters($aLogConfig, $iLevel)
	{
		if (!$this->checkLevel($iLevel, $aLogConfig['mask'])) {
			return false;
		}

		$aWriters = array();
		foreach ($aLogConfig['writers'] as $mKey => $mValue) {
			if (is_int($mKey) && is_string($mValue)) {
				$sWriter = $mValue;
				$aLogWriterConfig = array();
			}
			else {
				$sWriter = $mKey;
				if (is_array($mValue)) {
					$aLogWriterConfig = $mValue;
				}
				else {
					$aLogWriterConfig = array(
						'mask' => $mValue
					);
				}
			}
			$aLogWriterConfig = $this->getWriterConfig($sWriter, $aLogWriterConfig);
			if (!$aLogWriterConfig['enabled'] || !$this->checkLevel($iLevel, $aLogWriterConfig['mask'])) {
				continue;
			}
			$aWriters[$sWriter] = $aLogWriterConfig;
		}

		return $aWriters;
	}

	/**
	 * Assemble message data
	 *
	 * @internal
	 * @param array $aLogConfig as returned by {@link getLogConfig}
	 * @param int $iLevel as returned by {@link getLevelInt}
	 * @param string $sMessage
	 * @param (mixed|null) $mData
	 * @param (float|null) $fStartTimer
	 * @return array message data
	 */
	public function write_prepMessage($aLogConfig, $iLevel, $sMessage, $mData, $fStartTimer)
	{
		$fTime = $this->getTimer();

		if (is_array($mData)) {
			foreach (array_intersect(array_keys($this->aConfig['level_numbers']), array_keys($mData)) as $sDataLevelLabel) {
				$iDataLevel = $this->getLevelInt($sDataLevelLabel);
				if (!$this->checkLevel($iDataLevel, $aLogConfig['mask'])) {
					unset($mData[$sLabel]);
				}
			}
		}

		return array(
			'log' => $aLogConfig['log'],
			'time' => time(),
			'ftime' => $fTime - $this->getSessionTimer(),
			'level' => $iLevel,
			'msg' => TextPatterns::interpolate(
				strval($sMessage),
				is_array($mData) ? $mData : array('data' => $mData)
			),
			'data' => $mData,
			'timer' => $fStartTimer ? ($this->getTimer() - $fStartTimer) : NULL
		);
	}

	/**
	 * Send message to writers
	 *
	 * @internal
	 * @param array $aLogConfig as returned by {@link getLogConfig}
	 * @param array $aWriters as returned by {@link write_prepWriters}
	 * @param arraey $aMessage as returned by {@link write_prepMessage}
	 * @return void
	 */
	public function write_commit($aLogConfig, $aWriters, $aMessage)
	{
		foreach ($aWriters as $sWriter => $aWriterConfig) {
			$oWriter = $this->getWriter($sWriter);
			$oWriter->commit($aWriterConfig, $aLogConfig, $aMessage);
		}
	}

	/**
	 * Format a log message as text
	 *
	 * Format settings:
	 *     bool `html` - TRUE for html, FALSE for plain-text
	 *     string `pattern` - message text pattern, may contain interpolation variables for message parts
	 *     string `PART_format`, `PART_format_html` - sub-pattern for $aMessage[PART]
	 *     string `time_format` - format for $aMessage[time] , date() format
	 *     mixed `ftime_format` - format for $aMessage[ftime], TextPatterns::formatNumber() format
	 *     mixed `timer_format` - format for $aMessge[timer], TextPatterns::formatNumber() format
	 *
	 * @internal
	 * @param array $aMessage as returned by {@link write_prepMessage}
	 * @param array $aFormat format settings
	 * @return string
	 */
	public function formatMessage($aMessage, $aFormat = array())
	{
		$aFormat = array_merge(
			array(
				'html' => false,
				'pattern_text' => "{time} {log} {level} - {msg}{timer}{data}\n",
				'pattern_html' => "<div style=\"float: none; clear: both; display: block; \"><div>{time} {log} {level} - {msg}</div>{timer}{data}</div>\n",
				'part_patterns_text' => array(
					'ftime' => ' ({ftime})',
					'time' => '{time}{ftime}',
					'log' => '[{log}]',
					'timer' => "\n    Timer: {timer} seconds",
					'data' => "\n    {data}",
				),
				'part_patterns_html' => array(
					'ftime' => ' ({ftime})',
					'time' => '{time}{ftime}',
					'log' => '[{log}]',
					'msg:problems' => '<span style="font-weight: bold; color: red; ">{msg}</span>',
					'msg:=notice' => '<span style="font-weight: bold; ">{msg}</span>',
					'timer' => "<div style=\"padding: 0 30px; \">Timer: {timer} seconds</div>",
					'data' => "<div style=\"padding: 0 30px; \">{data}</div>",
				),
				'time_format' => 'Y-m-d H:i:s',
				'ftime_format' => 2,
				'timer_format' => 1,
			),
			$aFormat
		);

		$aParts = array(
			'time' =>
				$aFormat['time_format']
				? date($aFormat['time_format'], $aMessage['time'])
				: null,
			'ftime' =>
				$aFormat['ftime_format']
				? TextPatterns::formatNumber($aMessage['ftime'], $aFormat['ftime_format'])
				: null,
			'log' => $aMessage['log'],
			'level' => $this->getLevelDisplay($aMessage['level']),
			'msg' => $aMessage['msg'],
			'timer' =>
				($aMessage['timer'] && $aFormat['timer_format'])
				? TextPatterns::formatNumber($aMessage['timer'], $aFormat['timer_format'])
				: null,
			'data' => null,
		);
		if ($aFormat['html']) {
			foreach ($aParts as $sKey => &$sValue) {
				if (is_string($sValue)) {
					$sValue = htmlspecialchars($sValue);
				}
			}
			unset($sValue);
		}
		if (isset($aMessage['data'])) {
			$aData =
				is_array($aMessage['data'])
				? $aMessage['data']
				: array( 'data' => $aMessage['data'] )
			;
			$aParts['data'] =
				$aFormat['html']
				? TextPatterns::dump($aData, true, 'pretty')
				: rtrim(str_replace(PHP_EOL, PHP_EOL . '    ', TextPatterns::dump($aData, false, 'pretty')))
			;
		}

		foreach ($aFormat[$aFormat['html'] ? 'part_patterns_html' : 'part_patterns_text'] as $sKey => $sPattern) {
			if (strpos($sKey, ':') !== false) {
				list($sKey, $sMask) = explode(':', $sKey, 2);
				if (!$this->checkLevel($aMessage['level'], $this->getLevelMaskInt($sMask))) {
					continue;
				}
			}
			if (isset($aParts[$sKey])) {
				$aParts[$sKey] = TextPatterns::interpolate($sPattern, $aParts);
			}
		}

		return TextPatterns::interpolate($aFormat[$aFormat['html'] ? 'pattern_html' : 'pattern_text'], $aParts);
	}
}
