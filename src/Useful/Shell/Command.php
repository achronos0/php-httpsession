<?php
/**
 * \Useful\Shell\Command class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful\Shell;

use Useful\ArrayPatterns, Useful\Exception, Useful\TextPatterns;

/**
 * x
 *
 * @uses \Useful\ArrayPatterns
 * @uses \Useful\Exception
 * @uses \Useful\Shell\Result
 * @uses \Useful\TextPatterns
 */
class Command
{
	//////////////////////////////
	// Public

	public function run($aRunData = array(), $aRunConfig = array())
	{
		if ($aRunConfig) {
			$aRunConfig = ArrayPatterns::mergeConfig($this->aConfig, $aRunConfig);
		}
		else {
			$aRunConfig = $this->aConfig;
		}
		$aRunData = array_merge($aConfig['data'], $aRunData);
		return new CommandResult($this->execute($aRunData, $aRunConfig));
	}

	public function getConfig()
	{
		return $this->aConfig;
	}

	public function setConfig($aConfig)
	{
		$this->aConfig = ArrayPatterns::mergeConfig($this->aConfig, $aConfig);
	}

	//////////////////////////////
	// Internal

	protected $aConfig array(
		'bin' => null,
		'args' => null,
		'data' => array(),
		'cwd' => null,
		'env' => null,
		'mode' => null,
		'in' => null,
		'out' => null,
		'err' => null,
	);

	public function __construct($aConfig)
	{
		$this->setConfig($aConfig);
	}

	protected function execute($aData, $aConfig)
	{
		$aData = 
	}
}
