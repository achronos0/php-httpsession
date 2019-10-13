<?php

use PHPUnit\Framework\TestCase;

use Useful\Logger;

class MockWriter extends \Useful\Logger\AbstractQueuedWriter
{
	public $aProcessed = array();

	public function getProcessed()
	{
		$aReturn = $this->aProcessed;
		$this->aProcessed = array();
		return $aReturn;
	}

	protected function getDefaultConfig()
	{
		return array(
			'queue' => 'single',
			'max_messages' => 1,
			'autoflush' => false,
		);
	}
	
	protected function processMessages($sQueue, $aMessageList)
	{
		$this->aProcessed[] = array($sQueue, $aMessageList);
	}
}

class LoggerTest extends TestCase
{
	const L_EMERGENCY       = 0x0001;
	const L_ALERT           = 0x0002;
	const L_CRITICAL        = 0x0004;
	const L_ERROR           = 0x0008;
	const L_WARNING         = 0x0010;
	const L_NOTICE          = 0x0020;
	const L_INFO            = 0x0040;
	const L_DETAIL          = 0x0080;
	const L_DEBUG           = 0x0100;
	const L_DEBUG2          = 0x0200;
	const L_DEBUG3          = 0x0400;
	const LM_NONE           = 0x0000;
	const LM_PROBLEMS       = 0x001F;
	const LM_INFORMATION    = 0x00E0;
	const LM_DEBUGGING      = 0x0700;
	const LM_ALL            = 0x07FF;

	public function testConstruct()
	{
		$oLogger = Logger::getLogger();
		$this->assertInstanceOf(
			'\\Useful\Logger',
			$oLogger,
			'Logger::getLogger() class'
		);

		$oOtherLogger = new Logger();
		$this->assertInstanceOf(
			'\\Useful\Logger',
			$oOtherLogger,
			'Logger new() class'
		);
	}

	public function testBasicMethods()
	{
		$oLogger = new Logger();

		$this->assertIsFloat(
			$oLogger->getSessionTimer(),
			'getSessionTimer'
		);
		$this->assertIsString(
			$oLogger->getSessionId(),
			'getSessionId'
		);
		$this->assertIsFloat(
			$oLogger->getTimer(),
			'getTimer'
		);
	}

