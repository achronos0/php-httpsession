<?php

use PHPUnit\Framework\TestCase;

class DateTest extends TestCase
{
	protected $aConversionTests = array(
		array(
			'm' => 'Y-m-d',
			'i' => '2009-12-31',
			'o' => '2009-12-31 00:00:00 +0000',
		),
		array(
			'm' => 'Y-m-d H:i:s',
			'i' => '2009-12-31 12:13:14 +1',
			'o' => '2009-12-31 12:13:14 +0100',
		),
	);
	public function testCreate()
	{
		// Test conversions
		foreach ($this->aConversionTests as $aTest) {
			$oDate = \Useful\Date::create($aTest['i']);
			$this->assertEquals($aTest['o'], $oDate->format('Y-m-d H:i:s O'), $aTest['m']);
		}
    }

    /**
     * @expectedException \Useful\Exception
     */
	public function testCreateMalformed()
	{
		\Useful\Date::create('foobar');
	}

	protected function setUp()
	{
		date_default_timezone_set('UTC');
	}
}
