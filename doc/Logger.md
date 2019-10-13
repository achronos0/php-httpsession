# Logger

This class is a message logger.

An instance of `Logger` represents a logging system, which can be comprised of multiple message sources and targets.

## Example

```php
// Setup logging system using built-in defaults
$oLogger = \Useful\Logger::getLogger();

// Write a message
$oLogger->write('my_log', 'error', 'Test error message');

// Write a message via PSR-3 compatible log object
$oMyLog = $oLogger->getLog('my_log');
$oMyLog->error('Test error message');

// Setup logging system using application-specific settings
/*
	1) Write messages from each named log to a separate file
	2) For very serious errors only, echo messages to page/screen immediately
	3) For log "my_log" only, process debugging messages (and all less "verbose" messages as well)
	4) For all other logs, only process warning and error messages
*/ 
$oLogger->setConfig(array(
	'writers' => array(
		'file' => array(
			'enabled' => true,
			'path' => './logs/{log}.txt',
		),
		'display' => array(
			'enabled' => true,
			'mask' => 'critical',
		),
	),
	'logs' => array(
		'my_log' => array(
			'mask' => 'debug',
		),
	),
	'default_log_config' => array(
		'mask' => 'problems',
	),
));

$oLogger->write('my_log', 'debug', 'This message will be processed');

$oLogger->write('other_log', 'debug', 'This message will be ignored');
$oLogger->write('other_log', 'warning', 'This message will be written to file');
$oLogger->write('other_log', 'emergency', 'This message will be displayed immediately and written to file');
```

## Concepts

### Logger

An instance of `Logger`, represents the entire logging subsystem.

Usually only one instance is used (i.e. class acts as a singleton). Retrieve main instance with `$oLogger = Logger::getLogger()`.

It is possible to construct multiple logging systems by calling the class constructor: `$oSeparateLogger = new Logger()`.

### Message

An event sent to the logger.

Each message has several pieces of data associated with it:
* Text message (`$sMessage` argument to `write()`). This can contain "{placeholders}" to be replaced from context data
* Severity level (`$mLevel` argument to `write()`), either a string label (e.g. "error") or int (e.g. 0x008)
* Time at which message was generated
* Optional additional context data (`$mData` argument to `write()`)
* Optional timing data (generated based on `$fStartTimer` argument to `write()`)

### Log

A named receiver of log messages.

Each log has its own configuration settings:
* Level mask, controls which messages to the log are processed and which are ignored.
* Writer list, with optional additional per-writer settings.

Any log name can receive messages at any time, log names do not beed to be registered in any way prior to use.

Likewise log configuration settings do not create the log or imply that it will be used.
You may declare config settings for a large set of logs that may or may not be used; the only cost is the size of the config array.

### Writer

A target or processor of log messages.

Each writer does something different with messages: show it, write it to file, email it, etc.

Standard writers included with Logger are:
* `callback` - Send messages to externally provided callback function.
* `display` - Output messages immediately to console or page.
* `file` - Write messages to file as plain-text.
* `csv` - Write messages to file as CSV.
* `redirect` - Send messages to other named logs.
* `trace` - Display log messages in an HTML comment block.

You can also define custom writers; see below.

Each writer has its own configuration settings:
* Enabled/disabled flag, controls whether any messages are sent to writer.
* Level mask, allows fine-grained control over which messages are sent to writer.
* Additional settings for the particular writer being used (e.g. log file name).

As with logs, you can configure writers without using them; until a message is actually sent to a writer it is not loaded.

### Severity level

Each message has a "level" indicating how severe of a problem it represents (or conversely how exhaustive a level of investigation is needed to care about it).

The defined levels are similar to (and compatible with) RFC 5424, as used in PSR-3, with several additional levels to aid in incremental debugging.

Levels:
* `emergency`
* `critical`
* `alert`
* `error`
* `warning`
* `notice`
* `info`
* `detail` (not from RFC)
* `debug`
* `debug2` (not from RFC)
* `debug3` (not from RFC)

### Level mask

