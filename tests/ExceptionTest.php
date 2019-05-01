<?php

use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
	public function testException()
	{
		$e = new \Useful\Exception('test', 2);
		$this->assertInstanceOf('\Useful\Exception', $e);
		$this->assertInstanceOf('\Exception', $e);
		$this->assertEquals('test', $e->getMessage());
		$this->assertEquals(2, $e->getCode());

		$e1 = new \Exception('first');

		$e = new \Useful\Exception('test', 2, $e1, 'data');
		$this->assertEquals($e1, $e->getPreviousException());
		$this->assertEquals('data', $e->getData());
		$this->assertEquals(null, $e->getData('foo'));

		$e = new \Useful\Exception(
			'test',
			2,
			null,
			array(
				'exception' => $e1,
			)
		);
		$this->assertEquals($e1, $e->getPreviousException());
		$e = new \Useful\Exception(
			'test',
			2,
			null,
			array(
				'error' => $e1,
			)
		);
		$this->assertEquals($e1, $e->getPreviousException());
		$e = new \Useful\Exception(
			'test',
			2,
			array(
				'exception' => $e1,
			)
		);
		$this->assertEquals($e1, $e->getPreviousException());

		$e = new \Useful\Exception(
			'test',
			2,
			array(
				'exception' => $e1,
				'foo' => 'bar',
			)
		);
		$this->assertEquals($e1, $e->getPreviousException());
		$this->assertEquals('bar', $e->getData('foo'));
    }
}
