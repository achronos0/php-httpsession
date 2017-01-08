# Csv #

This class is a flexible delimited-text parser/generator.

It handles CSV (comma-separated values), TSV (tab-separated values), and other delimited text
formats.

It can read and write large amounts of data in batches.

## Overview ##

In most cases, you can use one of the static methods to read/write CSV in one line:

*	`read`
	
	Read a CSV file into a two-dimensional array:

		$aData = Csv::read($sFilePath);

*	`write`

	Write a CSV file from an array:

		$aData = array(
			array(
				'Column A' => 3,
				'Column B' => 66,
				'Column C' => 'example 1',
			),
			array(
				'Column A' => 9,
				'Column B' => 312,
				'Column C' => 'example 2',
			),
		);
		Csv::write($sFilePath, $aData);

*	`append`

	Append to an existing CSV file:

		Csv::append($sFilePath, $aData);

*	`parse`

	Convert an in-memory CSV string to an array:

		$aData = Csv::parse($sCsvContent);

*	`generate`

	Convert an array to a CSV string:

		$sCsvContent = Csv::generate($aData);

*	`download`

	Output CSV content to browser from an array

		Csv::download($aData, $sFileName);

When handling large amounts of data, create a batch reader/writer object:

*	`createReader`

	Create batch reader to read from file in stages:

		// Open file and prepare for reading
		$oCsvReader = Csv::createReader($sFilePath);
		
		// Read file in batches of 1000 records
		while (($aData = $oCsvReader->readBatch(1000))) {
			// Handle each record in this batch
			foreach ($aData as $aRec) {
				// ...
			}
		}

		// Get total records found
		$iTotalRecords = $oCsvReader->getRecordIndex();

		// Close file
		$oCsvReader->close();

*	`createWriter`

	Create batch writer to write to file in stages:

		// Open file and prepare for writing
		$oCsvWriter = Csv::createWriter($sFilePath);

		// Do your thing
		while (...) {
			// Generate a record of data
			$aRec = array ( ... );

			// Write record to file
			$oCsvWriter->writeRecord($aRec);
		}

		// Close file when done
		$oCsvWriter->close();

*	`createStringReader`

	As `createReader`, but use in-memory string as source instead of file contents.

		// Prepare for reading from string
		$oCsvReader = Csv::createStringReader($sCsvContent);

*	`createStringWriter`

	As `createWriter`, but store generated content in memory instead of writing to file.

	Call `getContent()` when writing is complete to retrieve full delimited content.

		// Prepare for writing to string
		$oCsvWriter = Csv::createStringWriter();

		// Write data in stages
		while (...) {
			$aRec = array( ... );
			$oCsvWriter->writeRecord($aRec);
		}

		// Retrieve delimited content
		$sCsvContent = $oCsvWriter->getContent();

		// Free memory
		$oCsvWriter->close();

Pass options to control how data is read and written: adjust the file
format, control field names, skip some records, etc.

Simple options examples:

	# Read from a TSV (tab-separated values) file, instead of CSV
	$aData = Csv::read(
		$sFilePath,
		array(
			'format' => 'tsv',
		)
	);

	# Write a TSV file using a two-dimensional vector array
	$aData = array(
		array(
			3,
			66,
			'example 1',
		),
		array(
			9,
			312,
			'example 2',
		),
	);
	Csv::write(
		$sFilePath,
		$aData,
		array(
			'format' => 'tsv',
			'associative' => false,
			'column_names' => array(
				'Column A',
				'Column B',
				'Column C',
			),
		)
	);

## Formats ##

By default, this class expects to work with Comma Seperated Values (CSV) content, as defined by
RFC 4180 (mostly).

It can read and write other variants of delimited text content. Use options to specify the format
you are working with.

Standard formats:

#### `csv`
Common CSV (Comma Separated Values).

This is the default format.

This format is similar to CSV according to RFC 4180, but there are a few differences when writing data:

*	When writing files on Unix, record separator (i.e. newline) is `LF` not `CR+LF`.
*	Field values are only quoted when absolutely necessary.
	Field values that contain double-quote but do not contain any ambiguous text are NOT quoted.
	Double-quote is only escaped (doubled) within a quoted field.
	Double-quote in a non-quoted field is not escaped (it is not doubled).