Each log has an associated level mask which controls which messages are processed and which are ignored.
(Each writer and log-writer combination may also optionally have a level mask.)

Masks for level groups:
* `problems` includes `emergency`, `critical`, `alert`, `error`, `warning`
* `information` includes `notice`, `info`, `detail`
* `debugging` includes `debug`, `debug2`, `debug3`

Masks for verbosity:
* `none` includes nothing
* `important` includes `problems` plus `notice` - this is the built-in default mask for logs if no other configuration is provided
* `verbose` includes `problems` plus `information`, i.e. everything except debugging
* `all` includes all levels

Other mask formats are also accepted in case you need them:
* Each severity level is also a mask that includes itself plus all "more severe" (i.e. "less verbose") levels, e.g. `info` matches everything from `emergency` through `info`.
* To match one specific severity level only, prefix it with "=", e.g. `=info` matches only that exact level.
* Relative levels using "+" or "-", e.g. `detail+` (debug), `detail++` (debug2), `detail+2` (debug2), `error-` (alert), `error-3` (emergency)
* A level mask may be provided as an array or list of severity levels, e.g. `problems,=debug`. matches `problems` plus `debug` (but not `notice`, `info` or `detail`).

## Usage

### Creation

Most use cases only need one logging system, which means just one instance of `Logger`.

There's a convenience method for using Logger as a singleton class:

```php
$oLogger = \Useful\Logger::getLogger();
```

Only call the constructor directly if you need multiple separate logging configurations running in parallel:

```php
$oSeparateLogger = new \Useful\Logger();
```

### Configuration

You can pass an array of configuration settings to `\Useful\Logger::getLogger()`, or to the constructor, or to the `setOptions()` instance method:

```php
$oLogger->setOptions(array(
	'logs' => array(
		'my_log' => array(
			'mask' => 'info',
			'writers' => ('file'),
		),		
	),
	'writers' => array(
		'file' => array(
			'enabled' => true,
			'path' => './logs/{log}/{date}/log_{hour}.txt',
		),
	),
	'default_log_config' => array(
		'mask' => 'error',
	),
	'default_writer_config' => array(
		'enabled' => false,
	),
));
```

You can also configure one log or writer at a time:

```php
$oLogger->setLogConfig(
	'my_log',
	array(
		'mask' => 'info',
		'writers' => ('file'),
	)
);

$oLogger->setWriterConfig(
	'file',
	array(
		'enabled' => true,
		'path' => './logs/{log}/{date}/log_{hour}.txt',
	)
);
```

See below for a detailed reference of configuration settings.

### Write messages

Log a message by calling `write()`:

```php
$oLogger->write('my_log', 'warning', 'This is an example warning message');

$oLogger->write(
	'my_log',
	'info',
	'This is an example message with additional data',
	array(
		'foo' => 'bar',
		'baz' => array(1, 2, 3),
	)
);

$fMyTimer = $oLogger->getTimer();
// ... do stuff
$oLogger->write(
	'my_log',
	'debug',
	'This is an example message with a timer',
	array( /* data */ ),
	$fMyTimer
)
```

You can also interact with a log via a PSR-3-compliant object:

```php
$oMyLog = $oLogger->getLog('my_log');

$oMyLog->warning('This is an example warning message');

$oMyLog->info(
	'This is an example message with additional data',
	array(
		'foo' => 'bar',
		'baz' => array(1, 2, 3),
	)
);

$fMyTimer = $oLogger->getTimer();
// ... do stuff
$oMyLog->debug(
	'This is an example message with a timer',
	array( /* data */ ),
	$fMyTimer
);
```

### Flush messages

Some writers keep an internal queue of received messages, and only fully process messages in batches (e.g. file writer).

By default this happens automatically at the end of the request, before PHP exits.

Explicitly tell the logging system to process queued messages now by calling `flush()`:

```php
$oLogger->flush();
```

This can be useful e.g. during long running requests.

You can also configure each writer to automatically "flush" itself when its internal queue grows past a certain size:

```php
$oLogger->setWriterConfig(
	'file',
	array(
		'autoflush' => true,
	)
);
```

## Configuration reference

