<?php

use PHPUnit\Framework\TestCase;

use Useful\SequenceArray;

class SequenceArrayTest extends TestCase
{
	public function testCreate()
	{
		$oSeq = new SequenceArray();
		$this->assertInstanceof('\\Useful\\SequenceArray', $oSeq);
		$this->assertInstanceof('\\ArrayAccess', $oSeq);
		$this->assertInstanceof('\\Countable', $oSeq);
		$this->assertInstanceof('\\Traversable', $oSeq);
    }

    public function testAccess()
    {
		$oSeq = new SequenceArray();
		
		$oSeq->set('a', 1, 10);
		$oSeq->set('b', 2);
		$oSeq->set('c', 3, true);

		$this->assertEquals(true, $oSeq->has('a'));
		$this->assertEquals(true, $oSeq->has('b'));
		$this->assertEquals(true, $oSeq->has('c'));
		$this->assertEquals(false, $oSeq->has('d'));

		$this->assertEquals(true, isset($oSeq['a']));
		$this->assertEquals(true, isset($oSeq['b']));
		$this->assertEquals(true, isset($oSeq['c']));
		$this->assertEquals(false, isset($oSeq['d']));

		$this->assertEquals(1, $oSeq->get('a'));
		$this->assertEquals(2, $oSeq->get('b'));
		$this->assertEquals(3, $oSeq->get('c'));
		$this->assertEquals(null, $oSeq->get('d'));

		$this->assertEquals(1, $oSeq['a']);
		$this->assertEquals(2, $oSeq['b']);
		$this->assertEquals(3, $oSeq['c']);
		//$this->assertEquals(null, $oSeq['d']);

		$this->assertEquals(10, $oSeq->getOrder('a'));
		$this->assertEquals(11, $oSeq->getOrder('b'));
		$this->assertEquals(9, $oSeq->getOrder('c'));
		$this->assertEquals(null, $oSeq->getOrder('d'));

		$oSeq->set('a', 4, 20);
		$oSeq->set('d', 5, 30);

		$this->assertEquals(true, $oSeq->has('a'));
		$this->assertEquals(true, $oSeq->has('b'));
		$this->assertEquals(true, $oSeq->has('c'));
		$this->assertEquals(true, $oSeq->has('d'));

		$this->assertEquals(true, isset($oSeq['a']));
		$this->assertEquals(true, isset($oSeq['b']));
		$this->assertEquals(true, isset($oSeq['c']));
		$this->assertEquals(true, isset($oSeq['d']));

		$this->assertEquals(true, isset($oSeq['@a']));
		$this->assertEquals(true, isset($oSeq['@b']));
		$this->assertEquals(true, isset($oSeq['@c']));
		$this->assertEquals(true, isset($oSeq['@d']));

		$this->assertEquals(4, $oSeq->get('a'));
		$this->assertEquals(2, $oSeq->get('b'));
		$this->assertEquals(3, $oSeq->get('c'));
		$this->assertEquals(5, $oSeq->get('d'));

		$this->assertEquals(4, $oSeq['a']);
		$this->assertEquals(2, $oSeq['b']);
		$this->assertEquals(3, $oSeq['c']);
		$this->assertEquals(5, $oSeq['d']);

		$this->assertEquals(20, $oSeq->getOrder('a'));
		$this->assertEquals(11, $oSeq->getOrder('b'));
		$this->assertEquals(9, $oSeq->getOrder('c'));
		$this->assertEquals(30, $oSeq->getOrder('d'));

		$this->assertEquals(20, $oSeq['@a']);
		$this->assertEquals(11, $oSeq['@b']);
		$this->assertEquals(9, $oSeq['@c']);
		$this->assertEquals(30, $oSeq['@d']);

		$oSeq->set('a', 5);
		$this->assertEquals(5, $oSeq->get('a'));
		$this->assertEquals(20, $oSeq->getOrder('a'));

		$oSeq->set('a', 'string');
		$this->assertEquals('string', $oSeq->get('a'));
		$this->assertEquals('string', $oSeq['a']);
		$oSeq->set('a', 3.14);
		$this->assertEquals(3.14, $oSeq->get('a'));
		$this->assertEquals(3.14, $oSeq['a']);
		$oSeq->set('a', false);
		$this->assertEquals(false, $oSeq->get('a'));
		$this->assertEquals(false, $oSeq['a']);
		$oSeq->set('a', null);
		$this->assertEquals(null, $oSeq->get('a'));
		$this->assertEquals(null, $oSeq['a']);
		$this->assertEquals(true, $oSeq->has('a'));
		$this->assertEquals(true, isset($oSeq['a']));
		$oSeq->set('a', array('array'));
		$this->assertEquals(array('array'), $oSeq->get('a'));
		$this->assertEquals(array('array'), $oSeq['a']);
		$oObj = new StdClass();
		$oSeq->set('a', $oObj);
		$this->assertEquals($oObj, $oSeq->get('a'));
		$this->assertEquals($oObj, $oSeq['a']);

		$oSeq->remove('a');
		$this->assertEquals(false, $oSeq->has('a'));
		$this->assertEquals(false, isset($oSeq['a']));
		$this->assertEquals(null, $oSeq->get('a'));
		//$this->assertEquals(null, $oSeq['a']);

		unset($oSeq['b']);
		$this->assertEquals(false, $oSeq->has('b'));
		$this->assertEquals(false, isset($oSeq['b']));
		$this->assertEquals(null, $oSeq->get('b'));
		//$this->assertEquals(null, $oSeq['b']);

		$oSeq->remove('does-not-exist'); // no error
		unset($oSeq['also-does-not-exist']); // no error

		$oSeq['e'] = 'new';
		$this->assertEquals('new', $oSeq->get('e'));
		$this->assertEquals('new', $oSeq['e']);
		$this->assertEquals(true, $oSeq->has('e'));
		$this->assertEquals(true, isset($oSeq['e']));
		$this->assertEquals(31, $oSeq->getOrder('e'));

		$oSeq['@e'] = 40;
		$this->assertEquals(40, $oSeq->getOrder('e'));

		$oSeq['@f'] = 50;
		$this->assertEquals(true, $oSeq->has('f'));
		$this->assertEquals(null, $oSeq->get('f'));
		$this->assertEquals(50, $oSeq->getOrder('f'));

		$oSeq['g']['ga'] = 'autosubarray';
		$this->assertEquals(true, $oSeq->has('g'));
		$this->assertEquals(true, isset($oSeq['g']));
		$this->assertEquals(true, isset($oSeq['g']['ga']));
		$this->assertEquals('autosubarray', $oSeq['g']['ga']);
		$this->assertEquals(51, $oSeq->getOrder('g'));

		$oSeq->set(10, 'a');
		$this->assertEquals(true, $oSeq->has(10));
		$this->assertEquals(true, isset($oSeq[10]));
		$this->assertEquals('a', $oSeq->get(10));
		$this->assertEquals('a', $oSeq[10]);
		$this->assertEquals(52, $oSeq->getOrder(10));

		$oSeq->set(null, 'b');
		$this->assertEquals(true, $oSeq->has(11));
		$this->assertEquals(true, isset($oSeq[11]));
		$this->assertEquals('b', $oSeq->get(11));
		$this->assertEquals('b', $oSeq[11]);
		$this->assertEquals(53, $oSeq->getOrder(11));

		$oSeq[''] = 'empty string';
		$oSeq['@'] = 60;
		$this->assertEquals(true, $oSeq->has(''));
		$this->assertEquals('empty string', $oSeq->get(''));
		$this->assertEquals(60, $oSeq->getOrder(''));

		$oSeq[] = 'appended';
		$this->assertEquals(true, $oSeq->has(12));
		$this->assertEquals('appended', $oSeq->get(12));
		$this->assertEquals(61, $oSeq->getOrder(12));
    }

