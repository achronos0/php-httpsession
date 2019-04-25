<?php
/**
 * Useful_Legacy_Loader class
 *
 * @link https://github.com/morvren-achronos/useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

/**
 * Load namespaced classes in pre-namespace PHP 5 < 5.3
 *
 * This class takes a legacy-style classname like "Foo_Bar_Baz_Bat", finds a PSR-compliant class file, and loads the class without its namespace.
 *
 * Note this is only works on specially-designed class files, it can't fixup just any file you point it at.
 * Useful classes are so designed, check out the source for details if you want to do the same for your code.
 */
class Useful_Legacy_Loader
{
	//////////////////////////////
	// Public static

	/**
	 * Load a namespaced class using a non-namespaced classname
	 *
	 * This method can be registered directly as an SPL autoloader (and that's exactly what {link @registerSplAutoloader()} does).
	 *
	 * @param string $sClassName legacy classname
	 * @param bool $bThrow true to throw an Exception if an error occurs during loading
	 * @return bool true if class loaded, null if class not loaded, false on error and if $bThrow is false
	 * @throws Exception (on request only)
	 */
	public static function loadClass($sClassName, $bThrow = false)
	{
		foreach (self::$aRegistry as $sPrefix => $aDefine) {
			$sFind = $aDefine['lc'] ? strtolower($sClassName) : $sClassName;
			if (strpos($sFind, $sPrefix) === 0) {
				$sFind = substr($sFind, strlen($sPrefix));
				$sFilePath = self::findClassFile($sFind, $aDefine['path'], $aDefine['name']);
				if ($sFilePath) {
					$sCacheDir = $aDefine['cache'] . dirname(substr($sFilePath, strlen($aDefine['path']))) . DIRECTORY_SEPARATOR;
					return self::loadClassFromFile($sClassName, $sFilePath, $sCacheDir, $bThrow);
				}
			}
		}
		return null;
	}

	/**
	 * Load a namespaced class file under a non-namespaced classname
	 *
	 * @param string $sLoadAsClassName what to load the class as
	 * @param string $sFilePath file containing single class, PSR style
	 * @param string $sCacheDir directory to which modified class code can be written
	 * @param bool $bThrow true to throw an Exception if an error occurs during loading
	 * @return bool true if class loaded, false on error and if $bThrow is false
	 */
	public static function loadClassFromFile($sLoadAsClassName, $sFilePath, $sCacheDir, $bThrow = false)
	{
		$sFileName = basename($sFilePath);
		$sClassName = preg_replace('/^(\w+)\..*$/', '$1', $sFileName);
		mkdir($sCacheDir, 0777, true);
		$sCacheFile = $sCacheDir . DIRECTORY_SEPARATOR . $sFileName;
		if (!file_exists($sCacheFile)) {
			$sContent = file_get_contents($sFilePath);
			$sContent = self::transformClassCode($sClassName, $sLoadAsClassName, $sContent);
			if (!file_put_contents($sCacheFile, $sContent)) {
				if ($bThrow) {
					throw new Exception("file_put_contents($sCacheFile) failed");
				}
				return false;
			}
		}
		if (!include_once($sCacheFile)) {
			@unlink($sCacheFile);
			if ($bThrow) {
				throw new Exception("include($sCacheFile) failed, check for PHP errors");
			}
			return false;
		}
		return true;
	}

	/**
	 * Register a namespace to be handled by this loader
	 *
	 * @param string $sNamespace the namespace prefix to register, e.g. "\Foo" or "\Foo\Bar_Baz"
	 * @param string $sRootClassDir where to start looking for class files, e.g. "/vendor/my_library/src/Foo/Bar_Baz"
	 * @param string $sCacheDir where to store modified class files; directory must be writable
	 * @param string $sFileNamePattern sprintf format for what class filenames look like; for PSR "ClassName.php" use '%s.php'
	 * @param bool $bLowercase true to transform everything (paths, namespace, classname) to lowercase when looking for code
	 * @return void
	 */
	public static function registerNamespace($sNamespace, $sRootClassDir, $sCacheDir, $sFileNamePattern = '%s.php', $bLowercase = false)
	{
		if ($bLowercase) {
			$sNamespace = strtolower($sNamespace);
		}
		$sNamespace = trim($sNamespace, '\\');
		$sPrefix = str_replace('\\', '_', $sNamespace) . '_';
		$sRootClassDir = rtrim($sRootClassDir, '\\/') . DIRECTORY_SEPARATOR;
		$sCacheDir = rtrim($sCacheDir, '\\/') . DIRECTORY_SEPARATOR;
		self::$aRegistry[$sPrefix] = array(
			'prefix' => $sPrefix,
			'ns' => $sNamespace,
			'path' => $sRootClassDir,
			'name' => $sFileNamePattern,
			'lc' => $bLowercase,
			'cache' => $sCacheDir,
		);
	}

