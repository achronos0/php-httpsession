<?php
spl_autoload_register(
	function($sClass)
	{
		if (substr($sClass, 0, 7) != 'Useful\\')
			return;
		require_once(
			__DIR__
			. DIRECTORY_SEPARATOR
			. 'Useful'
			. DIRECTORY_SEPARATOR
			. substr($sClass, 7)
			. '.php'
		);
	}
);