    public function testCount()
    {
		$oSeq = new SequenceArray();
		$this->assertEquals(0, $oSeq->count());
		$this->assertEquals(0, count($oSeq));

		$oSeq->set('a', 1);
		$oSeq->set('b', 2);
		$oSeq->set('c', 3);
		$this->assertEquals(3, $oSeq->count());
		$this->assertEquals(3, count($oSeq));

		$oSeq->set('c', 4);
		$oSeq->set('d', 5);
		$this->assertEquals(4, $oSeq->count());
		$this->assertEquals(4, count($oSeq));
    }

    public function testSetArray()
    {
		$oSeq = new SequenceArray();

		$aData = array(
			'a' => 1,
			'b' => array(2),
			10 => 'va',
			'vb',
		);

		$oSeq->setArray($aData);
		$this->assertEquals(4, $oSeq->count());
		$this->assertEquals(1, $oSeq->get('a'));
		$this->assertEquals(array(2), $oSeq->get('b'));
		$this->assertEquals('va', $oSeq->get(11));
		$this->assertEquals('vb', $oSeq->get(12));

		$oSeq = new SequenceArray($aData);
		$this->assertEquals(4, $oSeq->count());
		$this->assertEquals(1, $oSeq->get('a'));
		$this->assertEquals(array(2), $oSeq->get('b'));
		$this->assertEquals('va', $oSeq->get(11));
		$this->assertEquals('vb', $oSeq->get(12));

		$oSeq = new SequenceArray();

		$oSeq->setArray(array(
			'a' => 'va',
			'@a' => 10,
			'b' => array('vb'),
			'@b' => 20,
			'c' => 'vc',
			'@c' => 30,
			10 => 'vd',
			'@10' => 40,
		));
		$this->assertEquals(4, $oSeq->count());
		$this->assertEquals('va', $oSeq->get('a'));
		$this->assertEquals(array('vb'), $oSeq->get('b'));
		$this->assertEquals('vc', $oSeq->get('c'));
		$this->assertEquals('vd', $oSeq->get(10));
		$this->assertEquals(10, $oSeq->getOrder('a'));
		$this->assertEquals(20, $oSeq->getOrder('b'));
		$this->assertEquals(30, $oSeq->getOrder('c'));
		$this->assertEquals(40, $oSeq->getOrder(10));

		$oSeq->setArray(array(
			'a' => array(
				'order' => 100,
				'value' => 'va',
			),
			array(
				'key' => 'b',
				'order' => 110,
				'value' => array('vb'),
			),
			'ignore' => array(
				'key' => 'c',
				'order' => 120,
				'value' => array('value' => 'vc'),
			),
			10 => array(
				'order' => 130,
				'value' => 'vd',
			),
			array(
				'value' => 've',
			),
			'vf',
			array(
				'key' => 'd',
				'order' => 140,
				'value' => 1,
				'value2' => 2,
			),
		));
		$this->assertEquals(7, $oSeq->count());
		$this->assertEquals('va', $oSeq->get('a'));
		$this->assertEquals(array('vb'), $oSeq->get('b'));
		$this->assertEquals(array('value' => 'vc'), $oSeq->get('c'));
		$this->assertEquals('vd', $oSeq->get(10));
		$this->assertEquals('ve', $oSeq->get(11));
		$this->assertEquals('vf', $oSeq->get(12));
		$this->assertEquals(array( 'value' => 1, 'value2' => 2), $oSeq->get('d'));

		$this->assertEquals(100, $oSeq->getOrder('a'));
		$this->assertEquals(110, $oSeq->getOrder('b'));
		$this->assertEquals(120, $oSeq->getOrder('c'));
		$this->assertEquals(130, $oSeq->getOrder(10));
		$this->assertEquals(131, $oSeq->getOrder(11));
		$this->assertEquals(132, $oSeq->getOrder(12));
		$this->assertEquals(140, $oSeq->getOrder('d'));
    }