The following are keys that can be passed into `setOptions()`:
* `logs`
* `writers`
* `default_log_config`
* `default_writer_config`
* `level_numbers`, `level_masks`, `level_display`, `log_class`

The convenience method `setLogConfig()` is equivalent to calling `setOptions()` and passing a single named log in `logs`.

Similarly, the convenience method `setWriterConfig()` is equivalent to calling `setOptions()` and passing a single named writer in `writers`.

### `logs`

Set default settings for specific named logs.

Key name is log name. Value is array of named configuration settings.

Log configuration settings:
* (string|array) `mask` - Level mask sets which messages are processed for the log, and which are ignored.
* array `writers` - Defines which writers to send this log's messages to. Can be list with writer names as values; or map with writer names as keys, and log-writer config settings array as values.

Log settings are applied in this order:
1. Built-in defaults.
1. Configured defaults for all logs (`default_log_config`).
1. Configured settings for the named log (`logs`).

### `writers`

Define writers (log message handlers) that are available to process log messages.

Key is writer name. Value is array of named configuration settings.

Some writer settings are universal and apply to all writers, while others are specific to the Writer class used.

Standard writer configuration settings:
* bool `enabled` - TRUE to send messages to writer, FALSE to skip writer.
* (string|array) `mask` - For more fine-grained control, level mask sets which messages are processed for the writer, and which are ignored.
* (string|null) `class` - Optional, fully-qualified class name to use for this writer. If `null` then the writer name is used as class name to find a class in the `\Useful\Logger\Writer` namespace, e.g. `"file"` uses class `\Useful\Logger\Writer\File`.
* object `obj` - Writer instance to use for this writer.

See below for per-writer-class configuration settings.

Writer config settings are applied in this order:
1. Built-in defaults.
1. Configured defaults for all writers (`default_writer_config`).
1. Configured settings for the named writer (`writers`).
1. Per-log writer settings provided by log config

### `default_log_config` and `default_writer_config`

Provide defaults to be applied when no specific per-log or per-writer setting is provided.

### `level_numbers`, `level_masks`, `level_display`, `log_class`

Defines available severity levels, named level masks, display names for levels, and PSR-3-compliant log object class name (the class used by `getLog()`).

You can redefine these if you want.

## Standard writers

### Callback

Send messages to externally provided callback function.

Writer config settings:
* callable `call` - Function to call to process messages. Required.
* `queue`, `max_messages`, `autoflush` - This is a queued writer, default `queue=log`. See below for details on queue management config settings.

See below for details.

### Csv

Write messages to CSV file.

Writer config settings:
* string `path` - Filepath to write log messages to. Default is `"./logs/{log}.csv"`
* `queue`, `max_messages`, `autoflush` - This is a queued writer, default `queue=log`. See below for details on queue management config settings.

The provided path may contain placeholders:
* `"{log}"` - Replaced by the message's log name. If queue=single this is the string `"combined"`.
* `"{date}"` - Replaced by the current date, in YYYYMMDD format.
* `"{hour}"` - Replaced by the current two-digit hour in 24-hour format.
* `"{minute}"` - Replaced by the current two-digit minute.

The directory will be created if it does not exist.

The file will be created if it does not exist, or appended to if it does exist.

### Display

Output messages immediately to console or page.

Writer config settings:
* (bool|null) `plain_text` - FALSE to output messages with HTML formatting; TRUE to output plain-text messages; NULL to use plain text for command-line, HTML otherwise.

### File

Write messages to plain-text file.

Writer config settings:
* string `path` - Filepath to write log messages to. Default is `"./logs/{log}.txt"`
* `queue`, `max_messages`, `autoflush` - This is a queued writer, default `queue=log`. See below for details on queue management config settings.

The provided path may contain placeholders:
* `"{log}"` - Replaced by the message's log name. If queue=single this is the string `"combined"`.
* `"{date}"` - Replaced by the current date, in YYYYMMDD format.
* `"{hour}"` - Replaced by the current two-digit hour in 24-hour format.
* `"{minute}"` - Replaced by the current two-digit minute.

