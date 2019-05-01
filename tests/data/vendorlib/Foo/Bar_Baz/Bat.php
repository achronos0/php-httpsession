<?php

namespace Foo\Bar_Baz;

use Foo\FooClass, ArrayAccess;

class Bat extends AbstractBat implements InterfaceBat, \Countable
{
	public function test()
	{
		return 'ok';
	}

	public function getFoo()
	{
		return new FooClass('ok');
	}

	public function getFooStatic()
	{
		return FooClass::whoo('ok');
	}

	public function error()
	{
		throw new Exception('test');
	}

	public function count()
	{
		return 0;
	}
}