    public function testGetArray()
    {
		$oSeq = new SequenceArray();

		$oSeq->setArray(array(
			'a' => 'va',
			'@a' => 10,
			'b' => array('vb'),
			'@b' => 20,
			'c' => 'vc',
			'@c' => 30,
			10 => 'vd',
			'@10' => 40,
		));

    	$this->assertEquals(
    		array(
    			'a' => 'va',
    			'b' => 'vb',
    			'c' => 'vc',
    			10 => 'vd',
    		),
    		$oSeq->getArray()
    	);

    	$this->assertEquals(
    		array(
    			'a' => 10,
    			'b' => 20,
    			'c' => 30,
    			10 => 40,
    		),
    		$oSeq->getOrderArray()
    	);

    	$this->assertEquals(
    		array(
    			'a' => array(
    				'key' => 'a',
    				'order' => 10,
    				'value' => 'va',
    			),
    			'b' => array(
    				'key' => 'b',
    				'order' => 20,
    				'value' => 'vb',
    			),
    			'c' => array(
    				'key' => 'c',
    				'order' => 30,
    				'value' => 'vc',
    			),
    			10 => array(
    				'key' => 10,
    				'order' => 40,
    				'value' => 'vd',
    			),
    		),
    		$oSeq->getValueOrderArray()
    	);    	
    }

