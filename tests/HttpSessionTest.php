<?php

use PHPUnit\Framework\TestCase;

class HttpSessionTest extends TestCase
{
	public function testCreate()
	{
		$oDate = new \Useful\HttpSession(array(
			'url' => 'https://www.example.net/path/to/file.ext?foo=bar&foo=baz&bar=&bat&=urp'
		));
		$aOptions = $oDate->getParams();
		$this->assertEquals(true, $aOptions['ssl']);
		$this->assertEquals('www.example.net', $aOptions['host']);
		$this->assertEquals('/path/to/file.ext', $aOptions['path']);
		$this->assertEquals('foo=bar&foo=baz&bar=&bat&=urp', $aOptions['query']);
    }
}