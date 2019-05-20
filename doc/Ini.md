# Ini #

This class is an INI text parser/generator.

It is compatible with most key-value store files used by other software, such as:
* Microsoft Windows .ini files
* Unix conf files (e.g. MySQL .cnf files, OpenSSH config files).
* Java .properties files

It supports common INI format features:
* Stripping whitespace
* Quoted values to preserve whitespace
* Sections to represent two-dimensional data

Additionally, it supports several unique extensions to the basic INI format:
* Multi-part key and section names to represent N-dimensional data
* Multi-line quoted values
* C-escapes in quoted values
* Automatic conversion of null, boolean and numeric values
* List values

## Overview ##

All methods are static:

*	`read`
	
	Read an INI file into an array:

		$aData = Ini::read($sFilePath);

*	`write`

	Write an INI file from an array:

		$aData = array(
			'a' => 'value',
			'b' => 66,
			'c' => array( 'foo', 'bar', 'baz' ),
			'd' => array(
				'e' => 1,
				'f' => false,
				'g' => array(
					'h' => 2,
					'i' => null,
				)
			),
		);
		Ini::write($sFilePath, $aData);

*	`parse`

	Convert an in-memory INI string to an array:

		$aData = Ini::parse($sIniContent);

*	`generate`

	Convert an array to an INI string:

		$sIniContent = Csv::generate($aData);

## INI Format ##

### Basics ###

Each line of the content sets a named value
	name=value

Spaces are allowed (and ignored)

	name =  value

Case does not matter in names, names are returned in lowercase

	name=value
	Name=same key

Duplicate names result in the last defined value being kept

	name=ignored
	name=this one is used

Use "[]" name notation to combine multiple values into an array

	name[]=append value (converts key to array if necessary)

### Comments ###

A line beginning with // or # is ignored

	# ignored
	// ignored

C-style multi-line comments are also supported
	```
	/*
		ignored
	*/
	```

Comments cannot be only part of a line

	name=value  # NOT ignored, this text is part of the value

### Hierarchy ###

Names containing dots ( . ) are subdivided into a hierarchy

	first.second.name=this creates a multi-level hierarchy
	first.second.other=this adds another value to first.second

Use "[section]" notation to place all following values in a hierarchy
	[first.second]
	name=same as key first.second.name

Section name "[general]" or "[]" returns to "top" section
	[general]
	name=back in top section
	[]
	name=also back in top section

### Values ###

Values can be quoted

	name='value'
	name="value"
	name='  spaces in quoted values are not ignored '
	name="quoted values "can contain quotes" too"
	name='quoted values can
	span multiple lines'
	name="multiline
	values "can contain quotes" too
	if a line ends in a quote, it must be escaped: \"
	as above
	"
	name="double quote values can contain C-style escapes like\nnewline\n\t\tand tabs\n"

Numeric values are converted to integer or float

	# this is returned as integer:
	name=12345
	# this is returned as float:
	name=123.45
	# C-style numbers work too:
	octal=0777
	hex=0xFFFF

Some special values are recognized (values are case-insensitive)

	# these are returned as boolean TRUE:
	name=true
	name=on
	name=yes
	name=y
	# these are returned as boolean FALSE:
	name=false
	name=off
	name=no
	name=n
	# these are returned as NULL:
	name=null
	name=none
	name=nothing
	# this is an empty array
	name=emptylist

A missing value is treated as NULL

	# this is NULL
	name=
	# this is an empty string
	name=''

=== Arrays ===

Use "name[]=" notation to combine multiple values into an array:

	name=one
	name[]=two
	# name is now:
	#   [0] => "one"
	#   [1] => "two"

An empty key name appends a value to the current section:

	[name]
	=foo
	=bar
	# the above is equivalent to:
	#   name[]=foo
	#   name[]=bar

Use "name=[a b c]" notation to create an array of values without multiple lines

	name=[one two three]
	name=[ one two, three; four;,; five; "six, quotes are allowed" ]
	name=[
		one
		two
		three
	]

