<?php
/**
 * \Useful\CliScript class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

declare(ticks = 100);

namespace Useful;

/**
 * Manage a command-line interface (CLI) script
 */
abstract class CliScript
{
	//////////////////////////////
	// Abstract

	abstract protected function main();


	//////////////////////////////
	// Public

	public function run()
	{
		$this->init();
		$this->start();
		$this->main();
		$this->stop();
		$this->exit();
	}

	public function fail($sMessage, $oException = null, $iExitCode = 1)
	{
		if ($oException) {
			$sMessage .= ': ' . $oException->getMessage();
		}
		$this->log()->critical("Error: $sMessage", $oException);
		$this->end($iExitCode, false);
	}

	public function getArg($mArg)
	{
		if (is_string($mArg) && isset($this->aOptionArgs[$mArg])) {
			return $this->aOptionArgs[$mArg];
		}
		if (is_int($mArg) && isset($this->aPositionalArgs[$mArg])) {
			return $this->aPositionalArgs[$mArg];
		}
		return null;
	}

	public function getOptionSet($sPrefix)
	{
		$iPrefixLen = strlen($sPrefix);
		$aOptions = array();
		foreach ($this->aOptionArgs as $sName => $mVal) {
			if (substr($sName, 0, $iPrefixLen) == $sPrefix) {
				if ($sName == $sPrefix) {
					$aOptions[''] = $mVal;
				}
				else {
					$aOptions[substr($sName, $iPrefixLen)] = $mVal;
				}
			}
		}
		return $aOptions;
	}

	public function log()
	{
		return $this->oLog;
	}

	public function setLogger($oLog)
	{
		$this->oLog = $oLog;
	}


	//////////////////////////////
	// Internal

	protected $iExitCode;
	// protected $bMonitored;
	// protected $aPositionalArgs;
	// protected $aOptionArgs;
	// protected $aHeadlineData;
	protected $oLog;

	protected function init()
	{
		$this->init_first();
		$this->init_logger();
		$this->init_errors();
		$this->init_runtime();
		$this->init_config();
		$this->init_env();
		$this->init_args();
		$this->init_last();
	}

	protected function start()
	{
		$this->start_args();
	}

	protected function stop()
	{
		//
	}

	protected function exit()
	{
		if ($this->iExitCode === null) {
			$this->iExitCode = 2;
		}
		exit($this->iExitCode);
	}

	protected function init_first()
	{
		/* do nothing */
	}

	protected function init_logger()
	{
		if (!$this->log()) {
			$oLogger = new Logger();
			$oLog = $oLogger->getLog('script');
			$this->setLogger($oLog);
		}
	}

	protected function init_errors()
	{
		/*
			// Make errors highly visible
			ini_set('display_errors', 1);
			error_reporting(E_ALL);
			set_time_limit(0);
		*/

		ErrorHandler::
	}

	protected function init_runtime()
	{

		// Set timezone
		date_default_timezone_set(isset($_ENV['TZ']) ? $_ENV['TZ'] : 'America/New_York');

		// Require command-line
		if (PHP_SAPI != 'cli') {
			throw new Exception('Command-line only');
		}
		// Check whether there is anyone listening to STDOUT
		$this->bMonitored = @posix_isatty(STDOUT);

		// @FIXME logging
		$this->cli()->setPrintLevel($this->bMonitored ? 1 : 0);

		// Install our own signal handler
		if (function_exists('pcntl_signal')) {
			pcntl_signal(SIGHUP, array($this, '_signalHandler'));
			pcntl_signal(SIGINT, array($this, '_signalHandler'));
			pcntl_signal(SIGTERM, array($this, '_signalHandler'));
			pcntl_signal(SIGUSR1, array($this, '_signalHandler'));
			if (function_exists('pcntl_async_signals')) {
				pcntl_async_signals(true);
			}
		}
	}

	protected function init_last()
	{
		/* do nothing */
	}