	public function testLevels()
	{
		$oLogger = new Logger();

		$this->assertTrue(
			$oLogger->checkLevel(self::L_ERROR, self::LM_PROBLEMS),
			'checkLevel(L_ERROR, LM_PROBLEMS)'
		);
		$this->assertFalse(
			$oLogger->checkLevel(self::L_ERROR, self::LM_DEBUGGING),
			'checkLevel(L_ERROR, LM_DEBUGGING)'
		);

		$this->assertEquals(
			self::L_ERROR,
			$oLogger->getLevelInt(self::L_ERROR),
			'getLevelInt(L_ERROR)'
		);
		$this->assertEquals(
			self::L_ERROR,
			$oLogger->getLevelInt('error'),
			'getLevelInt(error)'
		);
		
		$this->assertEquals(
			'error',
			$oLogger->getLevelLabel(self::L_ERROR),
			'getLevelLabel(L_ERROR)'
		);
		$this->assertEquals(
			'error',
			$oLogger->getLevelLabel('error'),
			'getLevelLabel(error)'
		);

		$this->assertEquals(
			'Error',
			$oLogger->getLevelDisplay(self::L_ERROR),
			'getLevelDisplay(L_ERROR)'
		);
		$this->assertEquals(
			'Error',
			$oLogger->getLevelDisplay('error'),
			'getLevelDisplay(error)'
		);

		$this->assertEquals(
			self::LM_PROBLEMS,
			$oLogger->getLevelMaskInt(self::LM_PROBLEMS),
			'getLevelMaskInt(LM_PROBLEMS)'
		);
		$this->assertEquals(
			self::LM_PROBLEMS,
			$oLogger->getLevelMaskInt('problems'),
			'getLevelMaskInt(problems)'
		);
		$this->assertEquals(
			self::LM_PROBLEMS + self::LM_INFORMATION + self::L_DEBUG,
			$oLogger->getLevelMaskInt('debug'),
			'getLevelMaskInt(debug)'
		);
		$this->assertEquals(
			self::L_DEBUG,
			$oLogger->getLevelMaskInt('=debug'),
			'getLevelMaskInt(=debug)'
		);
		$this->assertEquals(
			self::L_DEBUG,
			$oLogger->getLevelMaskInt('debug='),
			'getLevelMaskInt(=debug)'
		);
		$this->assertEquals(
			$oLogger->getLevelMaskInt('info'),
			$oLogger->getLevelMaskInt('notice+'),
			'getLevelMaskInt(notice+)'
		);
		$this->assertEquals(
			$oLogger->getLevelMaskInt('info'),
			$oLogger->getLevelMaskInt('+notice'),
			'getLevelMaskInt(+notice)'
		);
		$this->assertEquals(
			$oLogger->getLevelMaskInt('detail'),
			$oLogger->getLevelMaskInt('notice++'),
			'getLevelMaskInt(notice++)'
		);
		$this->assertEquals(
			$oLogger->getLevelMaskInt('debug'),
			$oLogger->getLevelMaskInt('notice+++'),
			'getLevelMaskInt(notice+++)'
		);
		$this->assertEquals(
			$oLogger->getLevelMaskInt('detail'),
			$oLogger->getLevelMaskInt('notice+2'),
			'getLevelMaskInt(notice+2)'
		);
		$this->assertEquals(
			$oLogger->getLevelMaskInt('debug'),
			$oLogger->getLevelMaskInt('+3notice'),
			'getLevelMaskInt(+3notice)'
		);
		$this->assertEquals(
			$oLogger->getLevelMaskInt('warning'),
			$oLogger->getLevelMaskInt('notice-'),
			'getLevelMaskInt(notice-)'
		);
		$this->assertEquals(
			$oLogger->getLevelMaskInt('error'),
			$oLogger->getLevelMaskInt('--notice'),
			'getLevelMaskInt(--notice)'
		);
		$this->assertEquals(
			$oLogger->getLevelMaskInt('critical'),
			$oLogger->getLevelMaskInt('notice---'),
			'getLevelMaskInt(notice---)'
		);
		$this->assertEquals(
			$oLogger->getLevelMaskInt('error'),
			$oLogger->getLevelMaskInt('-2notice'),
			'getLevelMaskInt(-2notice)'
		);
		$this->assertEquals(
			$oLogger->getLevelMaskInt('critical'),
			$oLogger->getLevelMaskInt('notice-3'),
			'getLevelMaskInt(notice-3)'
		);
		$this->assertEquals(
			self::LM_NONE,
			$oLogger->getLevelMaskInt('notice-10'),
			'getLevelMaskInt(notice-10)'
		);
		$this->assertEquals(
			self::LM_ALL,
			$oLogger->getLevelMaskInt('notice+10'),
			'getLevelMaskInt(notice+10)'
		);

		$this->assertEquals(
			self::LM_PROBLEMS + self::L_DEBUG,
			$oLogger->getLevelMaskInt('problems, =debug'),
			'getLevelMaskInt(problems, =debug)'
		);
		$this->assertEquals(
			self::LM_PROBLEMS + self::L_DEBUG,
			$oLogger->getLevelMaskInt('problems , ; =debug ; , '),
			'getLevelMaskInt(problems , ; =debug ; , )'
		);
		
		$this->assertEquals(
			array('problems'),
			$oLogger->getLevelMaskLabel(self::LM_PROBLEMS),
			'getLevelMaskLabel(LM_PROBLEMS)'
		);
		$this->assertEquals(
			array('problems'),
			$oLogger->getLevelMaskLabel('problems'),
			'getLevelMaskLabel(problems)'
		);
		$this->assertEquals(
			array('problems', 'debug'),
			$oLogger->getLevelMaskLabel(self::LM_PROBLEMS + self::L_DEBUG),
			'getLevelMaskLabel(LM_PROBLEMS + L_DEBUG)'
		);
		$this->assertEquals(
			array('problems', 'debug'),
			$oLogger->getLevelMaskLabel('problems, =debug'),
			'getLevelMaskLabel(problems, =debug)'
		);

		$this->assertEquals(
			'Problems',
			$oLogger->getLevelMaskDisplay(self::LM_PROBLEMS),
			'getLevelMaskDisplay(LM_PROBLEMS)'
		);
		$this->assertEquals(
			'Problems',
			$oLogger->getLevelMaskDisplay('problems'),
			'getLevelMaskDisplay(problems)'
		);
		$this->assertEquals(
			'Problems, Debug',
			$oLogger->getLevelMaskDisplay(self::LM_PROBLEMS + self::L_DEBUG),
			'getLevelMaskDisplay(LM_PROBLEMS + L_DEBUG)'
		);
		$this->assertEquals(
			'Problems, Debug',
			$oLogger->getLevelMaskDisplay('problems, =debug'),
			'getLevelMaskDisplay(problems, =debug)'
		);
	}

