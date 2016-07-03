# HttpSession #

This class is a cUrl wrapper, designed to make managing HTTP(S) requests more convenient.

## Overview ##

Quick example:

	# Begin an HTTP session
	$oHttp = new HttpSession();

	# Make a GET request and return response
	$sResponse = $oHttp->call(array(
		'url' => 'http://example.com',
	));

	# Make a form POST request and return response
	$sResponse = $oHttp->call(array(
		'url' => 'http://example.com',
		'post' => array(
			'foo' => 'bar',
			'baz' => 'bat',
		),
	));

All aspects of the HTTP request are controlled by an array of call parameters.

Commonly used call parameters:
*	`url`

	URL to request.

*	`type`

	Type and format of request.

	Common values: get, form, multipart.

*	`post`

	Request content (POST data) for request.

	Format depends on 'type' parameter.

*	`mime`

	Manually specify Content-Type header value.

*	`headers`

	Custom HTTP headers for request.

Call parameters can be set at three levels:
*	individual call

	Parameters can be provided for each HTTP(S) request.

	This is typically used to set URL path, post data and other parameters that are different for
	every call.

*	session defaults

	Parameters can be applied to a session object. These apply to every call by default.

	This is useful for settings that will apply to all calls in the session, e.g. hostname, SSL.

*	global defaults

	Parameters can be applied statically to the class.

	Global defaults are used as session defaults when constructing a new session object.

	They are useful for environmental settings, e.g. SSL CA path.

## Call parameters ##

#### `url`
URL to request.

Can be a complete and fully-qualified URL, or a partial URL.

Setting the 'url' parameter is identical to setting the equivalent URL part parameters:
*	ssl
*	host
*	port
*	path
*	query

If the provided URL is not complete and fully-qualified then the missing parts are not set, which
means they retain their default values. This is useful for e.g. making many requests to the same
remote server:
*	Set some parts of the URL by default when creating the session:

		`$oHttp = Http::create(array(
			'host' => 'example.com',
			'ssl' => TRUE,
		));`

*	Use a partial URL for each request:

		`$sResponse = $oHttp->call(array(
			'url' => '/path/to/page?foo=bar',
		));`

#### `ssl`
`true` to use HTTPS protocol (SSL/TLS).  
`false` to use HTTP protocol (no encryption).

Default is `false`.

#### `host`
Host/domain name to use for request (if not specified in url).

No default, a value must be provided either in url or in host param.

#### `port`
Port to use for request.

Default is 80 (if `ssl`=`false`) or 443 (if `ssl`=`true`).

#### `path`
Directory path of request (path and filename).

Default is `'/'` (root).

Relative paths are not supported. A forward-slash ( / ) will be prepended automatically to path, if it's missing.

#### `query`
URL (query) parameters to include in request.

Multiple formats are accepted:
*	associative array

	Parameter names and values, e.g.:

		array(
			'phrase' => 'hello world',
			'var2' => 'foo&bar',
		)

	The data should not be URL encoded.

*	vector array

	Parameter name/value pairs, e.g.:

		`array(
			'phrase=hello world',
			'var2=foo&bar',
			'var2=another value',
		)`

	The data should not be URL encoded.

*	string

	Finalized URL-encoded query string, e.g.:

		`"phrase=hello%20world&var2=foo%26bar&var2=another%20value"`

	The data must be correctly URL encoded.

Default is no query params.

#### `ssl_ignore_cert`
`false` to require valid SSL certificate signed by a trusted provider, per SSL/TLS.  
`true` to ignore SSL certificate errors - this means the request is encrypted but no attempt is made
to verify the remote host's identity.

Default is `false`.

#### `ssl_ca_file`
Path to SSL CA certificate file to use when verifying remote host identity.  
NULL to use cURL default CA file.

Default is NULL.

Has no effect is `ssl_ignore_cert` is `true` or `ssl_ca_path` is set.

Note: this is php `CURLOPT_CACERT` a.k.a. `curl --cacert`.

#### `ssl_ca_path`
OpenSSL indexed CA certificate directory path(s).

Multiple paths are accepted, separated by colon ( : ).

If set, `ssl_ca_file` is ignored.

Has no effect is `ssl_ignore_cert` is `true`.

CA directories must be indexed by OpenSSL, see curl documentation for details.

Note: this is php `CURLOPT_CAPATH` a.k.a. `curl --capath`.

#### `auth`
HTTP basic authentication credentials, in the format "username:password".

Default is no HTTP authentication is used.

#### `type`
Format of post (request) content.

This setting controls whether a GET or POST request is made.  
It also sets the format of the request post data, and in most cases it determines the request MIME type
(Content-Type header).

Supported types are:
*	`get`

	Perform GET request, not POST.

*	`form`

	Standard form data submission (application/x-www-form-urlencoded).

*	`multipart`

	Multipart form submission (multipart/form-data).

*	`xml`

	XML document submission (text/xml).

*	`json`

	JSON value submission (text/json).

