<?php
/**
 * \Useful\HttpClient class
 *
 * @link https://github.com/morvren-achronos/useful
 * @copyright Morvren-Achronos 2019, licensed under Apache 2.0
 * @package Useful
 */

namespace Useful;

/**
 * Manage an HTTP session
 *
 * @uses \Useful\Exception
 * @uses extension curl
 */
class HttpClient
{
	//////////////////////////////
	// Public static

	/**
	 * Set global default parameters
	 *
	 * Global default parameters are used as initial session defaults for new session objects.
	 *
	 * Common global default parameters:
	 *   * ssl_ca_file
	 *   * ssl_ignore_cert
	 *   * charset
	 *   * agent
	 *
	 * See docs for a reference of all available call parameters.
	 *
	 * @param array $aParams global default call parameters to set
	 * @return bool true
	 */
	public static function setDefaultParams($aParams)
	{
		self::$aDefaultParams = self::prepParams($aParams, self::$aDefaultParams);
		return true;
	}

	/**
	 * Get global default parameters
	 *
	 * Global default parameters are used as initial session defaults for new session objects.
	 *
	 * @return array all global default call parameters
	 */
	public static function getDefaultParams()
	{
		return self::$aDefaultParams;
	}

	/**
	 * Add custom call types
	 *
	 * In addition to the standard built-in call 'type' values (get, form, multipart, etc.), it
	 *  is possible to define custom types.
	 *
	 * Each type has a name, which is the value used in the 'type' call parameter, and an array of
	 *  definition data.
	 *
	 * At the moment only one definition element is defined:
	 *   handler
	 *     (callback) handler function that prepares data when this type is used in a call
	 *
	 * Handler function signature:
	 *   function (array $aParams, HttpSession $oHttp): array
	 *
	 * Arguments:
	 *   array       $aParams all call parameters
	 *   HttpSession $oHttp   session object
	 *
	 * Return: array callback may return any/all of these keys:
	 *   post_mode
	 *     string on of the following: get form data file
	 *   post_data
	 *     mixed finalized request body, formatted per post_mode
	 *     See documentation.
	 *   params
	 *     array call parameters to change.
	 *
	 * @param array $aCallTypes new call types to register
	 * @return void
	 */
	public static function registerCallTypes($aCallTypes)
	{
		self::$aCallTypes = array_merge(self::$aCallTypes, $aCallTypes);
	}

	/**
	 * Return all registered call types
	 *
	 * @return array call type names and definitions
	 */
	public static function getRegisteredCallTypes()
	{
		return self::$aCallTypes;
	}


	//////////////////////////////
	// Public

	/**
	 * Return an object for making HTTP/HTTPS requests
	 *
	 * @param array $aParams session default call parameters to set
	 */
	public function __construct($aParams = array())
	{
		$this->aParams = self::prepParams($aParams, self::$aDefaultParams);
	}

	/**
	 * Set session default parameters
	 *
	 * Session default parameters are applied to all requests made by this object.
	 *
	 * Common session default parameters:
	 *   * host
	 *   * ssl
	 *   * charset
	 *   * headers
	 *
	 * See docs for a reference of all available call parameters.
	 *
	 * @param array $aParams session default call parameters to set
	 * @return bool true
	 */
	public function setParams($aParams)
	{
		$this->aParams = self::prepParams($aParams, $this->aParams);
		return true;
	}

	/**
	 * Get session default parameters
	 *
	 * Session default parameters are applied to all requests made by this object.
	 *
	 * @return array session default call parameters
	 */
	public function getParams()
	{
		return $this->aParams;
	}

	/**
	 * Store additional data with session
	 *
	 * This additional data is not used by HttpSession but can be used by attached custom
	 *  routines (e.g. parser_callback, logger_callback).
	 *
	 * @param array $aData session additional data
	 * @param bool  $bClear true to remove existing data, false to merge old and new data
	 * @return bool true
	 */
	public function setData($aData, $bClear = false)
	{
		$this->aParams['data'] =
			$bClear
			? $aData
			: array_merge($this->aParams['data'], $aData)
		;
		return true;
	}

	/**
	 * Get session additional data
	 *
	 * This additional data is not used by HttpSession but can be used by attached custom
	 *  routines (e.g. parser_callback, logger_callback).
	 *
	 * @return array session additional data
	 */
	public function getData()
	{
		return $this->aParams['data'];
	}

