<?php
/**
 * \Useful\Filesystem class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful;

use Useful\ArrayPatterns;

/**
 * x
 *
 * @uses \Useful\Exception
 */
class Filesystem
{
	//////////////////////////////
	// Public static

	public function local($sRootDir)
	{
		return new self('local', array(
			'root_dir' => $sRootDir,
		));
	}


	//////////////////////////////
	// Public

	public function __construct($sType, $aConfig = array())
	{
		f
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
	// Internal static

	

	//////////////////////////////
	// Internal

	protected $aConfig = array(
		'type' => null,
		'driver' => null,
		'root' => null,
	);

}