	public function end($iExitCode = 0, $bHeadline = true)
	{
		$aStatusMap = array(
			0 => 'success',
			1 => 'FAILED',
			2 => 'INCOMPLETE',
		);
		$this->aHeadlineData['status'] = isset($aStatusMap[$iExitCode]) ? $aStatusMap[$iExitCode] : 'unknown status';
		if ($iExitCode == 0 && $this->iLowMessageLevel < 0) {
			$this->aHeadlineData['status'] = 'WARNING';
		}
		$sHeadline = $this->template($this->defineHeadline(), $this->aHeadlineData);
		$sDetails = isset($this->aHeadlineData['results']) ? $this->aHeadlineData['results'] : null;

		if ($bHeadline) {
			$this->important($sHeadline, $sDetails);
		}

		$sTo = $this->getArg('alert-to');
		$sOn = $this->getArg('alert-on');
		if (
			$sTo
			&& (
				$sOn == 'all'
				|| ($sOn == 'error' && $iExitCode)
			)
		) {
			mail($sTo, $sHeadline, implode("\n", $this->aMessages) . "\n");
		}

		if ($this->iState == 1) {
			$this->iState = 0;
			$this->onShutdown();
			$this->oLogger->flush();
		}

		exit($iExitCode);
	}


	protected function init()
	{
		$this->init_start();
		$this->init_log();
		if ($this->aFatal) {
			$this->croak(implode("\n", $this->aFatal));
		}
		$this->init_config();
		$this->init_handleStandardArgs();
		if ($this->aFatal) {
			$this->croak(implode("\n", $this->aFatal));
		}
	}


	protected function init_php()
	{


	}

	protected function init_config()
	{
		global $argv;
		list($this->aPositionalArgs, $this->aOptionArgs, $aInvalidArgList) = $this->parseCliArgs(
			array_merge(
				$this->internalDefineOptions(),
				$this->defineOptions()
			),
			$this->defineEnvPrefix(),
			array_slice($argv, 1),
			$_ENV
		);
	}

	protected function init_handleStandardArgs()
	{
		if ($this->getArg('debug-dump-args')) {
			ob_start();
			print "Options:";
			if ($this->aOptionArgs) {
				print "\n";
				print_r($this->aOptionArgs);
			}
			else {
				print " (none)";
			}
			print "\nArguments:";
			if ($this->aPositionalArgs) {
				print "\n";
				print_r($this->aPositionalArgs);
			}
			else {
				print " (none)";
			}
			print "\n";
			$this->stdout(preg_replace('/\nArray\n\((.*?)\n\)\n/s', "\$1", ob_get_clean()));
			$this->end(0);
		}

		if ($this->getArg('help') || count($argv) == 1) {
			$this->stdout($this->getUsage());
			$this->end(0);
		}

		$iVerbose = $this->getArg('verbose');
		if ($iVerbose) {
			$this->iOutputLevel = $iVerbose + 1;
		}

		$sVal = $this->getArg('log-file');
		if ($sVal) {
			$this->mkdir('init: create log dir', dirname($sVal));
			$this->rLogFileHandle = fopen($sVal, 'a');
		}
	}