	/**
	 * Get reference to session additional data
	 *
	 * This additional data is not used by HttpSession but can be used by attached custom
	 *  routines (e.g. parser_callback, logger_callback).
	 *
	 * @return array-ref session additional data
	 */
	public function &getDataRef()
	{
		return $this->aParams['data'];
	}

	/**
	 * Execute an HTTP request and return response content
	 *
	 * Call is executed based on call parameters passed in $aParams, using session defaults
	 *  for parameters that are not specified.
	 *
	 * Common call-time call parameters:
	 *   * url
	 *   * headers
	 *   * post
	 *   * type
	 *
	 * See docs for a reference of all available call parameters.
	 *
	 * The function will return the response content as a string, unless the 'download' parameter
	 *  is in use.
	 *
	 * On a communication failure or HTTP error, the function will return false.
	 * Call httpError() to get a text description of the error.
	 *
	 * To always return response content, even when the server returns an error HTTP status code,
	 *  use the 'ignore_failure' call parameter.
	 *
	 * To gather detailed information about the disposition of the call (on success or failure),
	 *  pass the $aResults param.
	 * The variable will be populated with data about the request and response:
	 *   success
	 *     (bool) true on success, false on error
	 *   error
	 *     (string) error message, same as returned by httpError()
	 *   curl_errno
	 *     (int) cURL error number
	 *   http_status
	 *     (int) HTTP status code
	 *   content
	 *     string) response content body
	 *   headers
	 *     (string) response headers
	 *     includes headers from all responses, in cases of redirection
	 *   content_type
	 *     (string) response content MIME type
	 *   time
	 *     (float) elapsed request time in seconds
	 *   original_url
	 *     (string) URL originally requested
	 *   final_url
	 *     (string) URL actually returned
	 *     may differ from original_url, in cases of redirection
	 *   redirect_count
	 *     (int) number of redirects followed between original URL and actual URL
	 *   http_method
	 *     (string) request HTTP method, usually "GET" or "POST"
	 *   request_headers
	 *     (string) request headers sent (from final call, in cases of redirection)
	 *   post_data
	 *     (mixed) post data sent in original request
	 *
	 * @param array $aParams call-time call parameters
	 * @param array-ref &$aResults variable is populated with data about the call
	 * @return string response content; or false on failure; or true if 'download' parameter is set
	 * @throws \Useful\Exception on misconfiguration. Note does NOT throw an exception on HTTP failure.
	 */
	public function call($aParams, &$aResults = null)
	{
		// Finalize call data
		$aParams = self::prepParams($aParams, $this->aParams);

		// Validate call data
		if (empty($aParams['host']))
			throw new Exception('Call misconfigured: missing host');
		if (empty($aParams['path'])) {
			throw new Exception(
				'Call misconfigured: missing path (host: ' . $aParams['host'] . ')'
			);
		}
		if (!isset(self::$aCallTypes[$aParams['type']]))
			throw new Exception('Call misconfigured: invalid type "' . $aParams['type'] . '"');

		// Get custom routines
		$xTypeHandler = self::$aCallTypes[$aParams['type']]['handler'];
		$xParser = $aParams['parser_callback'];
		$xLogger = $aParams['logger_callback'];

		// Assemble URL
		$aParams['url'] = ($aParams['ssl'] ? 'https://' : 'http://') . $aParams['host'];
		if ($aParams['port'])
			$aParams['url'] .= ':' . $aParams['port'];
		$aParams['url'] .= $aParams['path'];
		$sQuery = self::queryToString($aParams['query'], $aParams['extra_query']);
		if ($sQuery)
			$aParams['url'] .= '?' . $sQuery;

		// Handle post data
		$sPostMode = 'get';
		$mPostData = null;
		if ($xTypeHandler) {
			$aData = $xTypeHandler($aParams, $this);
			if (!empty($aData['post_mode']))
				$sPostMode = $aData['post_mode'];
			if (isset($aData['post_data']))
				$mPostData = $aData['post_data'];
			if (!empty($aData['params']))
				$aParams = array_merge($aParams, $aData['params']);
		}
		elseif ($aParams['post']) {
			switch ($aParams['type']) {
				case 'form':
					$sPostMode = 'form';
					$mPostData = self::queryToString($aParams['post'], $aParams['extra_post']);
					break;
				case 'multipart':
					$sPostMode = 'form';
					$mPostData = self::queryToArray($aParams['post'], $aParams['extra_post']);
					break;
				case 'xml':
					$sPostMode = 'data';
					if (!$aParams['mime'])
						$aParams['mime'] = 'application/xml';
					if ($aParams['post'] instanceof DomDocument)
						$mPostData = $aParams['post']->saveXML();
					elseif ($aParams['post'] instanceof DomNode)
						$mPostData = $aParams['post']->ownerDocument->saveXML($aParams['post']);
					else
						$mPostData = strval($aParams['post']);
					if (
						!$aParams['charset']
						&& preg_match(
							'/<?xml[^>]*\nencoding\s*=\s*[\'"](\w+)/s',
							$mPostData,
							$aMatches
						)
					) {
						$aParams['charset'] = strtoupper($aMatches[1]);
					}
					break;
				case 'json':
					$sPostMode = 'data';
					if (!$aParams['mime'])
						$aParams['mime'] = 'application/json';
					if (is_array($aParams['post']))
						$mPortData = json_encode($aParams['post']);
					else
						$mPostData = strval($aParams['post']);
					break;
				case 'binary':
					$sPostMode = 'data';
					$mPostData = strval($aParams['post']);
					break;
				case 'file':
					$sPostMode = 'file';
					$mPostData = strval($aParams['post']);
					break;
				case 'multipart_complex':
					$sPostMode = 'data';
					if (!$aParams['mime'])
						$aParams['mime'] = 'multipart/form-data';
					if (preg_match('/;\s*boundary\s*=\s*=?([^\s"]+)/s', $aParams['mime'], $aMatches))
						$sBoundary = $aMatches[1];
					else {
						$sBoundary = md5(rand() . microtime());
						$aParams['mime'] .= '; boundary="' . $sBoundary . '"';
					}
					$mPostData = '';
					$sEol = chr(13) . chr(10);
					foreach ($aParams['post'] as $mKey => $aPart) {
						if (!empty($aPart['content']))
							$sPartBody = $aPart['content'];
						elseif (!empty($aPart['file']))
							$sPartBody = file_get_contents($aPart['file']);
						else
							$sPartBody = '';
						$aPartHeaders = array();
						$sDisposition =
							isset($aPart['disposition'])
							? $aPart['disposition']
							: 'form-data'
						;
						if ($sDisposition) {
							$sName =
								isset($aPart['name'])
								? $aPart['name']
								: (
									is_string($mKey)
									? $mKey
									: ('part' . ($mKey + 1))
								)
							;
							$sVal = 'Content-Disposition: ' . $sDisposition;
							if ($sName)
								$sVal .= '; name="' . $sName . '"';
							if (!empty($aPart['file']))
								$sVal .= '; filename="' . basename($aPart['file']) . '"';
							$aPartHeaders[] = $sVal;
						}
						if (empty($aPart['mime']) && !empty($aPart['charset']))
							$aPart['mime'] = 'text/plain';
						if (!empty($aPart['mime'])) {
							$sVal = 'Content-Type: ' . $aPart['mime'];
							if (!empty($aPart['charset'])) {
								$sVal .= '; charset=' . $aPart['charset'];
							}
							$aPartHeaders[] = $sVal;
						}
						if (!empty($aPart['headers']))
							$aPartHeaders = array_merge($aPartHeaders, $aPart['headers']);
						$mPostData .=
							'--' . $sBoundary . $sEol
							. implode($sEol, $aPartHeaders) . $sEol
							. $sEol
							. $sPartBody . $sEol
						;
					}
					$mPostData .= '--' . $sBoundary . '--' . $sEol;
					break;
			}
		}

		// Handle MIME type and charset
		if ($aParams['mime']) {
			foreach ($aParams['headers'] as $sVal) {
				if (preg_match('/^content-type\s*:/i', $sVal)) {
					$aParams['mime'] = null;
					break;
				}
			}
			if ($aParams['mime']) {
				if ($aParams['charset'])
					$aParams['mime'] .= '; charset=' . $aParams['charset'];
				$aParams['headers'][] = 'Content-Type: ' . $aParams['mime'];
			}
		}

		// Assemble per-request cURL settings
		if ($xParser)
			$aParams['url'] = $xParser($aParams['url'], true, $this);
		$aOptions = array(
			// bool
			CURLOPT_FOLLOWLOCATION => $aParams['max_redirects'] ? true : false,
			CURLOPT_SSL_VERIFYPEER => $aParams['ssl_ignore_cert'] ? false : true,
			// int
			CURLOPT_CONNECTTIMEOUT => $aParams['connect_timeout'],
			CURLOPT_MAXREDIRS => intval($aParams['max_redirects']),
			CURLOPT_SSL_VERIFYHOST => $aParams['ssl_ignore_cert'] ? 0 : 2,
			CURLOPT_TIMEOUT => $aParams['timeout'],
			// string
			CURLOPT_URL => $aParams['url'],
			CURLOPT_USERAGENT => $aParams['agent']
		);
		if ($aParams['ssl_ca_file'])
			$aOptions[CURLOPT_CAINFO] = $aParams['ssl_ca_file'];
		if ($aParams['ssl_ca_path'])
			$aOptions[CURLOPT_CAPATH] = $aParams['ssl_ca_path'];
		if ($aParams['auth'])
			$aOptions[CURLOPT_USERPWD] = $aParams['auth'];
		if ($aParams['close_connection']) {
			$aOptions[CURLOPT_FORBID_REUSE] = true;
			$aParams['headers'][] = 'Connection: close';
		}
		else
			$aOptions[CURLOPT_FORBID_REUSE] = false;
		if ($aParams['referer'])
			$aOptions[CURLOPT_REFERER] = $aParams['referer'];
		if ($aParams['track_cookies'])
			$aOptions[CURLOPT_COOKIEFILE] = tempnam("/tmp", "COOKIE");
		else
			$aOptions[CURLOPT_COOKIE] = null;
		switch ($sPostMode) {
			case 'get':
				$aOptions[CURLOPT_HTTPGET] = true;
				$sHttpMethod = 'GET';
				break;
			case 'form':
				if ($xParser)
					$mPostData = $xParser($mPostData, true, $this);
				$aOptions[CURLOPT_POST] = true;
				$aOptions[CURLOPT_POSTFIELDS] = $mPostData;
				$sHttpMethod = 'POST';
				break;
			case 'data':
				if ($xParser)
					$mPostData = $xParser($mPostData, false, $this);
				$aOptions[CURLOPT_POSTFIELDS] = $mPostData;
				$sHttpMethod = 'POST';
				break;
			case 'file':
				if ($xParser)
					$mPostData = $xParser($mPostData, false, $this);
				$aOptions[CURLOPT_PUT] = true;
				$aOptions[CURLOPT_INFILESIZE] = filesize($mPostData);
				$aOptions[CURLOPT_INFILE] = fopen($mPostData, 'r');
				$aOptions[CURLOPT_READFUNCTION] = array( self, '_curlRead' );
				$aOptions[CURLOPT_CUSTOMREQUEST] = $sHttpMethod = 'POST';
				break;
		}
		if ($aParams['http_method'])
			$aOptions[CURLOPT_CUSTOMREQUEST] = $sHttpMethod = $aParams['http_method'];
		$this->xDownloadCallback = null;
		if ($aParams['download']) {
			$aOptions[CURLOPT_HEADER] = false;
			$aOptions[CURLOPT_RETURNTRANSFER] = false;
			if (is_resource($aParams['download'])) {
				$aOptions[CURLOPT_FILE] = $aParams['download'];
			}
			elseif (is_callable($aParams['download'])) {
				$this->xDownloadCallback = $aParams['download'];
				$aOptions[CURLOPT_WRITEFUNCTION] = array( $this, '_curlWrite' );
			}
			else {
				$aOptions[CURLOPT_FILE] = fopen($aParams['download'], 'wb');
			}
		}
		else {
			$aOptions[CURLOPT_HEADER] = true;
			$aOptions[CURLOPT_RETURNTRANSFER] = true;
		}
		if ($aParams['headers'])
			$aOptions[CURLOPT_HTTPHEADER] = $aParams['headers'];

		// Initialize cURL session for this object
		if (!$this->rCurl) {
			// Create cURL resource
			$this->rCurl = curl_init();

			// Configure static cURL settings
			curl_setopt_array(
				$this->rCurl,
				array(
					CURLINFO_HEADER_OUT => true,
				)
			);
		}

		// Perform token replacement on all data
		if ($xParser) {
			foreach ($aOptions as $iKey => &$mValue) {
				if (is_array($mValue)) {
					foreach ($mValue as &$mSubVal)
						$mSubVal = $xParser($mSubVal, false, $this);
					unset($mSubVal);
				}
				elseif (is_string($mValue)) {
					if ($iKey == CURLOPT_POSTFIELDS || $iKey == CURLOPT_URL)
						continue;
					$mValue = $xParser($mValue, false, $this);
				}
			}
			unset($mValue);
		}

		// Configure dynamic cURL settings
		curl_setopt_array($this->rCurl, $aOptions);

		// Execute HTTP request and get response content
		$sResponseContent = curl_exec($this->rCurl);

		// Close input file
		if ($sPostMode == 'file')
			fclose($aOptions[CURLOPT_INFILE]);

		// Get request results data
		$aCurlResult = curl_getinfo($this->rCurl);

		// Get request status
		$iErrorNo = curl_errno($this->rCurl) ?: null;
		if (curl_errno($this->rCurl)) {
			$bSuccess = false;
			$sError = curl_error($this->rCurl) . ' (error #' . $iErrorNo . ')';
			$bTestContent = false;
		}
		elseif ($aCurlResult['http_code'] >= 400) {
			$bSuccess = false;
			$sError = 'server reported status code: ' . $aCurlResult['http_code'];
			$bTestContent = false;
		}
		else {
			$bSuccess = true;
			$sError = null;
			$bTestContent = (
				$sHttpMethod != 'HEAD'
				&& !in_array($aCurlResult['http_code'], array( 204, 205, 304 ))
				&& !$aParams['download']
			);
		}

		// Strip HTTP headers from response
		$sResponseHeaders = '';
		if ($sResponseContent) {
			while (
				in_array(substr($sResponseContent, 0, 9), array( 'HTTP/1.0 ', 'HTTP/1.1 ' ))
				&& (
					$iIndex = strpos($sResponseContent, chr(13) . chr(10) . chr(13) . chr(10))
				) !== false
			) {
				$sResponseHeaders .= substr($sResponseContent, 0, $iIndex + 4);
				if (strlen($sResponseContent) <= $iIndex + 4) {
					$sResponseContent = '';
					break;
				}
				$sResponseContent = substr($sResponseContent, $iIndex + 4);
			}
			$sResponseHeaders = trim($sResponseHeaders);
		}

		// Check returned content to determine success/failure
		if (
			$bTestContent
			&& $aParams['response_min_length']
			&& strlen($sResponseContent) < $aParams['response_min_length']
		) {
			$bSuccess = false;
			$sError = 'content is invalid (length is too short)';
		}
		if ($bTestContent && $aParams['response_parse_success']) {
			if (preg_match('/^([\/#@]).+\\1\w{0,2}$/', $aParams['response_parse_success'])) {
				if (!preg_match($aParams['response_parse_success'], $sResponseContent)) {
					$bSuccess = false;
					$sError = 'content is invalid (does not match success test)';
				}
			}
			elseif (strpos($sResponseContent, $aParams['response_parse_success']) === false) {
				$bSuccess = false;
				$sError = 'content is invalid (does not match success test)';
			}
		}
		if ($bTestContent && $bSuccess && $aParams['response_parse_failure']) {
			if (preg_match('/^([\/#@]).+\\1\w{0,2}$/', $aParams['response_parse_failure'])) {
				if (preg_match($aParams['response_parse_failure'], $sResponseContent)) {
					$bSuccess = false;
					$sError = 'content is invalid (matches failure test)';
				}
			}
			elseif (strpos($sResponseContent, $aParams['response_parse_failure']) !== false) {
				$bSuccess = false;
				$sError = 'content is invalid (matches failure test)';
			}
		}

		// Get validation data from content
		if ($aParams['auto_validate'] && $sResponseContent) {
			foreach (self::$aAutoValidateTests as $aTest) {
				if (preg_match($aTest[1], $sResponseContent, $aMatches))
					$this->aParams['extra_post'][$aTest[0]] = $aMatches[1];
			}
		}

		// Set session "last error"
		$this->sHttpError = $sError;

		// Assemble result data
		$aResults = array(
			'success' => $bSuccess,
			'error' => $sError,
			'curl_errno' => $iErrorNo,
			'http_status' => $aCurlResult['http_code'],
			'content' => $sResponseContent,
			'headers' => $sResponseHeaders,
			'content_type' => $aCurlResult['content_type'],
			'time' => $aCurlResult['total_time'],
			'original_url' => $aParams['url'],
			'final_url' => $aCurlResult['url'],
			'redirect_count' => $aCurlResult['redirect_count'],
			'http_method' => $sHttpMethod,
			'request_headers' =>
				isset($aCurlResult['request_header'])
				? trim($aCurlResult['request_header'])
				: null,
		);
		if ($sPostMode == 'form' && is_string($mPostData)) {
			$aResults['post_data'] = self::queryToArray($mPostData, null);
			$aResults['post_raw'] = $mPostData;
		}
		else
			$aResults['post_data'] = $mPostData;

		// Log message
		if ($xLogger) {
			$sMessage = 'http call';
			$sMessage .=
				' ('
				. $aResults['http_method']
				. ' ' . $aParams['host'] . $aParams['path']
				. ')'
			;
			if ($sError) {
				$sMessage .= ' - FAILED, ' . $sError;
				if ($aParams['ignore_failure'])
					$sMessage .= ' - IGNORING FAIL';
			}
			else
				$sMessage .= ' - OK';
			$xLogger($sMessage, $aResults, $this);
		}

		// Return response content
		if ($bSuccess || $aParams['ignore_failure']) {
			if ($aParams['download'])
				return true;
			return $sResponseContent;
		}

		// Return error
		return false;
	}