*	`binary`

	Other content submission.

*	`file`

	Content submission from local file.

*	`multipart_complex`

	Multipart form submission with greater control over each MIME part.

See Request Types section, below, for details.

Default is `form` if `post` param is provided, or `get` if `post` param is missing or `false`.

#### `post`
Post (request) data to send.

Set to `false` to specify a GET request; otherwise the expected format of post data is determined by the
`type` setting.

See Request Types section, below, for details.

Default is `false`: if post param is not provided, GET method is assumed.

#### `mime`
MIME type of post data.

In most cases this is determined automatically based on `type`.

#### `charset`
Charset/character encoding of post data.

Default is to leave charset undefined.

#### `referer` (or `referrer`)
HTTP Referer header for request.

#### `agent`
HTTP User-Agent header for request.

Default is to leave user agent undefined

#### `headers`
Custom HTTP headers for request.

Multiple formats are accepted:
*	associative array

	keys are (string) header name  

	values are (string) header value or (array) multiple values (header is included more than once)

*	string

	Finalized HTTP headers text, e.g.  

		Header-Name: value
		Another-Header: value; option="yes"

#### `parser_callback`
Function to call to finalize templating data in parameters, prior to issuing each HTTP request.

Callback signature:

	function (string $sOriginalContent, bool $bUrlEncode, HttpSession $oHttp): string

#### `logger_callback`
Function to call after completing each call.

Callback signature:

	function (string $sMessage, array $aResults, HttpSession $oHttp): void

#### `ignore_failure`
`true` to return response content even if there was an error.  
`false` (default) returns `false` if request fails.

#### `download`
Download response content to file, rather than returning content as string.

Multiple formats are accepted:
*	string

	File path.

	Download content to specified file.

	If file exists it will be overwritten.

*	resource

	Open file handle.

	Write downloaded content to specified handle.

*	callback

	Custom handler routine.

	Send downloaded content in chunks to specified handler.

	Callback signature:

		function (string $sResponseContentChunk, HttpSession $oHttp): void

#### `response_min_length`
Minimum length of response content in bytes.

If response content length is less than this minimum, the call is treated as failed.

0 to disable this test (empty response content is OK).

Default is 1.  
This means that an empty response body is assumed to indicate an error by default, even if the
remote server does not return an error code.  
If an empty body is a legitimate possibility, set response_min_length to 0.

This parameter is ignored if no response body is expected according to the HTTP standard.  
No response body is expected if the request method is HEAD or the response status code is one of:
*	`204 No Content`
*	`205 Reset Content`
*	`304 Not Modified`

#### `response_parse_success`
Text or regular expression match (within response content) indicating success.

If response content does not contain text/does not match regex, the call is treated as failed.

This parameter is ignored if no response body is expected according to the HTTP standard.

#### `response_parse_failure`
Text or regular expression match (within response content) indicating failure.

If response content contains text/matches regex, the call is treated as failed.

This parameter is ignored if no response body is expected according to the HTTP standard.

#### `track_cookies`
`true` to mimic browser cookie handling (remember cookies set by remote server and automatically
return them in future requests).  
`false` to disable cookie handling.

Default is `true`.

#### `auto_validate`
`true` to pick up common server-side validation parameters from html page content (e.g. to handle
Microsoft ASP form validation).  
`false` to disable this functionality.

Default is `false`.

#### `timeout`
Maximum allowed time for call, in seconds.  
`false` is 0 to wait indefinitely.

This is the total time allowed for the entire call, including connection, request and response.

Default is 100.

#### `connect_timeout`
Maximum allowed time for TCP/IP connection process, in seconds.  
`false` or 0 to wait indefinitely.

Default is 10.

#### `close_connection`
`true` to explicitly close the low-level network (TCP) connection after this call.  
`false` to keep the connection open if possible, to be re-used by future calls.

Default is `false`.

#### `max_redirects`
Maximim number of redirects ("Location" headers) to follow.  
`false` or 0 to disable redirection entirely.

Default is 3.

#### `http_method`
Custom HTTP request method to use instead of `"GET"` or `"POST"`; e.g. `"PUT"`, `"DELETE"`, `"HEAD"`.

#### `extra_query`
Array of additional URL (query) parameter names and values to add to URL.

This can be used to specify some "global" values that must be passed with every request (e.g. a
session identifier or validation token).

#### `extra_post`
Array of additional form field names and values to add to POST data.

This can be used to specify some "global" values that must be passed with every request (e.g. a
session identifier or validation token).

#### `data`
Additional data to store with session.

This additional data is not used by HttpSession, but can be used by attached custom routines (e.g. parser_callback, logger_callback).

# Request types #

#### `get`
Perform GET request, not POST.

Post data is ignored.

This is the equivalent of setting param 'post' to `false`.

#### `form`
Standard form data submission (application/x-www-form-urlencoded).

