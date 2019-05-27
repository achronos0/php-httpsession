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
	protected $data;
	protected $prev;

	/**
	 * Exception constructor
	 *
	 * This constructor can be called with `$previous` or `$data` arguments in either order.
	 * So, all of the following are valid:
	 *     throw new \Useful\Exception($message);
	 *     throw new \Useful\Exception($message, $code);
	 *     throw new \Useful\Exception($message, $code, $data);
	 *     throw new \Useful\Exception($message, $code, $previous);
	 *     throw new \Useful\Exception($message, $code, $previous, $data);
	 *     throw new \Useful\Exception($message, $code, $data, $previous);
	 *
	 * @param string $message exception message to throw
	 * @param $int $code exception code
	 * @param \Exception $previous underlying exception that lead to this one
	 * @param mixed $data additional data, can to be retrieved by calling {@link getData()}
	 */
	public function __construct($message = '', $code = 0, $previous = null, $data = null)
	{
		if (!($previous instanceof \Exception)) {
			if ($data instanceof \Exception) {
				$a = $previous;
				$previous = $data;
				$data = $a;
			}
			elseif ($data === null) {
				$data = $previous;
				$previous = null;
			}
		}

		if (
			!$previous
			&& isset($data['exception'])
			&& $data['exception'] instanceof \Exception
		) {
			$previous = $data['exception'];
			unset($data['exception']);
		}
		if (
			!$previous
			&& isset($data['error'])
			&& $data['error'] instanceof \Exception
		) {
			$previous = $data['error'];
			unset($data['error']);
		}

		if (method_exists($this, 'getPrevious')) {
			parent::__construct($message, $code, $previous);
			$this->prev = true;
		}
		else {
			parent::__construct($message, $code);
			$this->prev = $previous;
		}

		$this->data = $data;
	}

	/**
	 * Return previous exception
	 *
	 * Previous exception is set by the constructor `$previous` argument.
	 *
	 * For versions of PHP that natively support $previous, this method is an alias of {@link getPrevious()}.
	 * For earlier versions of PHP that do not support $previous, this method acts as a polyfill.
	 *
	 * @return (\Exception|null) previous exception
	 */
	public function getPreviousException()
	{
		if ($this->prev === true) {
			return $this->getPrevious();
		}
		return $this->prev;
	}

	/**
	 * Return additional data
	 *
	 * Additional data is set by the constructor `$data` argument.
	 *
	 * @param (scalar|null) $key if data is an array, return only this key from it; otherwise all data is returned
	 * @return mixed data
	 */
	public function getData($key = null)
	{
		if ($key === null) {
			return $this->data;
		}
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	public function __debugInfo()
	{
		return array(
			'message' => $this->getMessage(),
			'code' => $this->getCode(),
			'file' => $this->getFile(),
			'line' => $this->getLine(),
			'previous' => $this->getPreviousException(),
			'data' => $this->getData(),
		);
	}
}