	/**
	 * Perform a sequence of HTTP requests
	 *
	 * Execute a series of HTTP requests.
	 *
	 * Each element in the call data array causes an HTTP request to be made, using the element
	 *  value as an array of call parameters, exactly as per call().
	 * Requests are executed one at a time, in array order.
	 * The result data array is populated in the same order, and using the same key as the call
	 *  data element.
	 *
	 * If a request fails (call() returns false) then the sequence is stopped, no further requests
	 *  are made.
	 *
	 * @param array $aCallData sequence of calls to execute. Each element is an array of call-time
	 *  call parameters
	 * @param array-ref &$aResultData variable is populated with call results. Each element is an
	 *  array of call results, per $aResults param from call()
	 * @return bool true on success (all calls succeeded), false on failure
	 */
	public function sequence($aCallData, &$aResultData = null)
	{
		$aResultData = array();
		foreach ($aCallData as $mKey => $aParams) {
			$aResults = null;
			$sResponseContent = $this->call($aParams, $aResults);
			$aResultData[$mKey] = $aResults;
			if ($sResponseContent === false)
				return false;
		}
		return true;
	}

	/**
	 * Return description of HTTP error from most recent call
	 *
	 * @return string http error description, or null if there was no error during the most recent
	 *  call
	 */
	public function httpError()
	{
		return $this->sHttpError;
	}


