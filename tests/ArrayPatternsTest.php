<?php

use PHPUnit\Framework\TestCase;

class ArrayPatternsTest extends TestCase
{
	public function testIsList()
	{
		$aTests = array(
			array(true,  array('a', 'b', 'c')),
			array(true,  array(1, 'b', 3.14, true, false, null)),
			array(false, array(1, 'b', 3.14, true, false, null, array())),
			array(false, array('a' => 1, 'b' => 2)),
			array(false, array(1, 2, 4=> 3)),
			array(false, array(1 => 1, 2, 3)),
			array(false, array(1, 2 => 2, 1 => 3)),
		);
		foreach ($aTests as $aTest) {
			list($bResult, $aData) = $aTest;
			$this->assertEquals($bResult, \Useful\ArrayPatterns::isList($aData));
		}
    }

	public function testMergeConfig()
	{
		$aTests = array(
			array(
				array(
					'a' => 1,
					'b' => array('b-new'),
					'c' => array(4, 5, 6),
					'd' => array(
						'e' => 2,
						'f' => 'f-old',
						'g' => 'g-new',
						'h' => array(
							'i' => 'i-new',
							'i2' => 'i2-new',
						),
						'j' => 'j-new',
					),
				),
				array(
					'a' => 1,
					'b' => 'b-old',
					'c' => array(1, 2, 3),
					'd' => array(
						'e' => 1,
						'f' => 'f-old',
						'g' => array(1, 2, 3),
						'h' => array(
							'i' => 'i-old',
						),
						'j' => array(
							'k' => 'k-old',
						),
					),
				),
				array(
					'b' => array('b-new'),
					'c' => array(4, 5, 6),
					'd' => array(
						'e' => 2,
						'g' => 'g-new',
						'h' => array(
							'i' => 'i-new',
							'i2' => 'i2-new',
						),
						'j' => 'j-new',
					),
				),
			),
		);
		foreach ($aTests as $aTest) {
			list($aResult, $aOld, $aNew) = $aTest;
			$this->assertEquals($aResult, \Useful\ArrayPatterns::mergeConfig($aOld, $aNew));
		}
	}
}