	/**
	 * Register autoloader for legacy classes to be handled by this leader
	 *
	 * This is just a wrapper around `spl_autoload_register('Useful_Legacy_Loader::loadClass')`
	 *
	 * @return void
	 */
	public static function registerSplAutoloader()
	{
		spl_autoload_register(array('Useful_Legacy_Loader', 'loadClass'));
	}

	/**
	 * De-register this class' autoloader
	 *
	 * @return void
	 */
	public static function unregisterSplAutoloader()
	{
		spl_autoload_unregister(array('Useful_Legacy_Loader', 'loadClass'));
	}

	public static function getRegisteredNamespaces()
	{
		return self::$aRegistry;
	}

	public static function clearCache($sNamespace = null)
	{
		if ($sNamespace) {
			$sNamespace = trim($sNamespace, '\\');
			$sPrefix = str_replace('\\', '_', $sNamespace) . '_';
			if (!isset(self::$aRegistry[$sPrefix])) {
				throw new Exception('Namespace not registered');
			}
			self::rmraf(self::$aRegistry[$sPrefix]['cache']);
			return;
		}
		foreach (self::$aRegistry as $aDefine) {
			self::rmraf($aDefine['cache']);
		}
	}


	//////////////////////////////
	// Internal static

	/**
	 * Stores registered classname prefixes and associated loading instructions
	 *
	 * @internal
	 * @var array
	 */
	protected static $aRegistry = array();

	/**
	 * Find a class file being loaded via a legacy name
	 *
	 * Foo_Bar_Baz_Bat => \Foo\Bar_Baz\Bat => /my_lib_dir/Foo/Bar_Baz/Bat.php
	 *
	 * @internal
	 * @param string $sClassName legacy class name to locate
	 * @param string $sDir directory to look in, must have trailing DIRECTORY_SEPARATOR
	 * @param string $sFileNamePattern sprintf filename pattern with %s for final classname part; e.g. "%s.php"
	 * @return void
	*/
	protected static function findClassFile($sClassName, $sDir, $sFileNamePattern)
	{
		while (is_dir($sDir)) {
			$sFileName = sprintf($sFileNamePattern, $sClassName);
			if (is_file($sDir . $sFileName)) {
				return $sDir . $sFileName;
			}
			if (strpos($sClassName, '_') === false) {
				return false;
			}
			foreach (scandir($sDir) as $sFileName) {
				if (strpos($sClassName, $sFileName . '_') === 0) {
					$sClassName = substr($sClassName, strlen($sFileName) + 1);
					$sDir .= $sFileName . DIRECTORY_SEPARATOR;
					continue 2;
				}
			}
			return false;
		}
		return false;
	}

	/**
	 * Transform class file content to load without namespace support
	 *
	 * @internal
	 * @param string $sLoadAsClassName what the class should call itself
	 * @param string $sContent the original class code
	 * @return string the transformed class code
	 */
	protected static function transformClassCode($sClassName, $sLoadAsClassName, $sContent)
	{
		$sContent = str_replace("\n/*==NAMESPACE*/\n", "\n/*==NAMESPACE\n", $sContent);
		$sContent = str_replace("\n/*NAMESPACE==*/\n", "\n==NAMESPACE*/\n", $sContent);
		$sContent = preg_replace('/(\n[ \t]*class\s*)[\w\\\\]+/s', '$1' . $sLoadAsClassName, $sContent);
		$sContent = str_replace("$sClassName::", "$sLoadAsClassName::", $sContent);
		return $sContent;
	}

	protected static function rmraf($sPath)
	{
		if (is_file($sPath)) {
			unlink($sPath);
			return;
		}
		if (is_dir($sPath)) {
			foreach (scandir($sPath) as $sName) {
				if ($sName != '.' && $sName != '..') {
					self::rmraf($sPath . DIRECTORY_SEPARATOR . $sName);
				}
			}
			rmdir($sPath);
			return;
		}
		return false;
	}
}
