<?php

use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
	public function testError()
	{
		$aResult = $this->runErrorScript('error');
		$this->assertEquals(2, count($aResult));
    	$sErrorText = 'Invalid argument supplied for foreach()';
		$this->assertErrorLoggerOutput(false, $sErrorText, $aResult[0]);
		$this->assertErrorHandlerOutput(false, $sErrorText, $aResult[1]);
	}

    public function testFatal()
    {
		$aResult = $this->runErrorScript('fatal');
		$this->assertEquals(2, count($aResult));
    	$sErrorText = 'Uncaught Error: Class \'ClassDoesNotExist\'';
		$this->assertErrorLoggerOutput(true, $sErrorText, $aResult[0]);
		$this->assertErrorHandlerOutput(true, $sErrorText, $aResult[1]);
    }

    public function testThrow()
    {
		$aResult = $this->runErrorScript('throw');
    	$sErrorText = 'Invalid argument supplied for foreach()';
		$this->assertEquals(1, count($aResult));
		$this->assertErrorExceptionOutput($sErrorText, $aResult[0]);
    }

    public function testNative()
    {
		$aResult = $this->runErrorScript('native');
    	$sErrorText = 'Invalid argument supplied for foreach()';
		$this->assertEquals(3, count($aResult));
		$this->assertErrorLoggerOutput(false, $sErrorText, $aResult[0]);
		$this->assertErrorHandlerOutput(false, $sErrorText, $aResult[1]);
		$this->assertIsString($aResult[2]);
		$this->assertStringContainsString($sErrorText, $aResult[2]);
    }

    protected function runErrorScript($sArg)
    {
		$sOutput = shell_exec(PHP_BINARY . ' ' . __DIR__ . '/data/error.php ' . $sArg . ' 2> /dev/null');
		$aResult = array();
		foreach (explode("\n", $sOutput) as $sLine) {
			if (!$sLine) {
				continue;
			}
			if (substr($sLine, 0, 1) == '{') {
				$aResult[] = json_decode($sLine, true);
			}
			else {
				$aResult[] = $sLine;
			}
		}
		return $aResult;
    }

	protected function assertErrorLoggerOutput($bFatal, $sErrorText, $aData)
	{
		$this->assertIsArray($aData);
		$this->assertArrayHasKey('source', $aData);
		$this->assertArrayHasKey('queue', $aData);
		$this->assertArrayHasKey('message', $aData);
		$this->assertEquals('logger', $aData['source']);
		if ($bFatal) {
			$this->assertEquals('phpfatal', $aData['queue']);
		}
		else {
			$this->assertEquals('phperror', $aData['queue']);
		}
		$this->assertIsArray($aData['message']);
		$this->assertArrayHasKey('msg', $aData['message']);
		$this->assertStringContainsString($sErrorText, $aData['message']['msg']);
	}

	protected function assertErrorHandlerOutput($bFatal, $sErrorText, $aData)
	{
		$this->assertIsArray($aData);
		$this->assertArrayHasKey('source', $aData);
		$this->assertArrayHasKey('fatal', $aData);
		$this->assertArrayHasKey('severity', $aData);
		$this->assertArrayHasKey('message', $aData);
		$this->assertArrayHasKey('error', $aData);
		$this->assertEquals('handler', $aData['source']);
		if ($bFatal) {
			$this->assertTrue($aData['fatal']);
			$this->assertEquals('critical', $aData['severity']);
		}
		else {
			$this->assertFalse($aData['fatal']);
			$this->assertEquals('error', $aData['severity']);
		}
		$this->assertStringContainsString($sErrorText, $aData['message']);
		$this->assertIsArray($aData['error']);
		$this->assertArrayHasKey('type', $aData['error']);
		$this->assertArrayHasKey('message', $aData['error']);
		$this->assertArrayHasKey('file', $aData['error']);
		$this->assertArrayHasKey('line', $aData['error']);
		$this->assertIsInt($aData['error']['type']);
		$this->assertStringContainsString($sErrorText, $aData['error']['message']);
		$this->assertIsString($aData['error']['file']);
		$this->assertIsInt($aData['error']['line']);
		if (!$bFatal) {
			$this->assertArrayHasKey('trace', $aData['error']);
			$this->assertIsArray($aData['error']['trace']);
		}
    }

    protected function assertErrorExceptionOutput($sErrorText, $aData)
    {
		$this->assertIsArray($aData);
		$this->assertArrayHasKey('source', $aData);
		$this->assertArrayHasKey('class', $aData);
		$this->assertArrayHasKey('message', $aData);
		$this->assertArrayHasKey('code', $aData);
		$this->assertArrayHasKey('type', $aData);
		$this->assertArrayHasKey('file', $aData);
		$this->assertArrayHasKey('line', $aData);
		$this->assertArrayHasKey('trace', $aData);
		$this->assertEquals('exception', $aData['source']);
		$this->assertEquals('ErrorException', $aData['class']);
		$this->assertEquals($sErrorText, $aData['message']);
		$this->assertIsInt($aData['code']);
		$this->assertIsInt($aData['type']);
		$this->assertIsString($aData['file']);
		$this->assertIsInt($aData['line']);
		$this->assertIsArray($aData['trace']);
    }
}