	//////////////////////////////
	// Internal static

	protected static $aAutoValidateTests = array(
		array( '__VIEWSTATE', '/name="__VIEWSTATE"[^>]+value="(.+?)"/' ),
		array( '__EVENTVALIDATION', '/name="__EVENTVALIDATION"[^>]+value="(.+?)"/' )
	);
	protected static $aDefaultParams = array(
		'ssl' => false,
		'ssl_ignore_cert' => false,
		'ssl_ca_file' => null,
		'ssl_ca_path' => null,
		'auth' => null,
		'host' => null,
		'port' => null,
		'path' => '/',
		'query' => null,
		'post' => null,
		'type' => 'form',
		'mime' => null,
		'charset' => null,
		'referer' => null,
		'agent' => null,
		'headers' => array(),
		'parser_callback' => null,
		'logger_callback' => false,
		'download' => false,
		'ignore_failure' => null,
		'response_min_length' => 1,
		'response_parse_success' => null,
		'response_parse_failure' => null,
		'track_cookies' => true,
		'auto_validate' => false,
		'timeout' => 100,
		'connect_timeout' => 10,
		'close_connection' => false,
		'max_redirects' => 3,
		'http_method' => null,
		'extra_query' => array(),
		'extra_post' => array(),
		'data' => array(),
	);
	protected static $aCallTypes = array(
		'get' => array(
			'description' => 'Fetch URL (no request body)',
			'handler' => false,
		),
		'form' => array(
			'description' => 'Standard form post',
			'handler' => false,
		),
		'multipart' => array(
			'description' => 'Multipart form post',
			'handler' => false,
		),
		'xml' => array(
			'description' => 'Send XML',
			'handler' => false,
		),
		'json' => array(
			'description' => 'Send JSON',
			'handler' => false,
		),
		'binary' => array(
			'description' => 'Send other data',
			'handler' => false,
		),
		'file' => array(
			'description' => 'Send file contents',
			'handler' => false,
		),
		'multipart_complex' => array(
			'description' => 'Complex multipart post',
			'handler' => false,
		),
	);

