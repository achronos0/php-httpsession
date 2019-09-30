<?php
/**
 * \Useful\Shell class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful;

/**
 * x
 *
 * @uses \Useful\Exception
 */
class Shell
{
	//////////////////////////////
	// Public static

	public static function getShell($aConfig = array())
	{
		if (!self::$oDefaultShell) {
			self::$oDefaultShell = new self();
		}
		if ($aConfig) {
			self::$oDefaultShell->setConfig($aConfig);
		}
		return self::$oDefaultShell;
	}


	//////////////////////////////
	// Public

	public function createCommand($aConfig = array())
	{
		//
	}


	/**
	 * Get shell settings
	 *
	 * @api
	 * @return array config info
	 */
	public function getConfig()
	{
		return $this->aConfig;
	}

	/**
	 * Update shell settings
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

	public function run($sMode, $sCommand)
	{
		
	}


	//////////////////////////////
	// Internal static

	protected static $oDefaultShell;


	//////////////////////////////
	// Internal

	protected $aConfig = array(
	);

	protected function sh($sOperation, $sPattern, $mArg/*, $mArg...*/)
	{
		$aArgList = func_get_args();
		$sCommand = vsprintf($sPattern, $this->sh_escape(array_slice($aArgList, 2)));

		$this->aLastSh = array(
			'cmd' => $sCommand,
			'exit' => null,
			'out' => null,
			'err' => null,
		);

		if ($sOperation) {
			$this->verbose("$sOperation (sh): running");
		}

		$sErrFile = $this->makeTempFile();
		$aOutput = null;
		$iExitCode = null;
		if (exec("$sCommand 2> $sErrFile", $aOutput, $iExitCode) === false) {
			@unlink($sErrFile);
			throw new Exception("$sOperation (sh): shell exec failed");
		}

		$sOutput = trim(implode("\n", $aOutput));
		$sErrOutput = trim(file_get_contents($sErrFile));
		@unlink($sErrFile);

		$this->aLastSh['exit'] = $iExitCode;
		$this->aLastSh['out'] = $sOutput;
		$this->aLastSh['err'] = $sErrOutput;

		if ($iExitCode) {
			if ($sOperation) {
				$this->verbose("$sOperation (sh): failed");
			}
			$e = new Exception("$sOperation (sh): failed: $sErrOutput", $iExitCode);
			$e->data = array(
				'sh' => $this->aLastSh,
			);
			throw $e;
		}

		if ($sOperation) {
			$this->debug("$sOperation (sh): complete", $this->aLastSh);
		}

		return $sOutput;
	}

	protected function sh_escape($aArgList)
	{
		if (!$aArgList || $aArgList[0] === false) {
			return $aArgList;
		}
		foreach ($aArgList as &$mArg) {
			if (is_array($mArg)) {
				$mArg = implode(' ', $this->sh_escape($mArg));
			}
			elseif (is_string($mArg) && !preg_match('#^[\w/,:\.\-]+$#s', $mArg)) {
				$mArg = escapeshellarg($mArg);
			}
		}
		unset($mArg);
		return $aArgList;
	}
}