    public function testEmpty()
    {
		$oSeq = new SequenceArray();

		$oSeq->setArray(array(
			'a' => 'va',
			'@a' => 10,
			'b' => array('vb'),
			'@b' => 20,
			'c' => 'vc',
			'@c' => 30,
			10 => 'vd',
			'@10' => 40,
		));
		$this->assertEquals(4, $oSeq->count());

		$oSeq->empty();

		$this->assertEquals(0, $oSeq->count());
		$this->assertEquals(array(), $oSeq->getArray());
    }

    public function testClone()
    {
		$oSeq = new SequenceArray();

		$oSeq->setArray(array(
			'a' => 'va',
			'@a' => 10,
			'b' => array('vb'),
			'@b' => 20,
			'c' => 'vc',
			'@c' => 30,
			10 => 'vd',
			'@10' => 40,
		));
		$this->assertEquals(4, $oSeq->count());

		$oSeqClone = $oSeq->clone();
		$this->assertNotSame($oSeq, $oSeqClone);
		$this->assertEquals(
			array(
				'a' => 'va',
				'b' => array('vb'),
				'c' => 'vc',
				10 => 'vd',
			),
			$oSeqClone->getArray()
		);

		$oSeq->set('a', 'changed');
		$this->assertEquals(
			array(
				'a' => 'changed',
				'b' => array('vb'),
				'c' => 'vc',
				10 => 'vd',
			),
			$oSeq->getArray()
		);
		$this->assertEquals(
			array(
				'a' => 'va',
				'b' => array('vb'),
				'c' => 'vc',
				10 => 'vd',
			),
			$oSeqClone->getArray()
		);
    }

    public function testIterate()
    {
		$oSeq = new SequenceArray();

		$oSeq->setArray(array(
			'a' => 'va',
			'@a' => 10,
			'b' => 'vb',
			'@b' => 20,
			'c' => 'vc',
			'@c' => 5,
			10 => 'vd',
			'@10' => 15,
		));

		$this->assertEquals(
			array(
				'c' => 'vc',
				'a' => 'va',
				10 => 'vd',
				'b' => 'vb',
			),
			$oSeq->getArray()
		);

		$aCheck = array();
		foreach ($oSeq as $sKey => $sValue) {
			$aCheck[] = array($sKey, $sValue);
		}
		$this->assertEquals(
			array(
				array('c', 'vc'),
				array('a', 'va'),
				array(10, 'vd'),
				array('b', 'vb'),
			),
			$aCheck
		);
    }
}