	/**
	 * Normalize call data parameters
	 *
	 * Internal use only, do not call directly.
	 *
	 * @internal
	 */
	public static function prepParams($aNew, $aFinal)
	{
		if ($aNew) {
			foreach ($aNew as $sName => $mValue) {
				$sName = strtolower($sName);
				switch ($sName) {
					// string
					case 'mime':
					case 'charset':
					case 'auth':
					case 'host':
					case 'ssl_ca_file':
					case 'ssl_ca_path':
					case 'agent':
					case 'response_parse_success':
					case 'response_parse_failure':
						$aFinal[$sName] = trim(strval($mValue));
						break;
					// bool
					case 'ssl':
					case 'ssl_ignore_cert':
					case 'ignore_failure':
					case 'track_cookies':
					case 'auto_validate':
					case 'close_connection':
						$aFinal[$sName] = $mValue ? true : false;
						break;
					// int
					case 'max_redirects':
					case 'port':
					case 'response_min_length':
					case 'timeout':
					case 'connect_timeout':
						$aFinal[$sName] = intval($mValue);
						break;
					// array
					case 'extra_query':
					case 'extra_post':
					case 'data':
						$aFinal[$sName] = is_array($mValue) ? $mValue : array();
						break;
					// callback
					case 'parser_callback':
					case 'logger_callback':
						$aFinal[$sName] = ($mValue && is_callable($mValue)) ? $mValue : null;
						break;
					// mixed
					case 'query':
					case 'post':
					case 'download':
						$aFinal[$sName] = $mValue;
						break;
					// special
					case 'url':
						if (preg_match(
							'#^(?:(?:http(s?):/*)?(?:([^@]*)@)?([^/:]+)(?::(\d+))?)?(?:(/[^?]*)(?:\?(.*))?)?$#',
							$mValue,
							$aMatches
						)) {
							if (!empty($aMatches[1]))
								$aFinal['ssl'] = true;
							if (!empty($aMatches[2]))
								$aFinal['auth'] = trim($aMatches[2]);
							if (!empty($aMatches[3]))
								$aFinal['host'] = trim($aMatches[3]);
							if (!empty($aMatches[4]))
								$aFinal['port'] = intval($aMatches[4]);
							if (!empty($aMatches[5]))
								$aFinal['path'] = trim($aMatches[5]);
							if (!empty($aMatches[6]))
								$aFinal['query'] = trim($aMatches[6]);
						}
						break;
					case 'path':
						$aFinal['path'] = strval($mValue);
						if (substr($aFinal['path'], 0, 1) != '/')
							$aFinal['path'] = '/' . $aFinal['path'];
						break;
					case 'type':
						$mValue = strtolower($mValue);
						if ($mValue == 'get') {
							$aFinal['type'] = null;
							$aFinal['post'] = $aNew['post'] = false;
						}
						else
							$aFinal['type'] = $mValue;
						break;
					case 'referer':
					case 'referrer':
						$aFinal['referer'] = trim($mValue);
						break;
					case 'headers':
						$aTemp = array();
						if ($mValue) {
							if (is_array($mValue)) {
								foreach ($mValue as $mKey => $mVal) {
									if (is_numeric($mKey))
										$aTemp[] = $mVal;
									elseif (is_array($mVal)) {
										foreach ($mVal as $sVal)
											$aTemp[] = $mKey . ': ' . trim($sVal);
									}
									else
										$aTemp[] = $mKey . ': ' . trim($mVal);
								}
							}
							else {
								foreach (preg_split('/\s*\n+\s*/', $mValue) as $sLine) {
									if (!$sLine)
										continue;
									$aTemp[] = $sLine;
								}
							}
						}
						$aFinal['headers'] = $aTemp;
						break;
					case 'http_method':
						$aFinal['http_method'] = strtoupper(trim(strval($mValue)));
						break;
						break;
				}
			}
		}
		return $aFinal;
	}

