<?php
/**
 * \Useful\SequenceArray class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful;

use \ArrayAccess, \ArrayIterator, \Countable, \IteratorAggregate;

/**
 * An array-like object with a both a key index and a sort index for each element
 *
 * A SequenceArray is a combination of an associative array and an ordered queue.
 *
 * Each element has three attributes:
 *     * A lookup "key" index (string or int)
 *     * A sorting "order" index (int)
 *     * A value (anything)
 *
 * Elements are accessed by key index, and iterated according to order index (numerically sorted min to max).
 *
 * @uses \ArrayIterator
 * @uses \Countable
 * @uses \IteratorAggregate
 */
class SequenceArray implements ArrayAccess, Countable, IteratorAggregate
{
	//////////////////////////////
	// Public

	/**
	 * Create new SequenceArray object
	 *
	 * Optionally populate the sequence from an array (see {@link setArray()}) or another SequenceArray.
	 *
	 * @param (array|\Useful\SequenceArray) $mData populate sequence using this data
	 */
	public function __construct($mData = null)
	{
		if ($mData !== null) {
			if ($mData instanceof self) {
				$this->setFromSequence($mData);
			}
			else {
				$this->setArray($mData);
			}
		}
		else {
			$this->empty();
		}
	}

	/**
	 * Return element value
	 *
	 * @param (string|int) $mKey key
	 * @return (mixed|null) value, NULL if element does not exist
	 */
	public function get($mKey)
	{
		return isset($this->aValue[$mKey]) ? $this->aValue[$mKey] : null;
	}

	/**
	 * Return element order index
	 *
	 * @param (string|int) $mKey key
	 * @return (int|null) order index, NULL if element does not exist
	 */
	public function getOrder($mKey)
	{
		return isset($this->aValue[$mKey]) ? $this->aOrder[$mKey] : null;
	}

	/**
	 * Check whether element exists
	 *
	 * @param (string|int) $mKey key
	 * @return bool TRUE if element exists, FALSE if it does not exist
	 */
	public function has($mKey)
	{
		return array_key_exists($mKey, $this->aValue);
	}

	/**
	 * Set element value
	 *
	 * If element already exists and order index is NULL or not provided, existing order index remains unchanged.
	 * If element does not exist and order index is NULL or not provided, element is pushed onto the end of the queue.
	 *
	 * @param (string|int) $mKey key
	 * @param mixed $mValue value
	 * @param (int|bool) $mOrder optionally set order index, see {@link setOrder()}
	 * @return void
	 */
	public function set($mKey, $mValue, $mOrder = null)
	{
		$this->setElement($mKey, true, $mValue, $mOrder);
	}

	/**
	 * Set element order index
	 *
	 * Order index is a signed integer. Negative order indexes are supported.
	 *
	 * bool TRUE acts as array_unshift(), the element is placed at the start of the queue.
	 * (Its order index is set one lower than the previous lowest.)
	 *
	 * bool FALSE (or NULL) acts as array_push(), the element is placed at the end of the queue.
	 * (Its order index is set one higher than the previous highest.)
	 *
	 * Anything else is (e.g. string, float) is coerced to int.
	 *
	 * If element does not exist, it is added with value set to NULL.
	 *
	 * @param (string|int) $mKey key
	 * @param (int|bool) $mOrder order index
	 * @return void
	 */
	public function setOrder($mKey, $mOrder)
	{
		$this->setElement($mKey, false, null, $mOrder);
	}

	/**
	 * Remove element
	 *
	 * @param (string|int) $mKey key
	 * @return void
	 */
	public function remove($mKey)
	{
		unset($this->aValue[$mKey]);
		unset($this->aOrder[$mKey]);
	}

	/**
	 * Count elements
	 *
	 * @return int count
	 */
	public function count()
	{
		return count($this->aValue);
	}