	protected function parseCliArgs($aDefineOptions, $sEnvVarPrefix, $aArgv, $aEnv)
	{
		$aPositionalArgs = array();
		$aOptionArgs = array();
		$aInvalidArgList = array();

		$aLongOptions = array();
		$aRegexOptions = array();
		$aShortOptMap = array();
		foreach ($aDefineOptions as $sOpt => $aDefineOpt) {
			if (!empty($aDefineOpt['regex'])) {
				$aRegexOptions[$sOpt] = $aDefineOpt;
				continue;
			}
			if (!empty($aDefineOpt['short'])) {
				foreach (str_split($aDefineOpt['short']) as $sChar) {
					$aShortOptMap[$sChar] = $sOpt;
				}
			}
			$aLongOptions[$sOpt] = $aDefineOpt;
			if (isset($aDefineOpt['default'])) {
				$aOptionArgs[$sOpt] = $aDefineOpt['default'];
			}
		}

		$iEnvPrefixLen = strlen($sEnvVarPrefix);
		foreach ($aEnv as $sName => $sVal) {
			if (substr($sName, 0, $iEnvPrefixLen) != $sEnvVarPrefix) {
				continue;
			}
			$sOpt = strtolower(str_replace('_', '-', substr($sName, $iEnvPrefixLen)));
			if (isset($aLongOptions[$sOpt])) {
				$aOptionArgs[$sOpt] = $sVal;
				continue;
			}
			foreach ($aRegexOptions as $sRegex => $aDefineOpt) {
				if (preg_match($sRegex, $sOpt)) {
					$aOptionArgs[$sOpt] = $sVal;
					continue 2;
				}
			}
		}

		while ($aArgv) {
			$sArg = array_shift($aArgv);
			if (substr($sArg, 0,1) != '-') {
				$aPositionalArgs[] = $sArg;
				continue;
			}
			if ($sArg == '--') {
				$aPositionalArgs = array_merge($aPositionalArgs, $aArgv);
				break;
			}
			$aParseOptList = array();
			if (preg_match('/^-(\w+)$/', $sArg, $aMatches)) {
				foreach (str_split($aMatches[1]) as $sChar) {
					if (isset($aShortOptMap[$sChar])) {
						$sOpt = $aShortOptMap[$sChar];
						$aParseOptList[] = array($sOpt, $aLongOptions[$sOpt], null);
					}
					else {
						$aInvalidArgList[] = "Invalid short option -$sChar";
					}
				}
			}
			elseif (preg_match('/^--([\w\-]+)(?:=(.*))?$/', $sArg, $aMatches)) {
				$sOpt = $aMatches[1];
				$aDefineOpt = null;
				if (isset($aLongOptions[$sOpt])) {
					$aDefineOpt = $aLongOptions[$sOpt];
				}
				else {
					foreach ($aRegexOptions as $sRegex => $aRegexOpt) {
						if (preg_match($sRegex, $sOpt)) {
							$aDefineOpt = $aRegexOpt;
							break;
						}
					}
				}
				if (!$aDefineOpt) {
					$aInvalidArgList[] = "Invalid option --$sOpt";
				}
				elseif (isset($aMatches[2])) {
					$aParseOptList[] = array($sOpt, $aDefineOpt, $aMatches[2]);
				}
				elseif (isset($aDefineOpt['hasvalue']) && $aDefineOpt['hasvalue'] == false) {
					$aParseOptList[] = array($sOpt, $aDefineOpt, null);
				}
				elseif ($aArgv && substr($aArgv[0], 0, 1) != '-') {
					$sVal = array_shift($aArgv);
					if ($sVal == '=') {
						if (!$aArgv) {
							$aInvalidArgList[] = "Missing value for --$sOpt";
							continue;
						}
						$sVal = array_shift($aArgv);
					}
					$aParseOptList[] = array($sOpt, $aDefineOpt, $sVal);
				}
				elseif (isset($aDefineOpt['hasvalue']) && $aDefineOpt['hasvalue'] == true) {
					$aInvalidArgList[] = "Missing value for --$sOpt";
				}
				else {
					$aParseOptList[] = array($sOpt, $aDefineOpt, null);
				}
			}
			else {
				$aInvalidArgList[] = "Unrecognized argument '$sArg'";
			}
			foreach ($aParseOptList as $aParseOpt) {
				list($sOpt, $aDefineOpt, $mVal) = $aParseOpt;
				if (!empty($aDefineOpt['inc']) && $mVal === null) {
					if (isset($aOptionArgs[$sOpt])) {
						$aOptionArgs[$sOpt]++;
					}
					else {
						$aOptionArgs[$sOpt] = 1;
					}
					continue;
				}
				if ($mVal === null) {
					$mVal = 1;
				}
				if (is_string($mVal) && preg_match('/^\d+$/', $mVal)) {
					$mVal = intval($mVal);
				}
				$aOptionArgs[$sOpt] = $mVal;
			}
		}

		ksort($aOptionArgs);
		return array($aPositionalArgs, $aOptionArgs, $aInvalidArgList);
	}

	public function _signalHandler($iSignal)
	{
		$this->end(2);
	}

	public function __destruct()
	{
		$this->end();
	}
}