	public function testInvalidLevel_1()
	{
		$oLogger = new Logger();
		$this->expectException(
			\Useful\Exception::class,
			'getLevelInt(bad)'
		);
		$this->expectExceptionCode(
			1,
			'getLevelInt(bad)'
		);
		$oLogger->getLevelInt('bad');
	}
	public function testInvalidLevel_2()
	{
		$oLogger = new Logger();
		$this->expectException(
			\Useful\Exception::class,
			'getLevelInt(-1)'
		);
		$this->expectExceptionCode(
			1,
			'getLevelInt(-1)'
		);
		$oLogger->getLevelInt(-1);
	}
	public function testInvalidLevel_3()
	{
		$oLogger = new Logger();
		$this->expectException(
			\Useful\Exception::class,
			'getLevelMaskInt(bad)'
		);
		$this->expectExceptionCode(
			1,
			'getLevelMaskInt(bad)'
		);
		$oLogger->getLevelMaskInt('bad');
	}
	public function testInvalidLevel_4()
	{
		$oLogger = new Logger();
		$this->expectException(
			\Useful\Exception::class,
			'getLevelMaskInt(-1)'
		);
		$this->expectExceptionCode(
			1,
			'getLevelMaskInt(-1)'
		);
		$oLogger->getLevelMaskInt(-1);
	}

	public function testConfig()
	{
		$aOriginalConfig = array(
			'logs' => array(
				'test_log' => array(
					'mask' => 'info',
				),
			),
		);

		$oLogger = new Logger($aOriginalConfig);
		$aResultConfig = $oLogger->getConfig();
		$this->assertArrayHasKey(
			'logs',
			$aResultConfig,
			'constructor config has [logs]'
		);
		$this->assertArrayHasKey(
			'test_log',
			$aResultConfig['logs'],
			'constructor config has [logs][test_log]'
		);
		$this->assertArrayHasKey(
			'mask',
			$aResultConfig['logs']['test_log'],
			'constructor config has [logs][test_log][mask]'
		);
		$this->assertEquals(
			(self::L_INFO << 1) - 1,
			$aResultConfig['logs']['test_log']['mask'],
			'constructor config mask transform'
		);

		$oLogger = new Logger();
		$aConfig = $oLogger->setConfig($aOriginalConfig);
		$aResultConfig = $oLogger->getConfig();
		$this->assertArrayHasKey(
			'logs',
			$aResultConfig,
			'setConfig() config has [logs]'
		);
		$this->assertArrayHasKey(
			'test_log',
			$aResultConfig['logs'],
			'setConfig() config has [logs][example_log]'
		);
		$this->assertArrayHasKey(
			'mask',
			$aResultConfig['logs']['test_log'],
			'setConfig() config has [logs][test_log][mask]'
		);
		$this->assertEquals(
			(self::L_INFO << 1) - 1,
			$aResultConfig['logs']['test_log']['mask'],
			'setConfig() config mask transform'
		);
	}

