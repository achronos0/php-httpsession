<?php

use PHPUnit\Framework\TestCase;

require_once('BaseConversionTest.php');

class CsvTest extends BaseConversionTest
{
	public $sTestClass = 'Useful\\Csv';
	public $aConversionTests = array(
		array(
			'name' => 'basic',
			'content' => '
				a,b,c
				1,2,3
				4,5,6
			',
			'data' => array(
				array(
					'a' => '1',
					'b' => '2',
					'c' => '3',
				),
				array(
					'a' => '4',
					'b' => '5',
					'c' => '6',
				),
			),
		),
		array(
			'name' => 'quotes',
			'content' => '
				a,b,c
				"a a, a","b, b, b","c
				c, c"
			',
			'data' => array(
				array(
					'a' => 'a a, a',
					'b' => 'b, b, b',
					'c' => "c\nc, c",
				),
			),
		),
		array(
			'name' => 'unquoted contains quotes',
			'run' => 'pr',
			'content' => '
				a,b,c
				quote in " middle,quote at end",two "" quotes in middle
			',
			'data' => array(
				array(
					'a' => 'quote in " middle',
					'b' => 'quote at end"',
					'c' => 'two "" quotes in middle',
				),
			),
		),
		array(
			'name' => 'embedded quotes',
			'content' => '
				a,b,c
				"quoted text, ""with quotes"" in the middle","""quotes at start"", in quotes","at the end, ""more quotes"""
			',
			'data' => array(
				array(
					'a' => 'quoted text, "with quotes" in the middle',
					'b' => '"quotes at start", in quotes',
					'c' => 'at the end, "more quotes"',
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
	* @covers Useful\Csv::parse
	*/
	public function testConversionsParse()
	{
		$this->runTestConversions_parse();
	}

	/**
	* @covers Useful\Csv::read
	* @depends testConversionsParse
	*/
	public function testConversionsRead()
	{
		$this->runTestConversions_read();
	}

	/**
	* @covers Useful\Csv::generate
	*/
	public function testConversionsGenerate()
	{
		$this->runTestConversions_generate();
	}

	/**
	* @covers Useful\Csv::write
	* @depends testConversionsGenerate
	*/
	public function testConversionsWrite()
	{
		$this->runTestConversions_write();
	}
}