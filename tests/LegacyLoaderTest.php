<?php

use PHPUnit\Framework\TestCase;

class LegacyLoaderTest extends TestCase
{
	public function testClassLoader()
	{
		require_once(__DIR__ . '/../src/Useful_Legacy/Loader.php');
		
		$sRootClassDir = __DIR__ . '/data/vendorlib/Foo';
		$sCacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpusefultest';
		Useful_Legacy_Loader::registerNamespace('\Foo', $sRootClassDir, $sCacheDir);
		$aRegistry = Useful_Legacy_Loader::getRegisteredNamespaces();
		$this->assertEquals(
			array(
				'Foo_' => array(
					'prefix' => 'Foo_',
					'ns' => 'Foo',
					'path' => $sRootClassDir . '/',
					'name' => '%s.php',
					'lc' => false,
					'cache' => $sCacheDir . '/',
				),
			),
			$aRegistry
		);

		Useful_Legacy_Loader::clearCache('Foo');
		Useful_Legacy_Loader::registerSplAutoloader();
		$oBat = new Foo_Bar_Baz_Bat();
		$this->assertEquals('ok', $oBat->test());
		$e = null;
		try {
			$oBat->error();
		}
		catch (Exception $e) {
			/* */
		}
		$this->assertInstanceOf('Exception', $e);
		$this->assertEquals('Foo_Bar_Baz_Exception', get_class($e));

		Useful_Legacy_Loader::clearCache('Foo');
		$this->assertEquals(false, is_dir($sCacheDir));
    }

    public function testAutoloader()
    {
		require_once(__DIR__ . '/../src/legacy_autoloader.php');
		Useful_Legacy_Loader::clearCache();
		
		$oDate = Useful_Date::create('1999-12-31');
		$this->assertEquals('1999', $oDate->year());

		Useful_Legacy_Loader::clearCache();
		$sDir = dirname(__DIR__) . '/legacy_src';
		foreach (scandir($sDir) as $sFile) {
			if (in_array($sFile, array('.', '..'))) {
				continue;
			}
			unlink($sDir . DIRECTORY_SEPARATOR . $sFile);
			if (substr($sFile, 0, 1) != '.') {
				$this->fail('Useful_Legacy_Loader::clearCache() did not remove file ' . $sFile);
			}
		}
		rmdir($sDir);
    }
}
