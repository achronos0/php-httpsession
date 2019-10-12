<?php

// Install __autoload (PHP 5.0)
function __autoload($sClassName)
{
	Useful_Legacy_Loader::loadClass($sClassName);
}
