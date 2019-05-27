<?php
/**
 * \Useful\ErrorHandler class
 *
 * @link https://github.com/morvren-achronos/php-useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful;

use ErrorException, Psr\Log\LoggerAwareInterface, Psr\Log\LoggerInterface;

/**
 * Handle PHP errors
 *
 * Singleton class, call `ErrorHandler::getErrorHandler()` to get instance.
 *
 * @uses \ErrorException
 * @uses \Psr\Log\LoggerAwareInterface
 * @uses \Psr\Log\LoggerInterface
 * @uses \Useful\ErrorPatterns
 * @uses \Useful\Exception
 * @uses \Useful\Logger
 * @uses \Useful\SequenceArray
 */
class ErrorHandler implements LoggerAwareInterface
{
	//////////////////////////////
	// Public static

	/**
	 * Return singleton instance for this class
	 *
	 * @return \Useful\ErrorHandler instance
	 */
	public static function getErrorHandler()
	{
		if (!self::$oSelf) {
			self::$oSelf = new self();
		}
		return self::$oSelf;
	}


	//////////////////////////////
	// Public

	/**
	 * Install this class as PHP error handler
	 *
	 * By default install() does the following:
	 *     * Install custom PHP error handler
	 *     * Install fatal error handler (NOTE requires PHP 5 >= 5.2.0)
	 *     * Set PHP error reporting to report ALL errors
	 *     * Suppress PHP standard error handling and output
	 *     * Set default error logger
	 *
	 * The built-in default error logger uses class {@link \Useful\Logger}.
	 *     * It uses the default logging system, i.e. {@link \Useful\Logger::getLogger()}
	 *     * It writes PHP errors to log "phperror"
	 *     * It writes fatal errors to log "phpfatal"
	 * To change this, call {@link setLogger()} and {@link setFatalLogger()}.
	 *
	 * The installation steps can be altered by calling {@link getInstallSequence()} and modifying the returned sequence.
	 * This must be done BEFORE calling install().
	 *
	 * @return void
	 */
	public function install()
	{
		if ($this->mInstall === true) {
			return;
		}
		foreach ($this->getInstallSequence() as $xHandler) {
			call_user_func($xHandler);
		}
		$this->mInstall = true;
	}

	/**
	 * Check whether install() has been run
	 *
	 * @return bool
	 */
	public function getIsInstalled()
	{
		return $this->mInstall === true;
	}

	/**
	 * Set PSR-3 logger object to receive PHP errors
	 *
	 * @param (\Psr\Log\LoggerInterface|null) logger object, or NULL to disable logging
	 * @return void
	 */
	public function setLogger(LoggerInterface $oLogger)
	{
		$this->oLogger = $oLogger;
	}

	/**
	 * Return PSR-3 logger object registered to receive PHP errors
	 *
	 * @return \Psr\Log\LoggerInterface logger object
	 */
	public function getLogger()
	{
		return $this->oLogger;
	}

	/**
	 * Set PSR-3 logger object to receive fatal errors
	 *
	 * If set, this logger is used instead of the {@link setError()} logger for fatal errors.
	 *
	 * @param (\Psr\Log\LoggerInterface|null) logger object, or NULL to use logger for PHP errors
	 * @return void
	 */
	public function setFatalLogger(LoggerInterface $oLogger)
	{
		$this->oFatalLogger = $oLogger;
	}

	/**
	 * Return PSR-3 logger object registered to receive fatal errors
	 *
	 * @return \Psr\Log\LoggerInterface logger object
	 */
	public function getFatalLogger()
	{
		return $this->oFatalLogger;
	}

	/**
	 * Set PHP error reporting level
	 *
	 * This is a wrapper around {@link \error_reporting()}.
	 *
	 * @param (int|bool) $mLevel PHP error reporting level, or TRUE to report everything (including E_STRICT)
	 * @return void
	 */
	public function setErrorLevel($mLevel = true)
	{
		error_reporting(
			($mLevel === true)
			? (E_ALL | E_STRICT)
			: intval($mLevel)
		);
	}

