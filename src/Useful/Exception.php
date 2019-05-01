<?php
/**
 * \Useful\Exception class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful;

/**
 * Standard exception thrown by all Useful classes
 */
class Exception extends \Exception
{
	protected $mData;
	protected $mPreviousException;

	public function __construct($message = '', $code = 0, $previous = null, $data = null)
	{
		if (!is_object($previous) && !$data) {
			$data = $previous;
			$previous = null;
		}
		if (!$previous && isset($data['exception']) && is_object($data['exception'])) {
			$previous = $data['exception'];
		}
		if (!$previous && isset($data['error']) && is_object($data['error'])) {
			$previous = $data['error'];
		}
		if (method_exists($this, 'getPrevious')) {
			parent::__construct($message, $code, $previous);
			$this->mPreviousException = true;
		}
		else {
			parent::__construct($message, $code);
			$this->mPreviousException = $previous;
		}
		$this->mData = $data;
	}

	public function getData($key = null)
	{
		if ($key === null) {
			return $this->mData;
		}
		if (is_array($this->mData) && isset($this->mData[$key])) {
			return $this->mData[$key];
		}
		return null;
	}

	public function getPreviousException()
	{
		if ($this->mPreviousException === true) {
			return $this->getPrevious();
		}
		return $this->mPreviousException;
	}
}
