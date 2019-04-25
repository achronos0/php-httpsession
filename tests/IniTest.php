<?php

use PHPUnit\Framework\TestCase;

require_once('BaseConversionTest.php');

class IniTest extends BaseConversionTest
{
	public $sTestClass = 'Useful\\Ini';
	public $aConversionTests = array(
		array(
			'name' => 'basic',
			'content' => '
				a=b
			',
			'data' => array(
				'a' => 'b',
			),
		),
		array(
			'name' => 'hierarchy',
			'content' => '
				a.a=1
				a.b=2
				b=3
			',
			'data' => array(
				'a' => array(
					'a' => 1,
					'b' => 2,
				),
				'b' => 3,
			),
		),
		array(
			'name' => 'values',
			'content' => '
				a=text
				b=2
				c=3.14
				d=YES
				e=NO
				f=NOTHING
			',
			'data' => array(
				'a' => 'text',
				'b' => 2,
				'c' => 3.14,
				'd' => true,
				'e' => false,
				'f' => null,
			),
		),
		array(
			'name' => 'quotes',
			'content' => '
				a=" must quote"
				b="must quote "
				c="must
				quote"
			',
			'data' => array(
				'a' => ' must quote',
				'b' => 'must quote ',
				'c' => "must\nquote",
			),
		),
		array(
			'name' => 'list values',
			'run' => 'pr',
			'content' => '
				a=[one "two two" 3 YES NO NOTHING]
			',
			'data' => array(
				'a' => array( 'one', 'two two', 3, true, false, null ),
			),
		),
		array(
			'name' => 'comments parsing',
			'run' => 'pr',
			'content' => '
				a=1
				// b=2
				#c=3
				d=4
				e=// 5
				f=6
				/*
				g=7
				h=8
				*/
				i=9
				j=/*10*/


				=
			',
			'data' => array(
				'a' => 1,
				'd' => 4,
				'e' => '// 5',
				'f' => 6,
				'i' => 9,
				'j' => '/*10*/',
			),
		),
		array(
			'name' => 'sections parsing',
			'run' => 'pr',
			'content' => '
				[a]
				a=1
				b=2
				[a.c]
				a=4
				[general]
				b=3
			',
			'data' => array(
				'a' => array(
					'a' => 1,
					'b' => 2,
					'c' => array(
						'a' => 4,
					),
				),
				'b' => 3,
			),
		),
		array(
			'name' => 'list values parsing',
			'run' => 'pr',
			'content' => '
				a=[ one two three ]
				b={ one, two ,, three }
				c=[ 1 2 3 ]
				d=[
					one
					two
					three
				]
				e=[]
				f={ "one one", two, "three three" }
				g={ one:1 two=2, three : 3, 4 }
				h={one:"1 and 1", two : 2, three="3 and 3" }
				i={one:"1 and 1",
					two : 2,
							three="3 and 3" }
			',
			'data' => array(
				'a' => array( 'one', 'two', 'three' ),
				'b' => array( 'one', 'two', 'three' ),
				'c' => array( 1, 2, 3 ),
				'd' => array( 'one', 'two', 'three' ),
				'e' => array( ),
				'f' => array( 'one one', 'two', 'three three' ),
				'g' => array(
					'one' => 1,
					'two' => 2,
					'three' => 3,
					4
				),
				'h' => array(
					'one' => '1 and 1',
					'two' => 2,
					'three' => '3 and 3',
				),
				'i' => array(
					'one' => '1 and 1',
					'two' => 2,
					'three' => '3 and 3',
				),
			),
		),
		array(
			'name' => 'quotes parsing',
			'run' => 'pr',
			'content' => '
				a=\' single quotes \'
				b="double quotes"
				c=\'\'
				d=""
				e=\'span
				multiple
				lines\'
				f=\'multiple lines
				with \'embedded\' quotes\'
				g="double-quotes, multiple lines
				with "embedded" quotes"
				h="c-escapes: tab(\t) newline(\\n)"
			',
			'data' => array(
				'a' => ' single quotes ',
				'b' => 'double quotes',
				'c' => '',
				'd' => '',
				'e' => "span\nmultiple\nlines",
				'f' => "multiple lines\nwith 'embedded' quotes",
				'g' => "double-quotes, multiple lines\nwith \"embedded\" quotes",
				'h' => "c-escapes: tab(\t) newline(\n)",
			),
		),
		array(
			'name' => 'append values parsing',
			'run' => 'pr',
			'content' => '
				a[]=1
				a[]=2
				b=1
				b[]=2
				[c]
				=1
				=2
			',
			'data' => array(
				'a' => array( 1, 2 ),
				'b' => array( 1, 2 ),
				'c' => array( 1, 2 ),
			),
		),
		array(
			'name' => 'append list values parsing',
			'run' => 'pr',
			'content' => '
				a[]={ a:1, b:2, c:3 }
				a[]={ a:4, b:5, c:6 }
				[a]
				={ a:7, b:8, c:9 }
			',
			'data' => array(
				'a' => array(
					array(
						'a' => 1,
						'b' => 2,
						'c' => 3,
					),
					array(
						'a' => 4,
						'b' => 5,
						'c' => 6,
					),
					array(
						'a' => 7,
						'b' => 8,
						'c' => 9,
					),
				),
			),
		),
		array(
			'name' => 'merge values parsing',
			'run' => 'pr',
			'content' => '
				a=1
				a+=2
				a+=3
				[a]
				+=4
			',
			'data' => array(
				'a' => array( 1, 2, 3, 4 ),
			),
		),
		array(
			'name' => 'merge list values parsing',
			'run' => 'pr',
			'content' => '
				a={ a:1, b:2, c:3 }
				a+={ b:6, d:1 }
				[a]
				+={ c:9, e: 1 }
			',
			'data' => array(
				'a' => array(
					'a' => 1,
					'b' => 6,
					'c' => 9,
					'd' => 1,
					'e' => 1,
				),
			),
		),
		/*
		array(
			'name' => '',
			'run' => 'pr',
			'content' => '
			',
			'data' => array(
			),
		),
		*/
	);

	/**
	* @covers Useful\Ini::parse
	*/
	public function testConversionsParse()
	{
		$this->runTestConversions_parse();
	}

	/**
	* @covers Useful\Ini::read
	* @depends testConversionsParse
	*/
	public function testConversionsRead()
	{
		$this->runTestConversions_read();
	}

	/**
	* @covers Useful\Ini::generate
	*/
	public function testConversionsGenerate()
	{
		$this->runTestConversions_generate();
	}

	/**
	* @covers Useful\Ini::write
	* @depends testConversionsGenerate
	*/
	public function testConversionsWrite()
	{
		$this->runTestConversions_write();
	}
}