#### `csv_rfc`
Strict CSV per RFC 4180.

This is similar to default `csv` format, except more strictly conforms to RFC 4180:
*	Record separators (i.e. newlines) are always `CR+LF`, never `LF`.
*	Field values are quoted if they contain any double-quote or comma or newline, and double-quote
	is always escaped (doubled)

#### `tsv`
Tab Separated Values format.

This format is compatible with the default format used by MySQL's `LOAD DATA INFILE` and
`SELECT INTO OUTFILE` SQL statements.


## General options ##

#### `header`
Whether first record is a header row or data:

*	`bool true`: first row is a header containing column labels
*	`bool false`: first row is data, there is no header
*	`null`: writing only, `associative` option controls header

For reading, default is `true`.
For writing, default is '`null`.

This option is ignored when appending to a non-empty file, no header is written in that case.

#### `associative`
Whether to treat record arrays as associative (i.e. named keys) or vector:

*	`true`: records are associative (keys are column headers)
*	`false`: records are vector (keys are numeric column indices)
*	`null`: writing only, autodetect from first data record

For reading, default is `true`.
For writing, default is `null`.

#### `column_names`
Field labels.

*	array: values define names for file columns and keys for associative array records
	
	Array element order defines column order in file.

*	`null`: determine names automatically from file header row (for reading) or first data row
	(for writing).

Default is `null`, determine names automatically.

#### `format`
Predefined file format name.

Default is `csv`.

Note that for more control you can use some or all of the specific formatting options, see below.

## Read options ##

#### `start_record`
(int) Skip this many records before starting to process data (does not count header).

This option is not supported in batch reader mode.
To skip records in batch mode, call `readBatch()` with param `$sMode` set to `"skip"`.

#### `max_records`
(int) Stop after reading this many records (does not count header or skipped records)

This option is not supported in batch reader mode.
To skip records in batch mode, call `readBatch()` with param `$sMode` set to `"skip"`.

#### `skip_records`
(array) Specify a set of instructions for which records to read and which records to skip.

Each element is a record index (starting at zero) at which a state change occurs.
Each state change switches reading from "on" (process records) to "off" (skip records), or vice
versa.
The process starts in the "on" state.

Summary:
*	The first entry in the array means "stop processing at this #"
*	The second entry in the array eans "start processing again at this #"
*	`start_record=X` is the same as `skip_records=[0, X]`
*	`max_records=X` is the same as `skip_records=[X]`
*	`start_record=X` and `max_records=Y` is the same as `skip_records=[0, X, X + Y]`

Examples:
*	`skip_records=[0,10]`

	Skip first ten (0-9), then read all remaining (10+).

*	`skip_records=[10]`
	
	Read first ten (0-9), then skip all remaining (10+).

*	`skip_records=[10,50,100]`
	
	Read first ten (0-9), then skip forty (10-49), then read fifty (50-99),
	then skip all remaining (100+).

This option is not supported in batch reader mode.
To skip records in batch mode, call `readBatch()` with param `$sMode` set to `"skip"`.

#### `chunk_size`
(int) Fetch this many bytes during each file read.

Default is 128K (131072).

For efficiency, data is read from file in chunks rather than one line at a time.

By default this class optimizes for IO speed rather than memory usage, hence the fairly large size.

#### `max_field_length`
(int) Maximum allowed byte length for a field.

Default is 128K (131072).

If the parser detects a single field longer than this maximum, it assumes that the file is malformed.
Parsing is halted, and the current operation will throw an exception.

## Write options ##

#### `append`
How to handle an existing file:

*	`true`: starting writing new content at the end of the file
*	`false`: truncate the existing file then start writing content

Default is `false` -- the default is to overwrite an existing file.

#### `gzip`
Whether to write gzip compressed data:
*	`true`: force writing gzip compressed data.
*	`false`: check file name, write gzip if filename ends in ".gz" or ".gzip"

Set this option to `true` to force use of gzip for other filenames.

Default is `false`.

## Format options ##

These options control detailed aspects of the delimited text format.

Use these to handle custom/unusual formats.

