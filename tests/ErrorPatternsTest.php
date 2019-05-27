<?php

use PHPUnit\Framework\TestCase;

use Useful\ErrorPatterns;

class ErrorPatternsTest extends TestCase
{
	public function testGetTrace()
	{
		$aTrace = ErrorPatterns::getTrace();
		$this->assertIsArray($aTrace);
		foreach ($aTrace as $sFrame) {
			$this->assertRegExp('/. called at ./', $sFrame);
		}

		$aTrace = ErrorPatterns::getTrace(true);
		$this->assertIsArray($aTrace);
		foreach ($aTrace as $aFrame) {
			$this->assertIsArray($aFrame);
			$this->assertArrayHasKey('trace', $aFrame);
			$this->assertArrayHasKey('caller', $aFrame);
			$this->assertArrayHasKey('called', $aFrame);
			$this->assertArrayHasKey('file', $aFrame);
			$this->assertArrayHasKey('line', $aFrame);
			$this->assertArrayHasKey('function', $aFrame);
			$this->assertArrayHasKey('class', $aFrame);
			$this->assertArrayHasKey('type', $aFrame);
		}
		$iFullCount = count($aTrace);

		$aTrace = ErrorPatterns::getTrace(false, 1);
		$this->assertEquals($iFullCount - 1, count($aTrace));

		$aTrace = ErrorPatterns::getTrace(false, 1, 3);
		$this->assertEquals(3, count($aTrace));
	}

	public function testGetErrorLabel()
	{
		$this->assertEquals(
			'PHP runtime fatal error (E_ERROR)',
			ErrorPatterns::getErrorLabel(E_ERROR)
		);
	}

	public function testGetErrorSeverity()
	{
		$this->assertEquals(
			'critical',
			ErrorPatterns::getErrorSeverity(E_ERROR)
		);
	}

	public function testFormatPhpError()
	{
		$this->assertEquals(
			'PHP runtime fatal error (E_ERROR): Bad problem in "Foo" at /path/to/foo.php line 66',
			ErrorPatterns::formatPhpError(E_ERROR, 'Bad problem in &quot;Foo&quot;', '/path/to/foo.php', 66)
		);
	}

	public function testGetLastPhpError()
	{
		@strpos(); $iLine = __LINE__;
		$this->assertRegExp(
			'/^PHP runtime warning \(E_WARNING\): strpos\(\) expects at least 2 parameters, 0 given at .*ErrorPatternsTest\.php line ' . $iLine . '$/',
			ErrorPatterns::getLastPhpError()
		);
	}
}
