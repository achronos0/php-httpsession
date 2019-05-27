<?php

function logger_callback($sQueue, $aWriterConfig, $aMessageList)
{
	$aData = array(
		'source' => 'logger',
		'queue' => $sQueue,
		'message' => $aMessageList[0],
	);
	echo json_encode($aData) . "\n";
}

function handler_callback($bFatal, $sSeverity, $sFinalMessage, $aErrorData)
{
	$aData = array(
		'source' => 'handler',
		'fatal' => $bFatal,
		'severity' => $sSeverity,
		'message' => $sFinalMessage,
		'error' => $aErrorData,
	);
	echo json_encode($aData) . "\n";
}

function do_error()
{
	foreach (null as $foo) {}
}

function do_fatal()
{
	$foo = new ClassDoesNotExist();
}

$sProjectDir = realpath(__DIR__ . '/../../');
$sDir = dirname($sProjectDir);
while (strlen($sDir) > 1) {
	if (is_file($sDir . '/composer.json')) {
		$sProjectDir = $sDir;
		break;
	}
	$sDir = dirname($sDir);
}
require_once($sProjectDir . '/vendor/autoload.php');

\Useful\Logger::getLogger()->setConfig(array(
	'logs' => array(
		'phperror'=> array(
			'mask' => 'all',
			'writers' => array('callback'),
		),
		'phpfatal'=> array(
			'mask' => 'all',
			'writers' => array('callback'),
		),
	),
	'writers' => array(
		'callback' => array(
			'enabled' => true,
			'call' => 'logger_callback',
			'queue' => false,
		),
	),
));

$oErrorHandler = \Useful\ErrorHandler::getErrorHandler();

$oHandlers = $oErrorHandler->getHandlerSequence();
$oHandlers->set('test', 'handler_callback');

$oErrorHandler->install();

if (in_array('fatal', $argv)) {
	do_fatal();
}
elseif (in_array('throw', $argv)) {
	$oErrorHandler->setThrow();
	try {
		do_error();
	}
	catch (ErrorException $e) {
		$aData = array(
			'source' => 'exception',
			'class' => get_class($e),
			'message' => $e->getMessage(),
			'code' => $e->getCode(),
			'type' => $e->getSeverity(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTrace(),
		);
		echo json_encode($aData) . "\n";
	}
}
elseif (in_array('native', $argv)) {
	$oErrorHandler->setNative();
	ini_set('display_errors', 1);
	do_error();
}
else {
	do_error();
}