	public function testLogConfig()
	{
		$aOriginalConfig = array(
			'logs' => array(
				'test_log' => array(
					'mask' => 'debug',
					'writers' => array('csv'),
				),
			),
		);
		$oLogger = new Logger($aOriginalConfig);

		$this->assertEquals(
			array(
				'mask' => (self::L_DEBUG << 1) - 1,
				'writers' => array('csv'),
				'log' => 'test_log',
			),
			$oLogger->getLogConfig('test_log'),
			'getLogConfig()'
		);

		$this->assertEquals(
			(self::L_DEBUG << 1) - 1,
			$oLogger->getLogLevelMask('test_log'),
			'getLogLevelMask()'
		);

		$oLogger->setLogConfig(
			'test_log',
			array(
				'mask' => 'debug2',
			)
		);
		$this->assertEquals(
			array(
				'mask' => (self::L_DEBUG2 << 1) - 1,
				'writers' => array('csv'),
				'log' => 'test_log',
			),
			$oLogger->getLogConfig('test_log'),
			'setLogConfig() #1/getLogConfig()'
		);
		$this->assertEquals(
			(self::L_DEBUG2 << 1) - 1,
			$oLogger->getLogLevelMask('test_log'),
			'setLogConfig() #1/getLogLevelMask()'
		);

		$oLogger->setLogConfig(
			'test_log',
			array(
				'writers' => array('display'),
			)
		);
		$this->assertEquals(
			array(
				'mask' => (self::L_DEBUG2 << 1) - 1,
				'writers' => array('display'),
				'log' => 'test_log',
			),
			$oLogger->getLogConfig('test_log'),
			'setLogConfig() #2/getLogConfig()'
		);

		$oLogger->setLogLevelMask('test_log', 'error');
		$this->assertEquals(
			(self::L_ERROR << 1) - 1,
			$oLogger->getLogLevelMask('test_log'),
			'setLogLevelMask()/getLogLevelMask()'
		);
		$this->assertEquals(
			array(
				'mask' => (self::L_ERROR << 1) - 1,
				'writers' => array('display'),
				'log' => 'test_log',
			),
			$oLogger->getLogConfig('test_log'),
			'setLogLevelMask()/getLogConfig()'
		);
	}

	public function testWriterConfig()
	{
		$aOriginalConfig = array(
			'logs' => array(
				'test_log' => array(
					'mask' => 'info',
					'writers' => array('display'),
				),
			),
			'writers' => array(
				'display' => array(
					'html' => false,
				),
			),
		);
		$oLogger = new Logger($aOriginalConfig);

		$this->assertEquals(
			array(
				'enabled' => false,
				'mask' => self::LM_ALL,
				'html' => false,
			),
			$oLogger->getWriterConfig('display'),
			'getWriterConfig()'
		);

		$oLogger->setWriterConfig(
			'display',
			array(
				'html' => true,
			)
		);
		$this->assertEquals(
			array(
				'enabled' => false,
				'mask' => self::LM_ALL,
				'html' => true,
			),
			$oLogger->getWriterConfig('display'),
			'setWriterConfig()'
		);
	}

	public function testGetWriter()
	{
		$oMockWriter = new MockWriter();
		$aOriginalConfig = array(
			'logs' => array(
				'test_log' => array(
					'mask' => 'info',
					'writers' => array('display'),
				),
			),
			'writers' => array(
				'display' => array(
					'html' => false,
				),
				'mock' => array(
					'enabled' => true,
					'queue' => false,
					'obj' => $oMockWriter,
				),
			),
		);
		$oLogger = new Logger($aOriginalConfig);

		$oWriter = $oLogger->getWriter('display');
		$this->assertInstanceOf(
			\Useful\Logger\AbstractWriter::class,
			$oWriter,
			'getWriter(display)'
		);
		$this->assertInstanceOf(
			\Useful\Logger\Writer\Display::class,
			$oWriter,
			'getWriter(display)'
		);

		$oWriter = $oLogger->getWriter('mock');
		$this->assertEquals(
			$oMockWriter,
			$oWriter,
			'getWriter(mock)'
		);
	}

	public function testLogClass()
	{
		$aOriginalConfig = array(
			'logs' => array(
				'test_log' => array(
					'mask' => 'info',
					'writers' => array('display'),
				),
			),
			'default_log_config' => array(
				'mask' => 'error',
				'writers' => array('csv'),
			),
		);
		$oLogger = new Logger($aOriginalConfig);

		$oLog = $oLogger->getLog('test_log');
		$this->assertLogInstance($oLog, $oLogger);
	}