#### `newline`
(string) Record delimiter text (i.e. row separator).

Default depends on format.

As a special case, when reading, if newline is set to `LF`, the parser will also correctly handle
`CR+LF`.

#### `delimiter`
(string) Field delimiter text (i.e. column separator).

Default depends on format; for csv format default is comma ( `,` ).

#### `quote`
Field enclosure (i.e. quotes).

When `delimiter` text or `newline` text appears in a field value, the field value will be
surrounded by this text (prepended to start and appended to end).

*	(string) field enclosure text
*	`false`: no enclosure is ever used. Data must be escaped, or MUST NOT contain field
	delimiter text.

Default depends on format; for csv format default is double-quote ( `"` ).

#### `escaped_newline`
When `newline` text appears in a field value, this text is used instead.

*	(string) record delimiter escape text
*	`false`: record delimiters are never escaped. Data MUST NOT contain record separator text.

Default depends on format.

#### `escaped_delimiter`
When `delimiter` text appears in a field value, this text is used instead.

*	(string) field delimiter escape text.
*	`false`: field delimiters are never escaped. Data must be enclosed (i.e. quoted), or MUST
	NOT contain field delimiter text.

Default depends on format.

#### `escaped_quote`
When `quote` text appears in a field value, this text is used instead.

*	(string) escape text for quote (field enclosure).
*	`false`: quotes are never escaped.

Default depends on format.

#### `quote_mode`
Strategy for handling quoting (field enclosure) and escaping field values.

Accepted quote modes are:

*	`quote`

	Typical CSV-style "quote when necessary" approach.

	Fields are quoted (enclosed by quote text) if the field value would otherwise be
	misinterpreted because it contains the delimiter text or newline text.

	If a field value contains a newline or a delimiter, it is quoted.

	If a field value begins or ends with a quote, it is quoted; apart from that case, quote
	alone (without a newline or a delimiter) does not require the field to be quoted.

	Newline and delimiter are never escaped.

	Quote is only escaped within a quoted field value.

*	`escape`
	
	Use escape sequences rather than quoting fields.
	
	Delimiter text and newline text within field values is prefixed or replaced by other text,
	so it is not misinterpreted as an actual field/record separator.

	Field values are never quoted.

	Newline and delimiter are always escaped.

	Quote is never escaped (there is no special-meaning "quote" text in this strategy).

*	`quote_strict`
	
	Strict quoting rules per RFC 4180.
	
	If a field value contains a newline or a delimiter or a quote, it is quoted.
	
	Newline and delimiter are never escaped.
	
	Quote is always escaped.

*	`quote_all`

	Profuse "quote everything" approach.

	All field values are quoted.

*	`escape_and_quote`
	
	Employ both quoting and escape sequences.

	This strategy is not recommended for writing; it's intended for reading only in cases where
	a confused tool has both enclosed AND escaped text. Some do.

	Example malformed quoted-and-escaped CSV field value:

		"this value was both ""quoted"" and escaped\, so quote doubling and backslashes must both be stripped"
	
	All field values are quoted.

	Newline, delimiter and quote are always escaped.

Note that when reading, all "quote_*" modes behave identically, as follows:

*	If a field begins and ends with quote text, it is assumed to be quoted.

	The leading and trailing quote text stripped.

	Any cases of the escaped quote text found in the value are replaced with the quote text.

*	Otherwise it is assumed not to be quoted.
	
	All characters in the value are treated literally, no text replacements are performed.

	Any cases of the quote text or escaped quote text found in the value are left as-is.

*	Specific quoting rules according to the specified mode are not enforced.
	
	Theoretical violations are accepted and do not produce any warning or error.

	So long as the content is intelligible it will be parsed.

	For example `quote_strict` does not reject content that is not RFC 4180 complaint.

Default depends on format.

#### `null_value`
(string) Text to use in place of PHP `null` value.

Default depends on format.

#### `true_value`
(string) Text to use in place of PHP boolean `true` value.

Default depends on format.

#### `false_value`
(string) Text to use in place of PHP boolean `false` value.

Default depends on format.

#### `escapes`
(array) Set of text transformations to apply.

Each element is one transformation: key is text to find, value is replacement text.
