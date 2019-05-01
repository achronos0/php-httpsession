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
	 * Delete previously generated legacy class files
	 *
	 * @param string $sNamespace registered namespace prefix, or NULL to clear cache for all registered prefixes
	 * @return void
	 */
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

	/**
	 * Generate a non-namespaced class code file using a namespaced class code file
	 *
	 * @param string $sClassFile file containing single class, PSR style
	 * @param string $sCacheFile file to write modified class code to
	 * @param bool $bThrow true to throw an Exception if an error occurs during loading
	 * @return bool true if file written, false on error and if $bThrow is false
	 * @throws Exception (when requested only)
	 */
	protected static function generateClassCacheFile($sClassFile, $sCacheFile, $bThrow = false)
	{
		$sClassName = preg_replace('/^(\w+)\..*$/', '$1', $sClassFile);
		$sCacheDir = dirname($sCacheFile);
		if (!file_exists($sCacheDir)) {
			mkdir($sCacheDir, 0777, true);
		}
		$sContent = file_get_contents($sClassFile);
		$sContent = self::transformClassCode($sContent);
		if (!file_put_contents($sCacheFile, $sContent)) {
			if ($bThrow) {
				throw new Exception("file_put_contents($sCacheFile) failed");
			}
			return false;
		}
		touch($sCacheFile, filemtime($sClassFile));
		return true;
	}

	/**
	 * Load a namespaced class using a non-namespaced classname
	 *
	 * This method can be registered directly as an SPL autoloader (and that's exactly what {link @registerSplAutoloader()} does).
	 *
	 * @param string $sClassName legacy classname
	 * @param bool $bThrow true to throw an Exception if an error occurs during loading
	 * @return bool true if class loaded, null if class not loaded, false on error and if $bThrow is false
	 * @throws Exception (when requested only)
	 */
	public static function loadClass($sClassName, $bThrow = false)
	{
		foreach (self::$aRegistry as $sPrefix => $aDefine) {
			$sFind = $aDefine['lc'] ? strtolower($sClassName) : $sClassName;
			if (strpos($sFind, $sPrefix) === 0) {
				$sFind = substr($sFind, strlen($sPrefix));
				$sClassFile = self::findClassFile($sFind, $aDefine['path'], $aDefine['name']);
				if ($sClassFile) {
					$sCacheDir = $aDefine['cache'] . dirname(substr($sClassFile, strlen($aDefine['path']))) . DIRECTORY_SEPARATOR;
					return self::loadClassFromFile($sClassName, $sClassFile, $sCacheDir, $bThrow);
				}
			}
		}
		return null;
	}

	/**
	 * Load a namespaced class file under a non-namespaced classname
	 *
	 * @param string $sLoadAsClassName what to load the class as
	 * @param string $sClassFile file containing single class, PSR style
	 * @param string $sCacheDir directory to which modified class code can be written
	 * @param bool $bThrow true to throw an Exception if an error occurs during loading
	 * @return bool true if class loaded, false on error and if $bThrow is false
	 * @throws Exception (when requested only)
	 */
	public static function loadClassFromFile($sLoadAsClassName, $sClassFile, $sCacheDir, $bThrow = false)
	{
		$sCacheFile = $sCacheDir . DIRECTORY_SEPARATOR . basename($sClassFile);
		if (!file_exists($sCacheFile) || filemtime($sCacheFile) != filemtime($sClassFile)) {
			if (!self::generateClassCacheFile($sClassFile, $sCacheFile, $bThrow)) {
				return false;
			}
		}
		if (!include_once($sCacheFile)) {
			if ($bThrow) {
				throw new Exception("include($sCacheFile) failed, check for PHP errors");
			}
			return false;
		}
		if (!class_exists($sLoadAsClassName, false)) {
			if ($bThrow) {
				throw new Exception("class_exists($sLoadAsClassName) failed, class file does not declare correct class name");
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
	 * @param string $sContent the original class code
	 * @return string the transformed class code
	 */
	protected static function transformClassCode($sContent)
	{
		// Handle namespace statement
		if (preg_match('/\n[ \t]*namespace\s+([\w\\\\]+)\s*;/s', $sContent, $aMatches)) {
			$sStatement = substr($aMatches[0], 1);
			$sNamespace = '\\' . trim($aMatches[1], '\\') . '\\';
			$sContent = str_replace($sStatement, '/*#nonamespace ' . $sStatement . '*/', $sContent);
		}
		else {
			$sNamespace = '\\';
		}

		// Handle use statements
		$aClassConvertMap = array();
		preg_match_all('/\n[ \t]*use\s+([^;]+);/s', $sContent, $aMatchesList, PREG_SET_ORDER);
		foreach ($aMatchesList as $aMatches) {
			$sStatement = substr($aMatches[0], 1);
			foreach (preg_split('/\s*,\s*/', $aMatches[1]) as $sUse) {
				if (preg_match('/^([\w\\\\]+)\s+as\s+(\w+)$/', $sUse, $aMatches)) {
					$sFullClass = $aMatches[1];
					$sClass = $aMatches[2];
				}
				else {
					$sFullClass = $sUse;
					$i = strrpos($sFullClass, '\\');
					if ($i !== false) {
						$sClass = substr($sFullClass, $i + 1);
					}
					else {
						$sClass = $sFullClass;
					}
				}
				if (substr($sFullClass, 0, 1) != '\\') {
					$sFullClass = '\\' . $sFullClass;
				}
				$aClassConvertMap[$sClass] = $sFullClass;
			}
			$sContent = str_replace($sStatement, '/*#nonamespace ' . $sStatement . '*/', $sContent);
		}

		// Convert class statements
		preg_match_all('/\n([ \t]*(?:abstract\s+|final\s+)?(?:class\s+|interface\s+))([A-Z]\w*)(?:(\s+extends\s+)([\w\\\\]+))?(?:(\s+implements\s+)([^{]+))?/s', $sContent, $aMatchesList, PREG_SET_ORDER);
		foreach ($aMatchesList as $aMatches) {
			$sStatement = substr($aMatches[0], 1);
			$sPrefix = $aMatches[1];
			$sClass = $aMatches[2];
			$sExtendsKeyword = isset($aMatches[3]) ? $aMatches[3] : null;
			$sExtendsClass = isset($aMatches[4]) ? $aMatches[4] : null;
			$sImplementsKeyword = isset($aMatches[5]) ? $aMatches[5] : null;
			$sImplementsClassList = isset($aMatches[6]) ? $aMatches[6] : null;
			$sClass = self::convertNsClassName($sClass, $sNamespace, $aClassConvertMap);
			$sNewStatement = $sPrefix . $sClass;
			if ($sExtendsKeyword) {
				$sNewStatement .= $sExtendsKeyword . self::convertNsClassName($sExtendsClass, $sNamespace, $aClassConvertMap);
			}
			if ($sImplementsKeyword) {
				foreach (preg_split('/\s*,\s*/', $sImplementsClassList) as $sImplementsClass) {
					$sImplementsClassList = str_replace($sImplementsClass, self::convertNsClassName($sImplementsClass, $sNamespace, $aClassConvertMap), $sImplementsClassList);
				}
				$sNewStatement .= $sImplementsKeyword . $sImplementsClassList;
			}
			$sContent = str_replace($sStatement, $sNewStatement, $sContent);
		}

		// Convert scope resolution operator lvalue
		preg_match_all('/\b([A-Z][\w\\\\]+)(\s*::)/', $sContent, $aMatchesList, PREG_SET_ORDER);
		foreach ($aMatchesList as $aMatches) {
			$sExpression = $aMatches[0];
			$sNewExpression = self::convertNsClassName($aMatches[1], $sNamespace, $aClassConvertMap) . $aMatches[2];
			$sContent = str_replace($sExpression, $sNewExpression, $sContent);
		}

		// Convert new and instanceof operator rvalue
		preg_match_all('/\b(new\s+|instanceof\s+)([A-Z][\w\\\\]+)/', $sContent, $aMatchesList, PREG_SET_ORDER);
		foreach ($aMatchesList as $aMatches) {
			$sExpression = $aMatches[0];
			$sNewExpression = $aMatches[1] . self::convertNsClassName($aMatches[2], $sNamespace, $aClassConvertMap);
			$sContent = str_replace($sExpression, $sNewExpression, $sContent);
		}

		// Convert absolute classnames everywhere
		while (preg_match('/\\\\[A-Z][\w\\\\]+/', $sContent, $aMatches)) {
			$sContent = str_replace($aMatches[0], preg_replace('/\\\\+/', '_', substr($aMatches[0], 1)), $sContent);
		}

		return $sContent;
	}

	protected static function convertNsClassName($sClassName, $sNamespace, $aClassConvertMap)
	{
		if (in_array($sClassName, array('self', 'parent', 'static'))) {
			return $sClassName;
		}
		if (substr($sClassName, 0, 1) != '\\') {
			if (isset($aClassConvertMap[$sClassName])) {
				$sClassName = $aClassConvertMap[$sClassName];
			}
			else {
				$sClassName = $sNamespace . $sClassName;
			}
		}
		return str_replace('\\', '_', trim($sClassName, '\\'));
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