The directory will be created if it does not exist.

The file will be created if it does not exist, or appended to if it does exist.

### Redirect

Send messages to other named logs.

Writer config settings:
* array `redirect_logs` - list of log names to re-post message to. Required. Messages are handled per each of those logs' settings.

### Trace

Display log messages in an HTML comment block.

Writer config settings:
* `queue`, `max_messages`, `autoflush` - This is a queued writer, default `queue=single`. See below for details on queue management config settings.

## Queued writers

Some writers maintain an internal queue of messages and only process them in batches at the end of the request (or when `flush()` is called).

Queue behaviour configuration settings:
* (string|false) `queue` - Queue mode, one of `"log"`, `"single"` or `false`.
* int `max_messages` - Maximum messages to keep in queue (in each queue for `queue=log`).
* bool `autoflush` - TRUE to automatically flush (process and dequeue) messages when queue is full; FALSE means excess messages emit a warning message then are discarded.

`queue="single"` keeps one combined queue of all messages sent to the writer, from all logs.

`queue="log"` keeps a separate queue per log name.

`queue=false` disables queueing, each message is processed immediately when it is generated.

## Using writers multiple times

You can reuse standard writers with different configurations, by specifying the writer class directly.

Example, use file twice to define different file naming (most logs are grouped by day, errors are grouped by hour:

```php
$oLogger->setConfig(array(
	'writers' => array(
		'file' => array(
			'path' => './logs/{log}/{date}.csv',
		),
		'error_file' => array(
			'class' => 'File', // or specify fully qualified classname '\\Useful\\Logger\\Writer\\File'
			'path' => './logs/{log}/{date}/log_{hour}.csv',
		),
	),
));
```

## Custom writers

The logging system can be extended with new processing functionality by adding more writers.

### Use `callback` writer

The simplest way to add new functionality is to use the callback writer:

```php
function my_custom_writer_callback($sQueue, $aWriterConfig, $aMessageList)
{
	// process $aMessageList somehow...
}

$oLogger->setWriterConfig(
	'callback',
	array(
		'call' => 'my_custom_writer_callback',
	)
);
```

Callback function signature:

```php
function callback($sQueue, $aWriterConfig, $aMessageList): void
```

Arguments:
* (string|null) `$sQueue` - log name, or null when writer config `queue=single`
* array `$aWriterConfig` - writer config settings as returned by Logger `getWriterConfig()`
* array `$aMessageList` - list of messages, each is message data as returned by Logger `prepMessage()`

Callback is a queued writer but queueing is disabled by default.
You can enable queueing by setting `queue="single"` or `queue="log"`, and optionally setting `max_messages` and `autoflush`.

Note that like any writer you can create multiple callback writers by using different names and specifying the `class` config setting.

### Create custom logger class that implements `AbstractWriter`

All writers are an instance of a writer class, which is any concrete subclass of `\Useful\Logger\AbstractWriter`.

To create a completely custom writer, write a class that `extends \Useful\Logger\AbstractWriter` and implements these methods:
* `commit()`
* `flush()`

See source for display writer (`src/Useful/Logger/Writer/Display.php`) for an example.

### Create custom queued logger class that implements`AbstractQueuedWriter`

You can reuse the queueing functionality used by most standard writers (e.g. file, trace) by extending `\Useful\Logger\AbstractQueuedWriter` (instead of `\Useful\Logger\AbstractWriter`).

This base class handles all queueing and dequeueing operations automatically, all you need to do is provide the method to actually process messages:

```php
protected function processMessages($sQueue, $aMessageList)
{
	foreach ($aMessageList as $aMessage) {
		echo $this->oLogger->formatMessage();
	}
}
```

You can also provide additional default config settings by redeclaring `getDefaultConfig()`, just make sure you provide defaults for the queueing options:

```php
protected function getDefaultConfig()
{
	return array_merge(
		parent::getDefaultConfig(),
		array(
			'my_custom_setting' => 'foo',
		)
	);
}
```

See source for callback writer (`src/Useful/Logger/Writer/Callback.php`) for an example.