	public function testLogFactoryClass()
	{
		$aOriginalConfig = array(
			'logs' => array(
				'test_log' => array(
					'mask' => 'info',
					'writers' => array('display'),
				),
			),
			'default_log_config' => array(
				'mask' => 'error',
				'writers' => array('csv'),
			),
		);
		$oLogger = new Logger($aOriginalConfig);

		$oLogFactory = new \Useful\Logger\LogFactory();
		$this->assertInstanceOf(
			\Useful\Logger\LogFactory::class,
			$oLogFactory,
			'new LogFactory()'
		);

		$oLogFactory->setLogger($oLogger);
		$this->assertEquals(
			$oLogger,
			$oLogFactory->getLogger(),
			'LogFactory->getLogger()'
		);

		$oLog = $oLogFactory->createLog('test_log');
		$this->assertLogInstance($oLog, $oLogger);
	}

	protected function assertLogInstance($oLog, $oLogger)
	{
		$this->assertInstanceOf(
			\Useful\Logger\Log::class,
			$oLog,
			'getLog()'
		);
		
		$this->assertEquals(
			array(
				'mask' => (self::L_INFO << 1) - 1,
				'writers' => array('display'),
				'log' => 'test_log',
			),
			$oLog->getConfig(),
			'Log::getConfig()'
		);

		$this->assertEquals(
			(self::L_INFO << 1) - 1,
			$oLog->getLevelMask(),
			'Log::getLevelMask()'
		);

		$oLog->setConfig(array(
			'writers' => array('display', 'csv'),
		));
		$this->assertEquals(
			array(
				'mask' => (self::L_INFO << 1) - 1,
				'writers' => array('display', 'csv'),
				'log' => 'test_log',
			),
			$oLog->getConfig('test_log'),
			'Log::setConfig()'
		);

		$oLog->setLevelMask('debug');
		$this->assertEquals(
			(self::L_DEBUG << 1) - 1,
			$oLog->getLevelMask(),
			'Log::setLevelMask()/Log::getLevelMask()'
		);
		$this->assertEquals(
			array(
				'mask' => (self::L_DEBUG << 1) - 1,
				'writers' => array('display', 'csv'),
				'log' => 'test_log',
			),
			$oLog->getConfig('test_log'),
			'Log::setLevelMask()/Log::getConfig()'
		);

		$this->assertEquals(
			$oLogger->getLogConfig('test_log'),
			$oLog->getConfig(),
			'og::getConfig() equals Logger::getLogCOnfig'
		);

		$oLog = $oLogger->getLog('other_log');
		$this->assertInstanceOf(
			\Useful\Logger\Log::class,
			$oLog,
			'getLog(other_log)'
		);

		$aLogConfig = $oLogger->getLogConfig('other_log');
		$this->assertEquals(
			array(
				'mask' => (self::L_ERROR << 1) - 1,
				'writers' => array('csv'),
				'log' => 'other_log',
			),
			$aLogConfig,
			'getLogConfig(other_log)'
		);
		$this->assertEquals(
			$aLogConfig,
			$oLog->getConfig(),
			'Log::getConfig()'
		);
	}

	public function testWrite()
	{
		$oMockWriter = new MockWriter();
		$aOriginalConfig = array(
			'logs' => array(
				'test_log' => array(
					'mask' => 'info',
					'writers' => array('mock'),
				),
			),
			'writers' => array(
				'mock' => array(
					'enabled' => true,
					'queue' => false,
					'obj' => $oMockWriter,
				),
			),
		);
		$oLogger = new Logger($aOriginalConfig);

		$aLogConfig = $oLogger->getLogConfig('test_log');

		$aWriters = $oLogger->write_prepWriters($aLogConfig, self::L_ERROR);
 		$this->assertArrayHasKey(
 			'mock',
 			$aWriters,
 			'write_prepWriters()'
 		);

		$aMessage = $oLogger->write_prepMessage($aLogConfig, self::L_ERROR, 'test', null, null);
		$this->assertLoggerMessage($aMessage, 'write_prepWriters()');

		$oLogger->write('test_log', 'error', 'test error message');		
		$aProcessed = $oMockWriter->getProcessed();
		$this->assertCount(1, $aProcessed, 'write()');
		$this->assertMockWriterResult('test_log', 1, $aProcessed[0], 'write()');
	}

