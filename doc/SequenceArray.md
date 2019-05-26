# SequenceArray

An array-like object with a both a key index and a sort index for each element

## Overview

A `SequenceArray` is a combination of an associative array and an ordered queue.

Each element has three attributes:
* A lookup "key" index (string or int)
* A sorting "order" index (int)
* A value (anything)

Elements are accessed by key index, and iterated according to order index (numerically sorted min to max).

## Examples

```php
$oSequence = new \Useful\SequenceArray(array(
	'key_a' => array(
		'order' => 10,
		'value' => 'a value',
	),
	'key_b' => array(
		'order' => 20,
		'value' => 'b value',
	),
	'key_c' => array(
		'order' => 5,
		'value' => 'c value',
	),
));

foreach ($oSequence as $sKey => $sValue) {
	echo "$sKey = '$sValue'\n";
}
/*
	key_c = 'c value'
	key_a = 'a value'
	key_b = 'b value'
*/

$oSequence->set('key_d', 'd value', 15); // insert between key_a and key_b
$oSequence->set('key_e', 'e value'); // push at end after key_b
$oSequence->set('key_f', 'f value', true); // unshift at beginning before key_c

foreach ($oSequence as $sKey => $sValue) {
	echo "$sKey = '$sValue'\n";
}
/*
	key_f = 'f value'
	key_c = 'c value'
	key_a = 'a value'
	key_d = 'd value'
	key_b = 'b value'
	key_e = 'e value'
*/

echo $oSequence->get('key_c') . "\n";
// c value
echo ($oSequence->has('key_c') ? 'true' : 'false') . "\n";
// true

echo $oSequence->getOrder('key_c') . "\n";
// 5

$oSequence->remove('key_c');
echo ($oSequence->has('key_c') ? 'true' : 'false') . "\n";
// false

echo $oSequence['key_d'] . "\n";
// d value
echo $oSequence['@key_d'] . "\n";
// 15

echo (isset($oSequence['key_d'] ? 'true' : 'false') . "\n";
// true

unset($oSequence['key_c']);
echo (isset($oSequence['key_d'] ? 'true' : 'false') . "\n";
// false

$oSequence['key_g'] = 'g value';
$oSequence['@key_g'] = 25;
echo $oSequence->get('key_g') . "\n";
// g value
echo $oSequence->getOrder('key_g') . "\n";
// 25
```