	/**
	 * Convert data array to query string
	 *
	 * Internal use only, do not call directly.
	 *
	 * @internal
	 */
	public static function queryToString($mValue, $aExtraData)
	{
		$aQueryData = self::queryToArray($mValue, $aExtraData);
		$aTemp = array();
		foreach ($aQueryData as $sName => $mVal) {
			if (is_array($mVal)) {
				foreach ($mVal as $sVal) {
					$aTemp[] =
						($sVal === null)
						? rawurlencode($sName)
						: (rawurlencode($sName) . '=' . rawurlencode($sVal))
					;
				}
			}
			else {
				$aTemp[] =
					($mVal === null)
					? rawurlencode($sName)
					: (rawurlencode($sName) . '=' . rawurlencode($mVal))
				;
			}
		}
		return $aTemp ? str_replace('%23', '#', implode('&', $aTemp)) : null;
	}

	/**
	 * Convert query string to data array
	 *
	 * Internal use only, do not call directly.
	 *
	 * @internal
	 */
	public static function queryToArray($mValue, $aExtraData)
	{
		$aQueryData = array();
		$aProcessData = null;
		// already an array, each element is one var=>val pair
		if (is_array($mValue))
			$aQueryData = $mValue;
		// multiline text, each line is one "var=val" pair
		elseif (is_string($mValue) && strpos($mValue, PHP_EOL) !== false) {
			$aProcessData = preg_split('/[\r\n]+/', trim($mValue));
		}
		// normal query string, "var=val&var=val"
		elseif (
			is_string($mValue)
			&& (
				strpos($mValue, '&') !== false
				|| strpos($mValue, '=') !== false
			)
		) {
			$aProcessData = explode('&', trim($mValue));
		}
		// not a normal query string
		else
			$aQueryData[strval($mValue)] = null;
		if ($aProcessData) {
			foreach ($aProcessData as $sTemp) {
				if (strpos($sTemp, '=') !== false) {
					list ($sName, $sValue) = explode('=', $sTemp, 2);
					$sName = rawurldecode(trim($sName));
					$sValue = rawurldecode($sValue);
				}
				else {
					$sName = rawurldecode($sTemp);
					$sValue = null;
				}
				if ($sName == '')
					continue;
				if (array_key_exists($sName, $aQueryData)) {
					if (is_array($aQueryData[$sName]))
						$aQueryData[$sName][] = $sValue;
					else
						$aQueryData[$sName] = array( $aQueryData[$sName], $sValue );
				}
				else
					$aQueryData[$sName] = $sValue;
			}
		}
		if ($aExtraData) {
			foreach ($aExtraData as $sName => $sValue) {
				if (array_key_exists($sName, $aQueryData)) {
					if (is_array($aQueryData[$sName]))
						$aQueryData[$sName][] = $sValue;
					else
						$aQueryData[$sName] = array( $aQueryData[$sName], $sValue );
				}
				else
					$aQueryData[$sName] = $sValue;
			}
		}
		return $aQueryData;
	}

	/**
	 * Handle cURL requests to read data while uploading file
	 *
	 * Internal use only, do not call directly.
	 *
	 * @internal
	 */
	public static function _curlRead($rCurl, $rFile, $iMaxBytes)
	{
		if (feof($rFile))
			return '';
		return fread($rFile, $iMaxBytes);
	}


	//////////////////////////////
	// Internal

	protected $aParams;
	protected $rCurl;
	protected $sHttpError;
	protected $xDownloadCallback;

	/**
	 * Internal use only, do not call directly.
	 * Handle cURL requests to write data while downloading file
	 *
	 * @internal
	 */
	public function _curlWrite($rCurl, $sContent)
	{
		$xWrite = $this->xDownloadCallback;
		if (!$xWrite($sContent))
			return false;
		return strlen($sContent);
	}

	/**
	 * Internal use only, do not call directly. Destroy object
	 *
	 * @internal
	 */
	public function __destruct()
	{
		if ($this->rCurl)
			@curl_close($this->rCurl);
	}
}