	public function testCallbackWriter()
	{
		$aProcessed = array();
		$oLogger = new Logger(array(
			'writers' => array(
				'callback' => array(
					'enabled' => true,
					'call' => function($sQueue, $aWriterConfig, $aMessageList) use (&$aProcessed) {
						$aProcessed[] = array($sQueue, $aMessageList);
					}
				),
			),
			'logs' => array(
				'test_log' => array(
					'mask' => 'debug',
					'writers' => array('callback'),
				),
			),			
		));
		$oLogger->write('test_log', 'error', 'test message');
		$this->assertCount(
			0,
			$aProcessed,
			'queued write() 1'
		);
		$oLogger->write('test_log', 'error', 'test message 2');
		$this->assertCount(
			0,
			$aProcessed,
			'queued write() 2'
		);
		$oLogger->flush();
		$this->assertCount(
			1,
			$aProcessed,
			'flush()'
		);
		$this->assertMockWriterResult(
			null,
			2,
			$aProcessed[0],
			'flush()'
		);
		$aProcessed = array();
	}

	public function testDisplayWriterText()
	{
		$oLogger = new Logger(array(
			'writers' => array(
				'display' => array(
					'enabled' => true,
				),
			),
			'logs' => array(
				'test_log' => array(
					'mask' => 'debug',
					'writers' => array('display'),
				),
			),
		));
		$this->expectOutputRegex(
			'/^' . date('Y-m-d H:i') . ':\d\d \(\d+\.\d\d\) \[test_log\] Error - test message\n$/',
			'write() display outputs message'
		);
		$oLogger->write('test_log', 'error', 'test message');
	}

	public function testDisplayWriterHtml()
	{
		$oLogger = new Logger(array(
			'writers' => array(
				'display' => array(
					'enabled' => true,
					'html' => true,
				),
			),
			'logs' => array(
				'test_log' => array(
					'mask' => 'debug',
					'writers' => array('display'),
				),
			),
		));
		$this->expectOutputRegex(
			'@^<div style="float: none; clear: both; display: block; "><div>' . date('Y-m-d H:i') . ':\d\d \(\d+\.\d\d\) \[test_log\] Error - <span style="font-weight: bold; color: red; ">test message</span></div></div>\n$@',
			'write() display outputs html message'
		);
		$oLogger->write('test_log', 'error', 'test message');
	}

	public function testFileWriters()
	{
		$this->sFileWriterDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_php_useful_logger';
		foreach (
			[
				'csv' => '/error.*test message/s',
				'file' => '/Error.*test message/s',
			]
			as $sWriter => $sRegex
		) {
			$sExpectedFile = $this->sFileWriterDir . DIRECTORY_SEPARATOR . $sWriter . '_test_log_' . date('Ymd') . '.log';
			$oLogger = new Logger(array(
				'writers' => array(
					$sWriter => array(
						'enabled' => true,
						'path' => $this->sFileWriterDir . DIRECTORY_SEPARATOR . $sWriter . '_{log}_{date}.log',
					),
				),
				'logs' => array(
					'test_log' => array(
						'mask' => 'debug',
						'writers' => array($sWriter),
					),
				),
			));

			$oLogger->write('test_log', 'error', 'test message');
			$this->assertFileNotExists($sExpectedFile, 'write() no output until flush');

			$oLogger->flush();
			$this->assertFileExists($sExpectedFile, 'flush() creates log file');
			$sContent = file_get_contents($sExpectedFile);
			$this->assertRegExp(
				$sRegex,
				$sContent,
				'file content'
			);
		}
	}

