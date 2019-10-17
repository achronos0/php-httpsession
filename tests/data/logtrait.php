<?php

use \Useful\Logger\LogTrait;

class TestLogTrait
{
	use LogTrait;

	public function __construct($oLog)
	{
		$this->setLog($oLog);
	}

	public function doThing()
	{
		$this->info('did thing');
	}
}