	/**
	 * Return array with keys and values
	 *
	 * Returned array has one element for each sequence element; index is sequence element key, value is sequence element value.
	 *
	 * Elements are returned in sorted order (min to max).
	 *
	 * @return array keys and values
	 */
	public function getArray()
	{
		$this->sort();
		return $this->aValue;
	}

	/**
	 * Return array with keys and order indexes
	 *
	 * Returned array has one element for each sequence element; index is sequence element key, value is sequence element order index.
	 *
	 * Elements are returned in sorted order (min to max).
	 *
	 * @return array keys and order indexes
	 */
	public function getOrderArray()
	{
		$this->sort();
		return $this->aOrder;
	}

	/**
	 * Return array with keys, order indexes and values
	 *
	 * In returned array, each element is array:
	 *     string `key`
	 *     int `order`
	 *     mixed `value`
	 *
	 * Elements are returned in sorted order (min to max).
	 *
	 * @return array keys, order indexes and values
	 */
	public function getValueOrderArray()
	{
		$aReturn = array();
		$this->sort();
		foreach ($this->aValue as $mKey => $mValue) {
			$aReturn[$mKey] = array(
				'key' => $mKey,
				'order' => $this->aOrder[$mKey],
				'value' => $mValue,
			);
		}
		return $aReturn;
	}

	/**
	 * Populate sequence from an array
	 *
	 * Existing data in sequence is replaced.
	 *
	 * Any normal PHP array can be passed and will be used to set element keys and values.
	 *
	 * To set order indexes as well as values, two formats are supported:
	 *
	 * 1) Array
	 * Pass each element as an array containing order and value, using same format as returned by {@link getValueOrderArray()}.
	 * Example:
	 *     $this->setArray(array(
	 *         'a key' => array(
	 *             'order' => 10,
	 *             'value' => 'a value',
	 *         ),
	 *         array(
	 *             'key' => 'another key'
	 *             'order' => 20,
	 *             'value' => 'second value',
	 *         ),
	 *         2 => array(
	 *             'order' => 30,
	 *             'value' => 'numeric key value',
	 *         ),
	 *     ));
	 *
	 * 2) At-keys
	 * Pass each element as two keys, one as ["key"] to set value and one as ["@key"] to set order index.
	 * Example:
	 *     $this->setArray(array(
	 *         'a key' => 'a value',
	 *         '@a key' => 10,
	 *         'another key' => 'second value',
	 *         '@another key' => 20,
	 *         2 => 'numeric key value',
	 *         '@2' => 30,
	 *     ));
	 *
	 * @param (array|Traversable) $aData new data for this sequence
	 * @return void
	 */
	public function setArray($aData)
	{
		$this->empty();
		foreach ($aData as $mKey => $mValue) {
			$this->offsetSet($mKey, $mValue);
		}
	}

	/**
	 * Populate sequence from another sequence
	 *
	 * Existing data in sequence is replaced.
	 *
	 * All elements from other sequence are copied into this one (including keys, order indexes and values).
	 *
	 * @param \Useful\SequenceArray $oOtherSequence sequence
	 * @return void
	 */
	public function setFromSequence($oOtherSequence)
	{
		$this->aValue = $oOtherSequence->aValue;
		$this->aOrder = $oOtherSequence->aOrder;
		$this->bSorted = $oOtherSequence->bSorted;
	}

	/**
	 * Return copy of this object
	 *
	 * @return \Useful\SequenceArray new object
	 */
	public function clone()
	{
		return new self($this);
	}

	/**
	 * Remove all elements
	 *
	 * @return void
	 */
	public function empty()
	{
		$this->aValue = array();
		$this->aOrder = array();
		$this->bSorted = true;
	}

	/**
	 * Return iterator
	 *
	 * Iterates elements in sorted order (min to max).
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		$this->sort();
		return new ArrayIterator($this->aValue);
	}


	//////////////////////////////
	// Internal

	protected $aValue = array();
	protected $aOrder = array();
	protected $bSorted = true;

	/**
	 * Implement \ArrayAccess
	 *
	 * @internal
	 * @param (string|int) $mKey array element index
	 * @return bool
	 */
	public function offsetExists($mKey)
	{
		if (is_string($mKey) && substr($mKey, 0, 1) == '@') {
			$mKey = substr($mKey, 1);
		}
		return $this->has($mKey);
	}

