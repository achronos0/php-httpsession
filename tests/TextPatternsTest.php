<?php

use PHPUnit\Framework\TestCase;

class TextPatternsTest extends TestCase
{
	/*
	public function testDump()
	{
		@TODO add test
    }
    */

	public function testFormatFileSize()
	{
		$aTests = array(
			array('100',    100,     0),
			array('100.0',  100,     1),
			array('3K',     3500,    0),
			array('3.4K',   3500,    1),
			array('5M',     5300000, 0),
			array('5.1M',   5300000, 1),
		);
		foreach ($aTests as $aTest) {
			list($sResult, $iSize, $iDecimals) = $aTest;
			$this->assertEquals($sResult, \Useful\TextPatterns::formatFileSize($iSize, $iDecimals));
		}
	}

	/*
	public function testFormatNumber()
	{
		@TODO add test
	}
	*/

	/*
	public function testFormatPlainTextBlock()
	{
		@TODO add test
	}
	*/

	public function testInterpolate()
	{
		$aTests = array(
			array(
				'Test a=a, b=2, c=3.14, d={ddd}',
				'Test a={aaa}, b={bbb}, c={ccc}, d={ddd}',
				array(
					'aaa' => 'a',
					'bbb' => 2,
					'ccc' => 3.14
				),
			),
		);
		foreach ($aTests as $aTest) {
			list($sResult, $sTemplate, $aData) = $aTest;
			$this->assertEquals($sResult, \Useful\TextPatterns::interpolate($sTemplate, $aData));
		}
	}
}