Use "name={a:1 b:2}" notation to create a named array of values

	name={one:value, two: other_value, three: "quoted value"}
	name={
		a: one
		b: two
		c: three
	}

Use "name+=" name notation to combine array values

	name=[ one two ]
	name+= [ three four ]
	# name is now:
	#   [0] => "one"
	#   [1] => "two"
	#   [2] => "three"
	#   [3] => "four"
	name={
		a: one
		b: two
		c: three
	}
	name+={
		b: four
		d: five
	}
	# name is now:
	#       ["a"] => "one"
	#       ["b"] => "four"
	#       ["c"] => "three"
	#       ["d"] => "five"

### Duplicate names, arrays and merging ###

Normally a duplicate name completely removes the original value:

	name=foo
	name=bar
	# name is now "bar"

The same is true for array values:

	name=[ one two ]
	name=[ three four ]
	# name is now:
	#   [0] => "three"
	#   [1] => "four"
	name={ a: 1, b: 2, c: 3 }
	name={ b: foo, d: bar }
	# name is now:
	#       ["b"] => "foo"
	#       ["d"] => "bar"

To combine array values instead of replacing the original, use "name+=" notation:

	name=[ one two ]
	name+= [ three four ]
	# name is now:
	#   [0] => "one"
	#   [1] => "two"
	#   [2] => "three"
	#   [3] => "four"
	name={ a: 1, b: 2, c: 3 }
	name+={ b: foo, d: bar }
	# name now contains:
	#   ["a"] => 1
	#   ["b"] => "foo"
	#   ["c"] => 3
	#   ["d"] => "bar"

To append a new value instead of replacing the original, use "name[]=" notation:

	name=foo
	name[]=bar
	# name is now:
	#   [0] => "foo"
	#   [1] => "bar"

The same can be done with array values to create 2-dimensional arrays (i.e. a recordset)

	name=[ one two ]
	name[]= [ three four ]
	# name is now:
	#   [0] =>
	#       [0] => "one"
	#       [1] => "two"
	#   [1] =>
	#       [0] => "three"
	#       [1] => "four"
	name={ a: 1, b: 2, c: 3 }
	name[]={ b: foo, d: bar }
	# name now contains:
	#   [0] =>
	#       ["a"] => 1
	#       ["b"] => 2
	#       ["c"] => 3
	#   [1] =>
	#       ["b"] => "foo"
	#       ["d"] => "bar"

Remember that an empty name appends a value to the current section:

	[name]
	=foo
	=bar
	# the above is equivalent to:
	#   name[]=foo
	#   name[]=bar

In combination with array values this allows a shortcut method to create complex recordsets:

	[name]
	={ a: 1, b: "row one" }
	={ a: 2, b: "row two" }
	# name is now:
	#   [0] =>
	#       ["a"] => 1
	#       ["b"] => "row one"
	#   [1] =>
	#       ["a"] => 2
	#       ["b"] => "row two"

### Generated INI content ###

Notes on how the INI generator formats content:

* It can handle N-dimensional nested data.
* The generator does not output sections.
Nested arrays cause deep hierarchical keys to be output.
* The generator does output simple vector array values (lists).
So a simple vector array (e.g. `array( 'a', 'b', 'c' )`) will be output as a single line.

## Parser options ##

#### `element_separator`
(string) or (array<string>) One or more tokens to treat as element (i.e. line) separator.

Default is `CRLF`, `CR`, `LF`.

#### `pair_separator`
(string) or (array<string>) One or more tokens to treat as pair (key/value) separator.

Default is `=`.

#### `sections`
(bool) Whether to process sections.

Default is `true`.

#### `section_top_name`
(string) Special section name to consider equivalent to top-level section.

Default is `general`.

#### `hierarchy`
(bool) Whether to process hierarchical key and section names.

Default is `true`.

Disabling `hierarchy` also disables special key name processing
("name[]=value" and "name+=value").
Key and section names are always returned verbatim as given in the content.

#### `hierarchy_separator`
(string) or (array<string>) One or more tokens to treat as hierarchy separator.