	public function testRedirectWriter()
	{
		$oMockWriter1 = new MockWriter();
		$oMockWriter2 = new MockWriter();
		$oLogger = new Logger(array(
			'writers' => array(
				'redirect' => array(
					'enabled' => true,
				),
				'mock1' => array(
					'enabled' => true,
					'queue' => false,
					'obj' => $oMockWriter1,
				),
				'mock2' => array(
					'enabled' => true,
					'queue' => false,
					'obj' => $oMockWriter2,
				),
			),
			'logs' => array(
				'test_log' => array(
					'mask' => 'debug',
					'writers' => array(
						'redirect' => array(
							'redirect_logs' => array('test_log_b', 'test_log_c'),
						),
					),
				),
				'test_log_b' => array(
					'mask' => 'error',
					'writers' => array('mock1'),
				),
				'test_log_c' => array(
					'mask' => 'debug',
					'writers' => array('mock2'),
				),
			),
		));

		$oLogger->write('test_log', 'error', 'test message');

		$aProcessed = $oMockWriter1->getProcessed();
		$this->assertCount(
			1,
			$aProcessed,
			'write() redirect 1'
		);
		$this->assertMockWriterResult(
			'test_log_b',
			1,
			$aProcessed[0],
			'write() redirect 1'
		);

		$aProcessed = $oMockWriter2->getProcessed();
		$this->assertCount(
			1,
			$aProcessed,
			'write() redirect 2'
		);
		$this->assertMockWriterResult(
			'test_log_c',
			1,
			$aProcessed[0],
			'write() redirect 1'
		);
	}

	public function tearDown(): void
	{
		if (isset($this->sFileWriterDir)) {
			foreach (scandir($this->sFileWriterDir) as $sFile) {
				if (in_array($sFile, array('.', '..'))) {
					continue;
				}
				unlink($this->sFileWriterDir . DIRECTORY_SEPARATOR . $sFile);
			}
			rmdir($this->sFileWriterDir);
		}
	}

	
	public function testTraceWriter()
	{
		$oLogger = new Logger(array(
			'writers' => array(
				'trace' => array(
					'enabled' => true,
				),
			),
			'logs' => array(
				'test_log' => array(
					'mask' => 'debug',
					'writers' => array('trace'),
				),
			),
		));

		$oLogger->write('test_log', 'error', 'test message');
		$oLogger->write('test_log', 'error', 'test message 2');

		$this->expectOutputRegex(
			'/^\n<!--\n' . date('Y-m-d H:i') . ':\d\d \(\d+\.\d\d\) \[test_log\] Error - test message\n' . date('Y-m-d H:i') . ':\d\d \(\d+\.\d\d\) \[test_log\] Error - test message 2\n-->\n$/s',
			'flush() trace output'
		);
		$oLogger->flush();
	}

	protected function assertMockWriterResult($sExpectedQueue, $iExpectedMessageCount, $aProcessed, $sAssertMessage)
	{
		list($sQueue, $aMessageList) = $aProcessed;
		if ($sExpectedQueue === null) {
			$this->assertNull($sQueue, $sAssertMessage);
		}
		else {
			$this->assertEquals($sExpectedQueue, $sQueue, $sAssertMessage);
		}
		$this->assertIsArray($aMessageList, $sAssertMessage);
		$this->assertCount($iExpectedMessageCount, $aMessageList, $sAssertMessage);
		for ($i = 0; $i < $iExpectedMessageCount; $i++) {
	 		$this->assertArrayHasKey($i, $aMessageList, $sAssertMessage);
			$this->assertLoggerMessage($aMessageList[$i], $sAssertMessage);
		}
	}

	protected function assertLoggerMessage($aMessage, $sAssertMessage)
	{
		$this->assertIsArray($aMessage, $sAssertMessage);
 		$this->assertArrayHasKey('log', $aMessage, $sAssertMessage);
 		$this->assertArrayHasKey('time', $aMessage, $sAssertMessage);
 		$this->assertArrayHasKey('ftime', $aMessage, $sAssertMessage);
 		$this->assertArrayHasKey('level', $aMessage, $sAssertMessage);
 		$this->assertArrayHasKey('msg', $aMessage, $sAssertMessage);
 		$this->assertArrayHasKey('data', $aMessage, $sAssertMessage);
 		$this->assertArrayHasKey('timer', $aMessage, $sAssertMessage);
		$this->assertEquals('test_log', $aMessage['log'], $sAssertMessage);
		$this->assertIsInt($aMessage['time'], $sAssertMessage);
		$this->assertIsFloat($aMessage['ftime'], $sAssertMessage);
		$this->assertIsInt($aMessage['level'], $sAssertMessage);
		$this->assertIsString($aMessage['msg'], $sAssertMessage);
		$this->assertNull($aMessage['data'], $sAssertMessage);
		$this->assertNull($aMessage['timer'], $sAssertMessage);
	}
}