	/**
	 * Implement \ArrayAccess
	 *
	 * @internal
	 * @param (string|int) $mKey array element index
	 * @return mixed
	 */
	public function offsetGet($mKey)
	{
		if (is_string($mKey) && substr($mKey, 0, 1) == '@') {
			$mKey = substr($mKey, 1);
			return $this->getOrder($mKey);
		}
		return $this->get($mKey);
	}

	/**
	 * Implement \ArrayAccess
	 *
	 * @internal
	 * @param (string|int) $mKey array element index
	 * @param mixed $mValue array element value
	 */
	public function offsetSet($mKey, $mValue)
	{
		if (
			is_string($mKey)
			&& substr($mKey, 0, 1) == '@'
			&& (is_scalar($mValue) || $mValue === null)
		) {
			$mKey = substr($mKey, 1);
			$this->setElement($mKey, false, null, $mValue);
			return;
		}

		$mOrder = null;
		if (is_array($mValue)) {
			if (
				isset($mValue['key'])
				&& (is_scalar($mValue['key']) || $mValue['key'] === null)
			) {
				$mKey = $mValue['key'];
				unset($mValue['key']);
			}
			if (
				isset($mValue['order'])
				&& (is_scalar($mValue['order']) || $mValue['order'] === null)
			) {
				$mOrder = $mValue['order'];
				unset($mValue['order']);
			}
			if (count($mValue) == 1 && array_key_exists('value', $mValue)) {
				$mValue = $mValue['value'];
			}
		}
		$this->setElement($mKey, true, $mValue, $mOrder);
	}

	/**
	 * Implement \ArrayAccess
	 *
	 * @internal
	 * @param (string|int) $mKey array element index
	 * @return void
	 */
	public function offsetUnset($mKey)
	{
		if (is_string($mKey) && substr($mKey, 0, 1) == '@') {
			$mKey = substr($mKey, 1);
		}
		return $this->remove($mKey);
	}

	/**
	 * Set key/priority/value
	 *
	 * @internal
	 * @param (string|int) $mKey key
	 * @param mixed $mValue value
	 * @param int $mOrder order index
	 * @return void
	 */
	protected function setElement($mKey, $bSetValue, $mValue, $mOrder)
	{
		if ($mKey === null) {
			$mKey = 0;
			if ($this->aValue) {
				$mMax = max(array_keys($this->aValue));
				if (is_int($mMax)) {
					$mKey = $mMax + 1;
				}
			}
			$bExists = false;
		}
		elseif (isset($this->aValue[$mKey])) {
			if (!$bSetValue) {
				$mValue = $this->aValue[$mKey];
			}
			if ($mOrder === null) {
				$mOrder = $this->aOrder[$mKey];
			}
		}

		if ($mOrder === true) {
			$this->sort();
			$iOrder = reset($this->aOrder) - 1;
		}
		elseif ($mOrder === false || $mOrder === null) {
			$this->sort();
			$iOrder = end($this->aOrder) + 1;
		}
		else {
			$iOrder = intval($mOrder);
		}

		$this->aValue[$mKey] = $mValue;
		$this->aOrder[$mKey] = $iOrder;
		$this->bSorted = false;
	}

	/**
	 * Ensure elements are sorted
	 *
	 * @internal
	 */
	protected function sort()
	{
		if ($this->bSorted) {
			return;
		}

		asort($this->aOrder);

		$aSortedValues = array();
		foreach (array_keys($this->aOrder) as $mKey) {
			$aSortedValues[$mKey] = $this->aValue[$mKey];
		}
		$this->aValue = $aSortedValues;

		$this->bSorted = true;
	}
}