Default is `.`.

#### `comments`
(bool) Whether to process line comments.

Default is `true`.

#### `comment_start`
(string) or (array<string>) One or more tokens to treat as the start of a line comment.

Default is `#`, `//`.

#### `multiline_comments`
(bool) Whether to process multi-line comments.

Default is `true`.

#### `multiline_comment_open`
(string) or (array<string>) One or more tokens to treat as opening a multi-line comment.

Default is `/*`.

#### `multiline_comment_close`
(string) or (array<string>) One or more tokens to treat as closing a multi-line comment.

For multiple tokens, each open/close pair is matched.
Paired tokens must be provided in the same order.

Default is `*/`.

#### `quotes`
(bool) Whether to process quoted values.

Default is `true`.

If disabled, quoted values are returned verbatim without interpretation.
Multi-line values are not possible without quotes.

To disable all interpretation of values, disable `quotes` and `lists`, and provide an empty
`special_values`.

#### `lists`
(bool) Whether to process array (list) values.

Default is `true`.

If disabled, list values are returned verbatim without interpretation.

To disable all interpretation of values, disable `quotes` and `lists`, and provide an empty
`special_values`.

#### `list_open`
(string) or (array<string>) One or more tokens to treat as opening a value list.

Default is `[`, `{`.

#### `list_close`
(string) or (array<string>) One or more tokens to treat as closing a value list.

Default is `]`, `}`.

#### `special_values`
(array) Map of INI values that should be parsed as a specific PHP value.

Key is plain text to look for (in uppercase). Value is PHP value to substitute for it.

Default is:

	["TRUE"] => true
	["ON"] => true
	["YES"] => true
	["Y"] => true
	["FALSE"] => false
	["OFF"] => false
	["NO"] => false
	["N"] => false
	["NULL"] => null
	["NONE"] => null
	["NOTHING"] => null
	["EMPTYLIST"] => array()

Value comparison is case-insensitive. Text matches MUST be provided in uppercase.

#### `data_handler_call`
(callable) Call an external routine for each key/value pair parsed.

Use this option to bypass default key/value handling and substitute custom logic.

Note, if `data_handler_call` is provided then parse methods will _not_ return any value, instead the callable must process/store the data.

Function signature:

	function (string $sSection, string $sKey, mixed $mValue): bool?

Arguments:

* `$sSection`: Current raw section name
* `$sKey`: Raw key name
* `$mValue`: Parsed value

Returns:

* `bool FALSE`: Stop processing. No further content will be parsed
* other: Continue processing

Using a custom data handler effectively disables `hierarchy` and special key name processing
("name[]=value" and "name+=value").

Section and key names are provided verbatim as they appear in the content.
It's up to the custom data handler to interpret them.

## Generator options ##

#### `element_separator`
(string) Token to use as element (i.e. line) separator.

Default is PHP line ending (`PHP_EOL`), i.e. `CRLF` on Windows or `LF` otherwise.

#### `pair_separator`
(string) Token to use as pair (key/value) separator.

Default is `=`.

#### `hierarchy_separator`
(string) Token to use as hierarchy separator.

Default is `.`.

#### `lists`
(bool) Whether to output array values for list values.

(List values are one-dimensional vector arrays that contain only scalar/null values).

Default is `true`.

Disabling `lists` causes such values to be output on multiple lines.

With `lists`:

	name=[a b c]

Without `lists`:

	name.0=a
	name.1=b
	name.2=c

#### `list_separator`
(string) Token to use as list value separator.

Default is space (` `).

#### `list_open`
(string) Token to use when opening a value list.

Default is `[`.

#### `list_close`
(string) Token to use when closing a value list.

Default is `]`.

#### `null_value`
(string) Value to output for NULL.

Default is `NOTHING`.

#### `true_value`
(string) Value to output for bool TRUE.

Default is `YES`.

#### `false_value`
(string) Value to output for bool FALSE.

Default is `NO`.

#### `empty_array_value`
(string) Value to output for empty array.

Default is `[]`.