	/**
	 * Convert PHP errors into exceptions
	 *
	 * When enabled, PHP errors are intercepted and converted to {@link \ErrorException}.
	 *
	 * Note that when exception conversion is enabled, no error handlers of any kind are called for normal PHP errors:
	 *     * Logger is not called
	 *     * Custom handlers are not called
	 *     * PHP native handler is not called
	 *
	 * PHP fatal errors cannot be converted into an exception.
	 * If a fatal error occurs, the fatal error handler provided by this class is still invoked.
	 *
	 * This option is disabled by default.
	 *
	 * @param bool $bMode TRUE to convert PHP errors to exception, FALSE to handle PHP errors normally
	 * @return void
	 */
	public function setThrow($bMode = true)
	{
		$this->bThrow = $bMode ? true : false;
	}

	/**
	 * Check whether conversion of PHP errors to exceptions is enabled
	 *
	 * This checks whether {@link setThrow()} has been called.
	 *
	 * @return bool TRUE if conversion is enabled, FALSE if errors are being handled normally
	 */
	public function getThrow()
	{
		return $this->bThrow;
	}

	/**
	 * Call PHP's default error handler after custom handler(s)
	 *
	 * When enabled, the custom error handler will invoke PHP's default handler after all other handlers.
	 *
	 * This option is disabled by default, meaning PHP's default error handler is NOT called.
	 *
	 * @param bool $bMode TRUE to call PHP default handler, FALSE to disable it
	 * @return void
	 */
	public function setNative($bMode = true)
	{
		$this->bNative = $bMode ? true : false;
	}

	/**
	 * Check whether PHP default error handler is enabled
	 *
	 * This checks whether {@link setNative()} has been called.
	 *
	 * @return bool TRUE if PHP default handler is enabled, FALSE if it is disabled
	 */
	public function getNative()
	{
		return $this->bNative;
	}

	/**
	 * Get custom error handler queue
	 *
	 * This returns a {@link \Useful\SequenceArray} of functions to be executed when a PHP error occurs.
	 *
	 * The value of each array element must be a callable.
	 * Signature:
	 *     function handler(bool $bFatal, string $sSeverity, string $sFinalMessage, array $aErrorData): void
	 * Arguments:
	 *     bool $bFatal TRUE if this is a fatal error
	 *     string $sSeverity RFC 5424 severity level, e.g. "critical"
	 *     string $sFinalMessage Formatted error description
	 *     array $aErrorData Details about error:
	 *         int `type` original PHP error type, e.g. E_ERROR
	 *         string `message` original PHP error message
	 *         string `file` error origin code file path
	 *         int `line` error origin line number
	 *         (array|null) `trace` stack trace, NULL for fatal error
	 *
	 * @return \Useful\SequenceArray error handler queue
	 */
	public function getHandlerSequence()
	{
		if (!$this->oHandlers) {
			$this->oHandlers = new SequenceArray();
		}
		return $this->oHandlers;
	}

	/**
	 * Get installation task queue
	 *
	 * This returns a {@link \Useful\SequenceArray} of functions to be executed when {@link install()} is called.
	 *
	 * If install() has already been called, this method returns bool FALSE.
	 *
	 * The value of each array element must be a callable.
	 * Signature:
	 *     function task(): void
	 * Arguments: none
	 *
	 * @return (\Useful\SequenceArray|bool) installation task queue, or FALSE if install() has already been called
	 */
	public function getInstallSequence()
	{
		if ($this->mInstall === true) {
			return false;
		}
		if (!$this->mInstall) {
			$this->mInstall = new SequenceArray(array(
				'set_default_logger' => array(
					'order' => 10,
					'value' => array($this, "install_set_default_logger"),
				),
				'set_reporting_level' => array(
					'order' => 20,
					'value' => array($this, "install_set_reporting_level"),
				),
				'register_error_handler' => array(
					'order' => 30,
					'value' => array($this, "install_register_error_handler"),
				),
				'register_fatal_handler' => array(
					'order' => 40,
					'value' => array($this, "install_register_fatal_handler"),
				),
			));
		}
		return $this->mInstall;
	}


	//////////////////////////////
	// Internal static

	protected static $oSelf;


	//////////////////////////////
	// Internal

