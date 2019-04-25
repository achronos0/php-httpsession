<?php

/*==NAMESPACE*/
namespace Foo\Bar_Baz;
if (!class_exists('Foo\Bar_Baz\Exception', false)) {
	class Exception extends \Exception {};
}
/*NAMESPACE==*/

class Bat
{
	public function test() {
		return 'ok';
	}

	public function error() {
		throw new Exception('test');
	}
}