Supported post data formats:
*	associative array

	Keys are (string) parameter names.

	Values are (string) parameter value or (array) multiple values (parameter is included
	more than once), e.g.:

		array(
			'phrase' => 'hello world',
			'var2' => array(
				'foo&bar',
				'another value'
			)
		)

*	string

	URL-encoded query string, e.g.:

		"phrase=hello%20world&var2=foo%26bar&var2=another%20value"

#### `multipart`
Multipart form submission (multipart/form-data).

Supported post data formats:
*	associative array
	per `form`
*	vector array
	per `form`

Multipart form fields may specify a file upload rather than a literal value.  
To upload a file, specify field value as:

	@<filepath>

or

	@<filepath>;type=<mime_type>

#### `xml`
XML document submission (text/xml).

Supported post data formats:
*	string

	Text content of xml document.

	Content is sent in request body as-is, no processing is performed.

*	object `DomDocument`

	Object representing XML document.

	Document is serialized automatically.

*	object `DomNode`

	Object representing a single XML node.

	Only this node and its descendents (not the entire document) are serialized.

*	object

	Any other object is serialized by converting to string.
	
	Any alternate XML library may be used, so long as the resulting object implements function
	`__toString`.

#### `json`
JSON value submission (text/json).

Supported post data formats:
*	string

	Text content of json value.

*	array

	Data is serialized automatically.

#### `binary`
Other content submission (MIME type should be specified separately).

Supported post data formats:
*	string

	Content to send in request body.

#### `file`
Content submission from local file (MIME type should be specified manually).

Supported post data formats:
*	string

	Path and filename of file to upload.

By default file content is uploaded using `"POST"` http method.  
To change this (e.g. to `"PUT"`), specify `http_method` param.

#### `multipart_complex`
Multipart form submission (multipart/form-data) with greater control over each MIME part.

Supported post data formats:
*	vector array

	Each value is (array) MIME part definition:

		content
			string
			Body content
		file
			string
			Body content filepath
			Either `content or file` is required.
		name
			string
			Name (typically form field name).
		disposition
			string
			Content-Disposition header.
			Default is `"form-data"`.
		mime
			string
			MIME type (Content-Type header).
			Default is to not specify.
		charset
			string
			Charset encoding.
			Default is to not specify.
		headers
			array
			Additional HTTP headers for attachment.

*	associative array

	As for vector array except `name` defaults to the array element's key.

## Custom call types ##

In addition to the built-in types described above, custom call types are possible.

To define a new call type (or override handling of a built-in type), call registerCallTypes().

Each type has a name, which is the value of the `type` call param, and an array of definition data.
For the moment the definition only has one defined element:

*	`handler`

	callback
	
	Function to call to generate finalized request body (POST data), set MIME, apply custom headers, etc.
	

Handler function signature:

	function (array $aParams, HttpSession $oHttp): array
	
Parameters:

*	`$aParams`

	Finalized call parameters to be used for this request.

*	`$oHttp`

	HttpSession object on which call is made.

Returns: `array` finalized POST, and other settings; format:

*	`post_mode`

	One of the following:
	
	`get`: No request body.  
	HTTP method defaults to `GET`.  

	`form`: Standard form post.  
	HTTP method defaults to `POST`.  
	This is php `CURLOPT_POST=true`, post data is `CURLOPT_POSTFIELDS`:  
	If post data is a string, it is assumed to be a URL-encoded query string, and content type defaults to `application/x-www-form-urlencoded`.  
	If post data is an array, it is assumed to be a map of non-URL-encoded query parameter names and string values, and content type defaults to `multipart/form-data`.
	
	`data`: Raw data.  
	HTTP method defaults to `POST`.  
	Post data must be a string of binary data, which will be used as the request body.  
	There is no default content type, MIME must be explicitly provided by type handler.
	
	`file`: File data.  
	HTTP method defaults to `POST`'.  
	Post data must be a valid file path. File contents will be used as the request body.
	
*	`post_data`

	Finalized request body, formatted according to `post_mode`.

*	`params`

	Array of call parameters to override.
	
	Common call parameters set by type handler are `mime`, `charset`, `headers`, 

## Examples ##
Create a new HTTP session:

	$oHttp = HttpSession::create();

Create a new HTTP session and specify some default call parameters:

	$oHttp = new HttpSession(array(
		'ssl' => true,
		'host' => 'www.example.com',
	));

Make a simple GET request and return response body content:

	$sContent = $oHttp->call(array(
		'path' => '/request/url/path',
		'query' => array(
			'get_param_a' => 1,
			'get_param_b' => 2,
		),
	));

Perform a regular form POST:

	$sContent = $oHttp->call(array(
		'path' => '/request/url/path',
		'post' => array(
			'post_param_a' => 1,
			'post_param_b' => 2,
		),
	));

Perform a multipart form POST:

	$sContent = $oHttp->call(array(
		'path' => '/request/url/path',
		'type' => 'multipart',
		'post' => array(
			'post_param_a' => 1,
			'post_param_b' => 2,
		),
	));