	protected $mInstall;
	protected $bThrow = false;
	protected $bNative = false;
	protected $oLogger;
	protected $oFatalLogger;
	protected $oHandlers;

	/**
	 * PHP error handler
	 *
	 * @internal
	 * @param int $iType
	 * @param string $sMessage
	 * @param string $sFile
	 * @param int $iLine
	 * @return void
	 * @throws \ErrorException
	 */
	public function _phperror($iType, $sMessage, $sFile, $iLine)
	{
		// Skip suppressed errors
		if ((error_reporting() & $iType) != $iType)
			return false;

		// Throw exception
		if ($this->bThrow) {
			throw new ErrorException($sMessage, null, $iType, $sFile, $iLine);
		}

		// Get stacktrace
		$aTrace = ErrorPatterns::getTrace(false, 1);
		$aTrace[0] = preg_replace('/ErrorHandler->_phperror(?: called)?/', '(error)', $aTrace[0]);

		// Run error-handling tasks
		$this->handleError(false, $iType, $sMessage, $sFile, $iLine, $aTrace);

		// Tell PHP to invoke normal error-handling process
		if ($this->bNative) {
			return false;
		}
	}

	/**
	 * PHP fatal error handler
	 *
	 * @internal
	 * @return void
	 */
	public function _phpfatal()
	{
		// Check for non-catchable (fatal) error
		$aError = error_get_last();
		if (
			$aError === null
			|| !in_array(
				$aError['type'],
				array( E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING )
			)
		) {
			return;
		}

		// Run error-handling tasks
		$this->handleError(true, $aError['type'], $aError['message'], $aError['file'], $aError['line'], null);
	}

	/**
	 * Run error-handling tasks
	 *
	 * @internal
	 * @param bool $bFatal
	 * @param int $iType
	 * @param string $sMessage
	 * @param string $sFile
	 * @param int $iLine
	 * @param array $aTrace
	 * @return void
	 */
	protected function handleError($bFatal, $iType, $sMessage, $sFile, $iLine, $aTrace)
	{
		// Get final message
		$sFinalMessage = ErrorPatterns::formatPhpError($iType, $sMessage, $sFile, $iLine);

		// Get RFC 5424 severity
		$sSeverity = ErrorPatterns::getErrorSeverity($iType);

		// Assemble error data
		$aErrorData = array(
			'type' => $iType,
			'message' => $sMessage,
			'file' => $sFile,
			'line' => $iLine,
		);
		if ($aTrace) {
			$aErrorData['trace'] = $aTrace;
		}

		// Call logger
		if ($bFatal && $this->oFatalLogger) {
			$this->oFatalLogger->log($sSeverity, $sFinalMessage, $aErrorData);
		}
		elseif ($this->oLogger) {
			$this->oLogger->log($sSeverity, $sFinalMessage, $aErrorData);
		}

		// Call custom handlers
		foreach ($this->getHandlerSequence() as $xHandler) {
			call_user_func($xHandler, $bFatal, $sSeverity, $sFinalMessage, $aErrorData);
		}
	}

	/**
	 * Standard installation task: set default error logger
	 *
	 * @internal
	 * @return void
	 */
	protected function install_set_default_logger()
	{
		if (!$this->getLogger()) {
			$this->setLogger(Logger::getLogger()->getLog('phperror'));
			if (!$this->getFatalLogger()) {
				$this->setFatalLogger(Logger::getLogger()->getLog('phpfatal'));
			}
		}
	}

	/**
	 * Standard installation task
	 *
	 * @internal
	 * @return void
	 */
	protected function install_set_reporting_level()
	{
		$this->setErrorLevel(true);
	}

	/**
	 * Standard installation task: register custom PHP error handler
	 *
	 * @internal
	 * @return void
	 */
	protected function install_register_error_handler()
	{
		ini_set('display_errors', 0);
		ini_set('html_errors', false);
		set_error_handler(array($this, '_phperror'));
	}

	/**
	 * Standard installation task: register fatal error handler
	 *
	 * @internal
	 * @return void
	 */
	protected function install_register_fatal_handler()
	{
		if (function_exists('error_get_last')) {
			class_exists('ErrorPatterns', true);
			register_shutdown_function(array($this, '_phpfatal'));
		}
	}
}
