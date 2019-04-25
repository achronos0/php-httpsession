<?php

use PHPUnit\Framework\TestCase;

abstract class BaseConversionTest extends TestCase
{
	//////////////////////////////
	// To be defined by subclasses

	public $sTestClass;
	public $aConversionTests;


	//////////////////////////////
	// Overload TestCase defaults

	public function setUp()
	{
		$this->sTempFile = tempnam(sys_get_temp_dir(), 'php');
	}

	public function tearDown()
	{
		@unlink($this->sTempFile);
	}


	//////////////////////////////
	// Internal

	protected $sTempFile;

	protected function runTestConversions_parse()
	{
		$this->runConversionTests(
			'p',
			function ($aTest, $sClass)
			{
				$aResult = $sClass::parse($aTest['content']);
				return array( 'data', $aResult );
			}
		);
	}

	protected function runTestConversions_read()
	{
		$this->runConversionTests(
			'r',
			function ($aTest, $sClass)
			{
				file_put_contents($this->sTempFile, $aTest['content']);
				$aResult = $sClass::read($this->sTempFile);
				return array( 'data', $aResult );
			}
		);
	}

	protected function runTestConversions_generate()
	{
		$this->runConversionTests(
			'g',
			function ($aTest, $sClass)
			{
				$sResult = $sClass::generate($aTest['data']);
				return array( 'content', $sResult );
			}
		);
	}

	protected function runTestConversions_write()
	{
		$this->runConversionTests(
			'w',
			function ($aTest, $sClass)
			{
				$sClass::write($this->sTempFile, $aTest['data']);
				$sResult = file_get_contents($this->sTempFile);
				return array( 'content', $sResult );
			}
		);
	}

	protected function runConversionTests($sRunCode, $xTestHandler)
	{
		static $aMethodNames = array(
			'p' => 'parse',
			'r' => 'read',
			'g' => 'generate',
			'w' => 'write',
		);
		foreach ($this->aConversionTests as $aTest) {
			// Check if conversion test is applicable to this method
			if (isset($aTest['run']) && strpos($aTest['run'], $sRunCode) === false)
				continue;

			// Normalize whitespace in content
			if (preg_match('/^\x0a(\t*)/', $aTest['content'], $aMatch)) {
				$aTest['content'] = preg_replace(
					'/\t+$/',
					'',
					str_replace(
						$aMatch[1],
						'',
						substr($aTest['content'], 1)
					)
				);
			}

			// Generate results
			list($sKey, $mResult) = $xTestHandler($aTest, $this->sTestClass);

			// Compare results to expected results
			$this->assertSame(
				$aTest[$sKey],
				$mResult,
				substr($this->sTestClass, 7)
					. '::'
					. $aMethodNames[$sRunCode]
					. '() conversion test failed: '
					. $aTest['name']
			);
		}
	}
}
