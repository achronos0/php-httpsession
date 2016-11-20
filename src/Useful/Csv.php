<?php
/**
* Parse and generate delimited text data (CSV, TSV, etc.)
*
* @link https://github.com/achronos0/useful
* @copyright Ky Patterson 2016, licensed under Apache 2.0
*/

namespace Useful;

class Csv
{
	//////////////////////////////
	// Public static

	/**
	* Read file into an array
	*
	* Read all data from a delimited text file and return as a two-dimensional PHP array.
	*
	* @param string $sFilePath file path
	* @param array $aOptions configuration settings, see docs
	* @return array parsed data, or false on error
	* @throws \Useful\Exception
	*/
	public static function read($sFilePath, $aOptions = array())
	{
		return self::readInternal(true, $sFilePath, $aOptions);
	}

	/**
	* Parse string into an array
	*
	* Convert delimited text content into a two-dimensional PHP array.
	*
	* @param string $sContent delimited text content
	* @param array $aOptions configuration settings, see docs
	* @return array parsed data, or false on error
	* @throws \Useful\Exception
	*/
	public static function parse($sContent, $aOptions = array())
	{
		return self::readInternal(false, $sContent, $aOptions);
	}

	/**
	* Write file from an array
	*
	* Write a delimited text file using data from a two-dimensional PHP array.
	*
	* If file exists it will be truncated, unless 'append' option is included.
	* If file does not exist it will be created.
	*
	* @param string $sFilePath file path
	* @param array $aData two-dimensional array of data to write
	* @param array $aOptions configuration settings, see docs
	* @return void
	* @throws \Useful\Exception
	*/
	public static function write($sFilePath, $aData, $aOptions = array())
	{
		$oCsv = new self($aOptions);
		$oCsv->initWriter($sFilePath);
		$oCsv->writeBatch($aData);
		$oCsv->close();
	}

	/**
	* Append an array to a CSV file
	*
	* Append to a delimited text file using data from a two-dimensional PHP array.
	*
	* If file does not exist it will be created.
	*
	* @param string $sFilePath file path
	* @param array $aData two-dimensional array of data to write
	* @param array $aOptions configuration settings, see docs
	* @return void
	* @throws \Useful\Exception
	*/
	public static function append($sFilePath, $aData, $aOptions = array())
	{
		$aOptions['append'] = true;
		self::write($sFilePath, $aData, $aOptions);
	}

	/**
	* Generate string from array
	*
	* Generate delimited text content using data from a two-dimensional PHP array.
	*
	* @param array $aData two-dimensional array of data to write
	* @param array $aOptions configuration settings, see docs
	* @return string generated delimited text
	*/
	public static function generate($aData, $aOptions = array())
	{
		$oCsv = new self($aOptions);
		$oCsv->initWriter(false);
		$oCsv->writeBatch($aData);
		return $oCsv->getContent();
	}

	/**
	* Create a batch file reader
	*
	* Create an object for reading a delimited text file one section at a time.
	*
	* Batch reading allows very large files to be processed while using a limited amount of
	* memory.
	*
	* To quickly read an entire file into an array, call Csv::read() instead.
	*
	* @param string $sFilePath file path
	* @param array $aOptions configuration settings, see docs
	* @return Csv batch reader object
	* @throws \Useful\Exception
	*/
	public static function createReader($sFilePath, $aOptions = array())
	{
		$oCsv = new self($aOptions);
		$oCsv->initReader(true, $sFilePath);
		return $oCsv;
	}

	/**
	* Create a CSV string batch reader
	*
	* Create an object for reading a delimited text string in batches.
	*
	* To quickly parse a single array, call Csv::parse() instead.
	*
	* @param $sContent string delimited text content
	* @param array $aOptions configuration settings, see docs
	* @return Csv string reader object
	* @throws \Useful\Exception
	*/
	public static function createStringReader($sContent, $aOptions = array())
	{
		$oCsv = new self($aOptions);
		$oCsv->initReader(false, $sContent);
		return $oCsv;
	}

	/**
	* Create a file batch writer
	*
	* Create an object for writing or appending to a delimited text file one section at a time.
	*
	* If file exists it will be truncated, unless 'append' option is included.
	* If file does not exist it will be created.
	*
	* To generate delimited content in batches, but store content in memory instead of writing to
	*  file, pass false for $sFilePath.
	* Get the finalized content by calling getContent().
	*
	* To quickly write an entire file from a single array, call Csv::write() instead.
	* To quickly generate a delimited string in memory, call Csv::generate() instead.
	*
	* @param string $sFilePath file path
	* @param array $aOptions configuration settings, see docs
	* @return Csv batch file writer object
	* @throws \Useful\Exception
	*/
	public static function createWriter($sFilePath, $aOptions = array())
	{
		$oCsv = new self($aOptions);
		$oCsv->initWriter($sFilePath);
		return $oCsv;
	}

	/**
	* Create a string batch writer
	*
	* Create an object for generating delimited text content one section at a time.
	*
	* When done, get the finalized content by calling getContent().
	*
	* To quickly generate a delimited string in memory, call Csv::generate() instead.
	*
	* @param array $aOptions configuration settings, see docs
	* @return Csv batch string writer object
	* @throws \Useful\Exception
	*/
	public static function createStringWriter($aOptions = array())
	{
		$oCsv = new self($aOptions);
		$oCsv->initWriter($sFilePath);
		return $oCsv;
	}

	/**
	* Add custom predefined formats
	*
	* In addition to the standard built-in 'format' values (csv, tsv), it is possible to define
	* custom formats.
	*
	* Each format has a name, which is the value of the 'format' option, and an array of options
	*  to be automatically applied when that format is specified.
	*
	* @param array $aFormats new formats to register
	* @return void
	*/
	public static function registerFormats($aFormats)
	{
		self::$aFormats = array_merge(self::$aFormats, $aFormats);
	}

	/**
	* Return all registered predefined formats
	*
	* @return array call type names and definitions
	*/
	public static function getRegisteredFormats()
	{
		return self::$aFormats;
	}


	//////////////////////////////
	// Public

	/**
	* Read batch of records
	*
	* Read next section of records and return as a two-dimensional PHP array.
	*
	* This method is only valid for batch reader objects.
	*
	* Alternatively, pass $sMode param to quickly read forward in the file without fully processing
	*  the content.
	* This can achieve significant time and memory savings when you do not need to read the parsed
	*  data, e.g. when skipping records or transcribing the data exactly.
	* Accepted $sMode values:
	*   normal (or null)
	*     Normal parsing mode.
	*     Return value is (array) two-dimensional array of records read.
	*     At end-of-file, returns empty array.
	*   skip
	*     Skip records, do not return any data.
	*     Useful if you need to start at a certain line number, or you need an accurate line count
	*      but don't need the data.
	*     Return value is (int) number of records read.
	*     At end-of-file, returns 0.
	*   raw
	*     Return records as raw content.
	*     Useful if you need to have a known number of records, but do not need to process the
	*      data (e.g. copying a block of records to a new location, with no changes).
	*     Return value is (string) raw content read, including all delimiters and escapes.
	*     At end-of-file, returns empty string.
	*
	* @param int $iRecords maximum number of records to read, or null to read all remaining records
	* @param bool-ref &$bReadIsComplete variable is set to true if all records have been read
	* @param int-ref &$iRecordCount variable is set to actual number of records read
	* @param string $sMode reading mode: null/"normal", "skip" or "raw"
	* @return mixed parsed data. Format of parsed data depends on $sMode, see above
	* @throws \Useful\Exception
	*/
	public function readBatch(
		$iRecords = null, &$bReadIsComplete = null, &$iRecordCount = null, $sMode = null
	)
	{
		if ($this->mHandle === null || $this->bWriter)
			throw new Exception('Not a valid reader');

		/*
			$this->aReadParseTree
				[$iState]
					[$sChar]
						[$iTestIndex]
							0 # token length
							1 # token text
							2 # parser action
							3 # replacement text
			parser actions
				0 text replacement
				1 delimiter, next field is unquoted
				2 newline, next field in unquoted
				3 delimiter + quote, next field is quoted
				4 newline + quote, next field is quoted

			parser states:
				-1 end of file
				-2 end of file
				0 start of batch (not in a value)
				1 in unquoted value
				2 in quoted value
				4 in special mode, start of batch
				5 in special mode, unquoted value
				6 in special mode, quoted value
		*/

		// Check whether read has already completed
		if ($this->bReadIsComplete) {
			$bReadIsComplete = true;
			$iRecordCount = 0;
			switch ($sMode) {
				case 'skip':
					return 0;
				case 'raw':
					return '';
			}
			return array();
		}

		// Prep return vars
		$bReadIsComplete = false;
		$iRecordCount = 0;
		$aFinalData = array();
		$sFinalRawData = '';

		// Prep content buffer tracking
		$iBufferOffset = 0;
		$iBufferLength = strlen($this->sReadBuffer);
		$iBufferMaxOffset = $iBufferLength - $this->iReadMaxTokenLength;

		// Prep parser tracking
		$bLimitRecords = $iRecords ? true : false;
		$aCurrentRec = array();
		$sCurrentVal = '';
		if ($this->iRecordIndex != -1 && ($sMode == 'skip' || $sMode == 'raw')) {
			$bStoreData = false;
			$iState = $this->bReadUseQuotes ? 4 : 5;
			$iHeaderLength = 0;
		}
		else {
			$bStoreData = true;
			$iState = $this->bReadUseQuotes ? 0 : 1;
		}
 		$bEscapes = $this->aEscapesFind ? true : false;

		// Run parser
		// $mem = memory_get_usage(true);
		while (true) {
			// Read more content into buffer if needed
			if ($iBufferOffset > $iBufferMaxOffset) {
				// Caller wants raw content, store handled buffer content
				if ($sMode == 'raw')
					$sFinalRawData .= substr($this->sReadBuffer, 0, $iBufferOffset);

				// Check max field length
				elseif (strlen($sCurrentVal) > $this->aOptions['max_field_length']) {
					$this->close();
					throw new Exception(
						'Max field length exceeded at record ' . $this->iRecordIndex
					);
				}

				// Remove handled portion of buffer
				$this->sReadBuffer = substr($this->sReadBuffer, $iBufferOffset);
				$iBufferOffset = 0;

				// Read next chunk of file into buffer
				switch ($this->iFileType) {
					// string
					case 0:
						if ($this->mHandle == '')
							break;
						if (strlen($this->mHandle) > $this->aOptions['chunk_size']) {
							$this->sReadBuffer .= substr(
								$this->mHandle,
								0,
								$this->aOptions['chunk_size']
							);
							$this->mHandle = substr(
								$this->mHandle,
								$this->aOptions['chunk_size']
							);
							break;
						}
						$this->sReadBuffer .= $this->mHandle;
						$this->mHandle = '';
						break;

					// file
					case 1:
						if (feof($this->mHandle))
							break;
						$sBuffer = fread($this->mHandle, $this->aOptions['chunk_size']);
						if ($sBuffer === false) {
							throw new Exception(
								'Error while reading from file, offset ' . ftell($this->mHandle)
							);
						}
						$this->sReadBuffer .= $sBuffer;
						unset($sBuffer);
						break;

					// gzip file
					case 2:
						if (gzeof($this->mHandle))
							break;
						$sBuffer = gzread($this->mHandle, $this->aOptions['chunk_size']);
						if ($sBuffer === false) {
							throw new Exception(
								'Error while reading from gzip file, offset '
									. gztell($this->mHandle)
							);
						}
						$this->sReadBuffer .= $sBuffer;
						unset($sBuffer);
						break;
				}

				// Recalculate buffer length
				$iBufferLength = strlen($this->sReadBuffer);
				if ($iBufferLength) {
					$iBufferMaxOffset =
						($iBufferLength <= $this->iReadMaxTokenLength)
						? ($iBufferLength - 1)
						: ($iBufferLength - $this->iReadMaxTokenLength)
					;
				}
				else {
					// Buffer empty, at end of file
					$iState = ($iState == 2) ? -2 : -1;
					$this->bReadIsComplete = $bReadIsComplete = true;
				}
			}

			// Check current parser state
			switch ($iState) {
				// Normal mode, unquoted or quoted field
				case 1:
				case 2:
					// Find next relevant character
					$iLen = strcspn(
						$this->sReadBuffer,
						$this->aReadParseFind[$iState],
						$iBufferOffset
					);

					// Handle content between current offset and found character
					if ($iLen) {
						// Add content to current field value
						$sCurrentVal .= substr($this->sReadBuffer, $iBufferOffset, $iLen);

						// Set buffer offset to found character
						$iBufferOffset += $iLen;

						// No character was found, need to read more content, back to top
						if ($iBufferOffset == $iBufferLength)
							continue 2;
					}

					// Determine what token we found
					$sChar = $this->sReadBuffer[$iBufferOffset];
					$aMatchedTest = null;
					foreach ($this->aReadParseTree[$iState][$sChar] as $aTest) {
						if (substr($this->sReadBuffer, $iBufferOffset, $aTest[0]) == $aTest[1]) {
							$aMatchedTest = $aTest;
							break;
						}
					}

					// No matching token found
					if (!$aMatchedTest) {
						// Add found character to current field value
						$sCurrentVal .= $sChar;

						// Set buffer offset past the found character
						$iBufferOffset++;

						// Back to top
						continue 2;
					}

					// Set buffer offset past the matched token
					$iBufferOffset += $aMatchedTest[0];

					// Token is a text replacement
					if ($aMatchedTest[2] == 0) {
						// Add replacement text to current field value
						$sCurrentVal .= $aMatchedTest[3];

						// Back to top
						continue 2;
					}
					// Otherwise token is end of field or end of record

					// Handle end of field:

					// Check for null or boolean special value (unquoted field only)
					if ($iState == 1) {
						switch ($sCurrentVal) {
							case $this->aOptions['null_value']:
								$sCurrentVal = null;
								break;
							case $this->aOptions['true_value']:
								$sCurrentVal = true;
								break;
							case $this->aOptions['false_value']:
								$sCurrentVal = false;
								break;
						}
					}

					// Apply reverse escapes to field value
					if ($bEscapes && is_string($sCurrentVal)) {
						$sCurrentVal = str_replace(
							$this->aEscapesReplace,
							$this->aEscapesFind,
							$sCurrentVal
						);
					}

					// Store finalized field
					$aCurrentRec[] = $sCurrentVal;

					// Reset current field value
					unset($sCurrentVal);
					$sCurrentVal = '';

					// Set new unquoted/quoted state
					$iState = ($aMatchedTest[2] >= 3) ? 2 : 1;

					// End of field but not end of record, back to top
					if (($aMatchedTest[2] & 1) == 1)
						continue 2;

					// Continue below to handle end of record
					break;

				// Special mode, unquoted or quoted field
				case 5:
				case 6:
					// Find next relevant character
					$iLen = strcspn(
						$this->sReadBuffer,
						$this->aReadParseFind[$iState],
						$iBufferOffset
					);

					// Set buffer offset to found character
					$iBufferOffset += $iLen;

					// No character was found, need to read more content, back to top
					if ($iBufferOffset == $iBufferLength)
						continue 2;

					// Determine what token we found
					$sChar = $this->sReadBuffer[$iBufferOffset];
					$aMatchedTest = null;
					foreach ($this->aReadParseTree[$iState][$sChar] as $aTest) {
						if (substr($this->sReadBuffer, $iBufferOffset, $aTest[0]) == $aTest[1]) {
							$aMatchedTest = $aTest;
							break;
						}
					}

					// No matching token found
					if (!$aMatchedTest) {
						// Set buffer offset past the found character
						$iBufferOffset++;

						// Back to top
						continue 2;
					}

					// Set buffer offset past the matched token
					$iBufferOffset += $aMatchedTest[0];

					// Token is a text replacement
					if ($aMatchedTest[2] == 0)
						continue 2;
					// Otherwise token is end of field or end of record

					// Set new unquoted/quoted state
					$iState = ($aMatchedTest[2] >= 3) ? 6 : 5;

					// End of field but not end of record, back to top
					if (($aMatchedTest[2] & 1) == 1)
						continue 2;

					// Continue below to handle end of record
					break;

				// Start of batch and quotes are enabled, normal mode or special mode
				case 0:
				case 4:
					// Check whether first character is a quote (parsing rules don't handle this):
					if (
						substr(
							$this->sReadBuffer,
							0,
							strlen($this->aOptions['quote'])
						) == $this->aOptions['quote']
					) {
						$iState += 2;
						$iBufferOffset += strlen($this->aOptions['quote']);
					}
					else
						$iState += 1;

					// Back to top
					continue 2;

				// End of file, unterminated quoted field
				case -2:
					// If the file doesn't end on a newline, the parsing rules don't detect the
					// quote. So if the field value ends in the quote, strip it
					if (
						substr(
							$sCurrentVal,
							-1 * strlen($this->aOptions['quote'])
						) == $this->aOptions['quote']
					) {
						$sCurrentVal = substr($sCurrentVal, 0, -1 * strlen($this->aOptions['quote']));
					}
					// continue with EOF handling below

				// End of file
				case -1:
					// Check for blank line preceding EOF
					if (!count($aCurrentRec) && $sCurrentVal == '') {
						// We're done, stop processing
						break 2;
					}

					// Handle end of field:

					// Check for null or boolean special value (unquoted field only)
					if ($iState == -1) {
						switch ($sCurrentVal) {
							case $this->aOptions['null_value']:
								$sCurrentVal = null;
								break;
							case $this->aOptions['true_value']:
								$sCurrentVal = true;
								break;
							case $this->aOptions['false_value']:
								$sCurrentVal = false;
								break;
						}
					}

					// Apply reverse escapes to field value
					if ($bEscapes && is_string($sCurrentVal)) {
						$sCurrentVal = str_replace(
							$this->aEscapesReplace,
							$this->aEscapesFind,
							$sCurrentVal
						);
					}

					// Store finalized field
					$aCurrentRec[] = $sCurrentVal;

					// Continue below to handle end of record
					break;
			}

			// Handle end of record:

			// Record is header row
			if ($this->iRecordIndex == -1) {
				// Set column names
				if ($this->aOptions['column_names'] === null)
					$this->aOptions['column_names'] = $aCurrentRec;

				// Set column count
				$this->iReadColumnCount = count($aCurrentRec);

				// Reset record
				unset($aCurrentRec);
				$aCurrentRec = array();

				// Switch to special mode
				if ($sMode == 'skip' || $sMode == 'raw') {
					$bStoreData = false;
					if ($iState == 2) {
						$iHeaderLength = $iBufferOffset - strlen($this->aOptions['quote']);
						$iState = 6;
					}
					elseif ($iState == 1) {
						$iHeaderLength = $iBufferOffset;
						$iState = 5;
					}
				}
			}

			// Record is data
			elseif ($bStoreData) {
				// Count columns in this record
				$iCount = count($aCurrentRec);

				// Handle extra columns
				if ($this->iReadColumnCount < $iCount) {
					if ($this->aOptions['associative']) {
						for ($iCol = $this->iReadColumnCount; $iCol < $iCount; $iCol++)
							$this->aOptions['column_names'][$iCol] = 'col_' . ($iCol + 1);
					}
					else {
						for ($iCol = $this->iReadColumnCount; $iCol < $iCount; $iCol++)
							$this->aOptions['column_names'][$iCol] = $iCol;
					}
					$this->iReadColumnCount = $iCount;
				}

				// Store record as associative array
				if ($this->aOptions['associative']) {
					$aFinal = array();
					for ($iCol = 0; $iCol < $iCount; $iCol++)
						$aFinal[$this->aOptions['column_names'][$iCol]] = $aCurrentRec[$iCol];
					for ($iCol = $iCount; $iCol < $this->iReadColumnCount; $iCol++)
						$aFinal[$this->aOptions['column_names'][$iCol]] = null;
					$aFinalData[] = $aFinal;
				}

				// Store record as-is, as vector
				else {
					$aFinalData[] = $aCurrentRec;
					for ($iCol = $iCount; $iCol < $this->iReadColumnCount; $iCol++)
						$aFinalData[] = null;
				}

				// Reset record
				unset($aCurrentRec);
				$aCurrentRec = array();
			}

			// Increment record number
			$this->iRecordIndex++;

			// Increment read record count
			if ($this->iRecordIndex)
				$iRecordCount++;

			// Stop processing if at end of file or if batch record limit has been reached
			if ($iState < 0 || ($bLimitRecords && $iRecordCount == $iRecords))
				break;

			// Back to top
		}

		// Handle partially handled buffer
		if ($iBufferOffset) {
			// Caller wants raw content, store handled buffer content
			if ($sMode == 'raw')
				$sFinalRawData .= substr($this->sReadBuffer, 0, $iBufferOffset);

			// Remove handled portion of buffer
			$this->sReadBuffer = substr($this->sReadBuffer, $iBufferOffset);
		}

		// Handle batch ending with open quote
		if ($iState == 2) {
			// Add opening quote back to buffer
			$this->sReadBuffer = $this->aOptions['quote'] . $this->sReadBuffer;

			// Strip quote from raw CSV return
			if ($sMode == 'raw')
				$sFinalRawData = substr($sFinalRawData, 0, -1 * strlen($this->aOptions['quote']));
		}

		// Return parsed data
		switch ($sMode) {
			case 'skip':
				return $iRecordCount;
			case 'raw':
				// Strip header row
				if ($iHeaderLength)
					$sFinalRawData = substr($sFinalRawData, $iHeaderLength);
				return $sFinalRawData;
		}
		return $aFinalData;
	}

	/**
	* Read one record
	*
	* Read next record and return as a PHP array.
	*
	* This is the same as:
	*   $oRec = $oCsv->readBatch(1)
	*
	* This method is only valid for batch reader objects.
	*
	* @return array single parsed record, or null if there are no remaining records
	* @throws \Useful\Exception
	*/
	public function readRecord()
	{
		$aData = $this->readBatch(1);
		return $aData ? $aData[0] : null;
	}

	/**
	* Write batch of records
	*
	* Write next section of records from a two-dimensional PHP array.
	*
	* This method is only valid for batch writer objects.
	*
	* @param array $aData two-dimensional array of data to write
	* @return void
	* @throws \Useful\Exception
	*/
	public function writeBatch($aData)
	{
		if ($this->mHandle === null || !$this->bWriter)
			throw new Exception('Not a valid writer');

		if ($this->iRecordIndex <= 0) {
			// Determine column names from data
			if ($this->aOptions['column_names'] === null)
				$this->aOptions['column_names'] = array_keys(current($aData));

			// Autodetect associative and header options
			if ($this->aOptions['associative'] === null) {
				$this->aOptions['associative'] = false;
				foreach (array_keys($aData[0]) as $sKey) {
					if (is_string($sKey)) {
						$this->aOptions['associative'] = true;
						break;
					}
				}
			}
			if ($this->aOptions['header'] === null) {
				if ($this->aOptions['associative'])
					$this->aOptions['header'] = true;
				else {
					$this->aOptions['header'] = false;
					$this->iRecordIndex = 0;
				}
			}

			// Add header
			if ($this->iRecordIndex == -1) {
				array_unshift(
					$aData,
					$this->aOptions['associative']
						? array_combine(
							$this->aOptions['column_names'],
							$this->aOptions['column_names']
						)
						: $this->aOptions['column_names']
				);
			}
		}

		// Generate CSV content
		$sCsvContent = '';
		$iColumnCount = count($this->aOptions['column_names']);
		$iLineCut = -1 * strlen($this->aOptions['delimiter']);
		$iQuoteLen = strlen($this->aOptions['quote']);
		$iQuoteEndCut = -1 * $iQuoteLen;
		$bEscapes = $this->aEscapesFind ? true : false;
		foreach ($aData as $aRec) {
			$sCsvLine = '';

			// Handle each column
			for ($iCol = 0; $iCol < $iColumnCount; $iCol++) {
				// Get value
				$mKey =
					$this->aOptions['associative']
					? $this->aOptions['column_names'][$iCol]
					: $iCol
				;
				$mVal = isset($aRec[$mKey]) ? $aRec[$mKey] : null;

				// Convert value
				if (is_string($mVal)) {
					$sCsvVal = $mVal;
					$bText = true;
				}
				elseif (is_int($mVal) || is_float($mVal)) {
					$sCsvVal = strval($mVal);
					$bText = false;
				}
				elseif ($mVal === null) {
					$sCsvVal = $this->aOptions['null_value'];
					$bText = false;
				}
				elseif (is_bool($mVal)) {
					$sCsvVal = $this->aOptions[$mVal ? 'true_value' : 'false_value'];
					$bText = false;
				}
				elseif (is_object($mVal) && is_callable(array($mVal, '__toString'))) {
					$sCsvVal = strval($mVal);
					$bText = true;
				}
				else {
					$sCsvVal = $this->aOptions['null_value'];
					$bText = false;
				}

				if ($bText) {
					// Apply escapes
					if ($bEscapes) {
						$sCsvVal = str_replace(
							$this->aEscapesFind,
							$this->aEscapesReplace,
							$sCsvVal
						);
					}

					// Apply quotes
					if (
						(
							$this->aOptions['quote_mode'] == 'quote'
							&& (
								strpos($sCsvVal, $this->aOptions['newline']) !== false
								|| strpos($sCsvVal, $this->aOptions['delimiter']) !== false
								|| substr($sCsvVal, 0, $iQuoteLen) == $this->aOptions['quote']
								|| substr($sCsvVal, $iQuoteEndCut) == $this->aOptions['quote']
							)
						)
						|| (
							$this->aOptions['quote_mode'] == 'quote_strict'
							&& (
								strpos($sCsvVal, $this->aOptions['newline']) !== false
								|| strpos($sCsvVal, $this->aOptions['delimiter']) !== false
								|| strpos($sCsvVal, $this->aOptions['quote']) !== false
							)
						)
						|| $this->aOptions['quote_mode'] == 'quote_all'
						|| $this->aOptions['quote_mode'] == 'escape_and_quote'
					) {
						$sCsvVal =
							$this->aOptions['quote']
							. str_replace(
								$this->aOptions['quote'],
								$this->aOptions['escaped_quote'], $sCsvVal
							)
							. $this->aOptions['quote']
						;
					}
				}

				// Add to current record
				$sCsvLine .= $sCsvVal . $this->aOptions['delimiter'];

				// Increment record number
				$this->iRecordIndex++;
			}

			// Add record to content
			$sCsvContent .= substr($sCsvLine, 0, $iLineCut) . $this->aOptions['newline'];
		}

		// Write CSV content
		switch ($this->iFileType) {
			// string
			case 0:
				$this->mHandle .= $sCsvContent;
				break;

			// file
			case 1:
				fwrite($this->mHandle, $sCsvContent);
				break;

			// gzip file
			case 2:
				gzwrite($this->mHandle, $sCsvContent);
				break;
		}
	}

	/**
	* Write one record
	*
	* Write a single delimited text record from a one-dimensional PHP array.
	*
	* This method is only valid for batch writer objects.
	*
	* This is the same as calling
	*   $oCsv->writeBatch(array($aRecord));
	*
	* @param array $aRecord single record to write
	* @return void
	* @throws \Useful\Exception
	*/
	public function writeRecord($aRecord)
	{
		return $this->writeBatch(array( $aRecord ));
	}

	/**
	* Close file and end processing
	*
	* Explicitly close the file on which this object operates.
	* This leaves the object in an invalid state, no further calls are possible.
	*
	* @return bool true
	*/
	public function close()
	{
		if ($this->mHandle === null)
			return true;
		switch ($this->iFileType) {
			case 1:
				@fclose($this->mHandle);
				break;
			case 2:
				@gzclose($this->mHandle);
				break;
		}
		$this->mHandle = null;
		return true;
	}

	/**
	* Return file path
	*
	* Returns full path of file on which this object operates.
	*
	* @return string file path
	*/
	public function getPath()
	{
		return $this->sPath;
	}

	/**
	* Return record number
	*
	* Return next record index. This is the number of the record that will be read or written
	* next time readBatch() or readRecord() is called.
	*
	* The first record in the file is index zero.
	*
	* If there is a header row, it does not count as a record.
	* If the header row has not been read/written yet, this method returns -1.

	* For batch reader objects, once all content has been read (isComplete() returns true), this
	* method returns the total number of data records in the file.
	*
	* @return int next record index
	* @throws \Useful\Exception
	*/
	public function getRecordIndex()
	{
		if ($this->mHandle === null)
			throw new Exception('No file handle');
		return $this->iRecordIndex;
	}

	/**
	* Return column names
	*
	* @return array column names, null if columns are unknown
	* @throws \Useful\Exception
	*/
	public function getColumnNames()
	{
		if ($this->mHandle === null)
			throw new Exception('No file handle');
		return $this->aOptions['column_names'];
	}

	/**
	* Check whether file has been completely read
	*
	* Only valid for batch reader objects.
	*
	* @return bool true if reader has finished reading data, false if not
	* @throws \Useful\Exception
	*/
	public function isComplete()
	{
		if ($this->mHandle === null || $this->bWriter)
			throw new Exception('Not a valid reader');
		return $this->bReadIsComplete;
	}

	/**
	* Return generated content
	*
	* Return delimited text data generated by this object.
	*
	* This method is only valid for writer objects that do not write to file, created by
	*  createWriter() with $sFilePath = null.
	*
	* @return string generated delimited text
	* @throws \Useful\Exception
	*/
	public function getContent()
	{
		if ($this->mHandle === null || !$this->bWriter || $this->iFileType)
			throw new Exception('Not a valid string writer');
		return $this->mHandle;
	}

	/**
	* Return whether object can be used
	*
	* @return bool true if object is valid and can be read from/written to, false if object is
	*  invalid for use
	*/
	public function isValid()
	{
		return $this->mHandle;
	}

	/**
	* Return whether object is a reader or writer
	*
	* @return bool true if writer, false if reader
	*/
	public function isWriter()
	{
		return $this->bWriter;
	}

	/**
	* Return file type
	*
	* @return string file type, one of: "string", "file" or "gzip"
	*/
	public function getFileType()
	{
		switch ($this->iFileType) {
			case 0:
				return 'string';
			case 1:
				return 'file';
			case 2:
				return 'gzip';
		}
	}

	/**
	* Return current options
	*
	* @return array options
	*/
	public function getOptions()
	{
		return $this->aOptions;
	}

	/**
	* Change options
	*
	* Note: changing options can have unpredictable results. In particular:
	*   * Changing format-related options is not supported.
	*   * Reducing size of column_names array is not supported
	*
	* @param array $aOptions new options to set
	* @return void
	*/
	public function setOptions($aOptions)
	{
		$this->aOptions = array_merge($this->aOptions, $aOptions);
	}


	//////////////////////////////
	// Internal static

	/**
	* internal
	*/
	const LF = "\x0A";
	/**
	* internal
	*/
	const CRLF = "\x0D\x0A";

	protected static $aFormats = array(
		'csv' => array(
			'newline' => "\n",
			'delimiter' => ',',
			'quote' => '"',
			'escaped_newline' => '\\n',
			'escaped_delimiter' => '\\t',
			'escaped_quote' => '""',
			'quote_mode' => "quote",
			'null_value' => '',
			'true_value' => 'Y',
			'false_value' => 'N',
			'escapes' => array()
		),
		'csv_rfc' => array(
			'newline' => "\r\n",
			'delimiter' => ',',
			'quote' => '"',
			'escaped_newline' => '\\n',
			'escaped_delimiter' => '\\t',
			'escaped_quote' => '""',
			'quote_mode' => "quote_strict",
			'null_value' => '',
			'true_value' => 'Y',
			'false_value' => 'N',
			'escapes' => array()
		),
		'tsv' => array(
			'newline' => "\n",
			'delimiter' => "\t",
			'quote' => false,
			'escaped_newline' => '\n',
			'escaped_delimiter' => '\t',
			'escaped_quote' => false,
			'quote_mode' => 'escape',
			'null_value' => '\N',
			'true_value' => 'Y',
			'false_value' => 'N',
			'escapes' => array(
				"\\" => "\\\\",
				"\n" => '\n',
				"\r" => '\r',
				"\b" => '\b',
				"\t" => '\t',
				"\0" => '\0'
			)
		),
	);

	// Read all data from file or string
	protected static function readInternal($bFile, $sFilePath, $aOptions)
	{
		$oCsv = new self($aOptions);
		$oCsv->initReader($bFile, $sFilePath);

		// Check for record skip instructions
		$aSkip = null;
		if ($oCsv->aOptions['skip_records'])
			$aSkip = $oCsv->aOptions['skip_records'];
		elseif ($oCsv->aOptions['start_record']) {
			if ($oCsv->aOptions['max_records']) {
				$aSkip = array(
					0,
					$oCsv->aOptions['start_record'],
					$oCsv->aOptions['start_record'] + $oCsv->aOptions['max_records'] - 1
				);
			}
			else
				$aSkip = array( 0, $oCsv->aOptions['start_record'] );
		}
		elseif ($oCsv->aOptions['max_records'])
			$aSkip = array( $oCsv->aOptions['max_records'] );

		// No records to skip, read all in a single batch
		if (!$aSkip) {
			$aData = $oCsv->readBatch();
			$oCsv->close();
			return $aData;
		}

		// Read records in batches according to skip instructions
		$aAllData = array();
		if ($aSkip[0] == 0) {
			array_shift($aSkip);
			$bState = false;
		}
		else
			$bState = true;
		$bEof = false;
		$iLines = null;
		while ($aSkip) {
			$aData = $oCsv->readBatch(
				array_shift($aSkip),
				$bEof,
				$iLines,
				$bState ? null : 'skip'
			);
			if ($aData === false) {
				$oCsv->close();
				return $aData;
			}
			if (!$aData)
				break;
			if ($bState) {
				$aAllData = array_merge($aAllData, $aData);
				$bState = false;
			}
			else
				$bState = true;
			if ($bEof)
				break;
		}
		if ($bState && !$bEof) {
			$aData = $oCsv->readBatch();
			if ($aData !== false)
				$aAllData = array_merge($aAllData, $aData);
		}
		$oCsv->close();
		return $aAllData;
	}


	//////////////////////////////
	// Internal

	protected $aOptions;
	protected $bWriter;
	protected $iFileType; // 0 string, 1 file, 2 gzip
	protected $sPath;
	protected $mHandle;
	protected $iRecordIndex;
	protected $aEscapesFind;
	protected $aEscapesReplace;
	protected $bReadIsComplete;
	protected $sReadBuffer;
	protected $bReadUseQuotes;
	protected $iReadColumnCount;
	protected $iReadMaxTokenLength;
	protected $aReadParseTree;
	protected $aReadParseFind;

	// Create object, set up options
	protected function __construct($aOptions)
	{
		// Fill in default options
		$this->aOptions = array_merge(
			array(
				// General
				'header' => null,
				'associative' => null,
				'column_names' => null,
				'format' => 'csv',
				// Reader
				'start_record' => false,
				'max_records' => false,
				'skip_records' => null,
				'chunk_size' => 131072,
				'max_field_length' => 131072,
				// Writer
				'append' => false,
				'gzip' => false,
				// Format
				'newline' => null,
				'delimiter' => null,
				'quote' => null,
				'escaped_newline' => null,
				'escaped_delimiter' => null,
				'escaped_quote' => null,
				'quote_mode' => null,
				'null_value' => null,
				'true_value' => null,
				'false_value' => null,
				'escapes' => null
			),
			$aOptions
		);

		// Finalize format options
		if ($this->aOptions['format'] && isset(self::$aFormats[$this->aOptions['format']])) {
			foreach (self::$aFormats[$this->aOptions['format']] as $sKey => $mValue) {
				if ($this->aOptions[$sKey] === null)
					$this->aOptions[$sKey] = $mValue;
			}
		}
		if (!$this->aOptions['delimiter'])
			$this->aOptions['delimiter'] = ',';
		if (!$this->aOptions['newline'])
			$this->aOptions['newline'] = "\n";

		// Prepare value transformations
		$this->aEscapesFind = array_keys($this->aOptions['escapes']);
		$this->aEscapesReplace = array_values($this->aOptions['escapes']);
	}

	/**
	* Internal
	*/
	public function __destruct()
	{
		$this->close();
	}

	// Configure object as a reader
	protected function initReader($bFile, $sFilePath)
	{
		$this->bWriter = false;
		$this->sReadBuffer = '';
		$this->bReadIsComplete = false;

		// Set null options
		if ($this->aOptions['associative'] === null)
			$this->aOptions['associative'] = true;
		if ($this->aOptions['header'] === null)
			$this->aOptions['header'] = true;

		// Set handle-header flag
		$this->iRecordIndex = $this->aOptions['header'] ? -1 : 0;

		// Open file
		if ($bFile) {
			$this->iFileType = 1;

			// Confirm file exists
			$this->sPath = realpath($sFilePath);
			if (!file_exists($this->sPath))
				throw new Exception('Path does not exist');
			if (!is_file($this->sPath))
				throw new Exception('Path is not a file');
			if (!is_readable($this->sPath))
				throw new Exception('Path is not readable');

			// Open file for reading
			$this->mHandle = fopen($this->sPath, 'rb');
			if (!$this->mHandle)
				throw new Exception('Unable to open file');

			// Detect gzip archive
			if (fread($this->mHandle, 2) == "\x1F\x8B") {
				// Check for gzip extension
				if (!function_exists('gzopen'))
					throw new Exception('Cannot open gzip file, no fzip extension');

				// Reopen using gzip extension
				fclose($this->mHandle);
				$this->mHandle = gzopen($this->sPath, 'rb');
				if (!$this->mHandle)
					throw new Exception('Unable to open gzip archive');

				// Set gzip type
				$this->iFileType = 2;
			}
			else {
				rewind($this->mHandle);
			}
		}

		// Set up string buffer
		else {
			$this->iFileType = 0;
			$this->mHandle = $sFilePath;
		}

		// Set parsing flags
		$this->bReadUseQuotes = (
			$this->aOptions['quote_mode'] != 'escape'
			&& $this->aOptions['quote']
		);
		$this->iReadColumnCount =
			$this->aOptions['column_names']
			? count($this->aOptions['column_names'])
			: 0
		;

		// Assemble parsing rules
		$aRules = array(
			// Normal mode, unquoted field
			1 => array(),
			// Normal mode, quoted field
			2 => array(),
			// Special mode, unquoted field
			5 => array(),
			// Special mode, quoted field
			6 => array()
		);
		// escaping
		if (
			$this->aOptions['quote_mode'] == 'escape'
			|| $this->aOptions['quote_mode'] == 'escape_and_quote'
		) {
			if ($this->aOptions['escaped_delimiter']) {
				// unquoted field -- escaped delimiter
				$aRules[1][] = array(
					$this->aOptions['escaped_delimiter'],
					0,
					$this->aOptions['delimiter']
				);
			}
			if ($this->aOptions['escaped_newline']) {
				// unquoted field -- escaped newline
				$aRules[1][] = array(
					$this->aOptions['escaped_newline'],
					0,
					$this->aOptions['newline']
				);
				// special mode, unquoted field -- escaped newline
				$aRules[5][] = array(
					$this->aOptions['escaped_newline'],
					0,
					$this->aOptions['newline']
				);
			}
			if ($this->aOptions['quote_mode'] == 'escape_and_quote') {
				if ($this->aOptions['escaped_delimiter']) {
					// quoted field -- escaped delimiter
					$aRules[2][] = array(
						$this->aOptions['escaped_delimiter'],
						0,
						$this->aOptions['delimiter']
					);
				}
				if ($this->aOptions['escaped_newline']) {
					// quoted field -- escaped newline
					$aRules[2][] = array(
						$this->aOptions['escaped_newline'],
						0,
						$this->aOptions['newline']
					);
					// special mode, quoted field -- escaped newline
					$aRules[6][] = array(
						$this->aOptions['escaped_newline'],
						0,
						$this->aOptions['newline']
					);
				}
			}
		}
		// quotes
		if ($this->bReadUseQuotes) {
			// unquoted field -- delimiter + quoted field
			$aRules[1][] = array( $this->aOptions['delimiter'] . $this->aOptions['quote'], 3 );
			// unquoted field -- newline + quoted field
			$aRules[1][] = array( $this->aOptions['newline'] . $this->aOptions['quote'], 4 );
			if ($this->aOptions['newline'] == self::LF)
				$aRules[1][] = array( self::CRLF . $this->aOptions['quote'], 4 );
			// quoted field -- escaped quote
			$aRules[2][] = array( $this->aOptions['escaped_quote'], 0, $this->aOptions['quote'] );
			// quoted field -- delimiter + quoted field
			$aRules[2][] = array(
				$this->aOptions['quote']
					. $this->aOptions['delimiter']
					. $this->aOptions['quote']
					,
				3
			);
			// quoted field -- delimiter + unquoted field
			$aRules[2][] = array( $this->aOptions['quote'] . $this->aOptions['delimiter'], 1 );
			// quoted field -- newline + quoted field
			$aRules[2][] = array(
				$this->aOptions['quote'] . $this->aOptions['newline'] . $this->aOptions['quote'],
				4
			);
			if ($this->aOptions['newline'] == self::LF)
				$aRules[2][] = array(
					$this->aOptions['quote'] . self::CRLF . $this->aOptions['quote'],
					4
				);
			// quoted field -- newline + unquoted field
			$aRules[2][] = array( $this->aOptions['quote'] . $this->aOptions['newline'], 2 );
			if ($this->aOptions['newline'] == self::LF)
				$aRules[2][] = array( $this->aOptions['quote'] . self::CRLF, 2 );
			// special mode, unquoted field -- delimiter + quoted field
			$aRules[5][] = array( $this->aOptions['delimiter'] . $this->aOptions['quote'], 3 );
			// special mode, unquoted field -- newline + quoted field
			$aRules[5][] = array( $this->aOptions['newline'] . $this->aOptions['quote'], 4 );
			if ($this->aOptions['newline'] == self::LF)
				$aRules[5][] = array( self::CRLF . $this->aOptions['quote'], 4 );
			// special mode, quoted field -- escaped quote
			$aRules[6][] = array( $this->aOptions['escaped_quote'], 0, $this->aOptions['quote'] );
			// special mode, quoted field -- delimiter + quoted field
			$aRules[6][] = array(
				$this->aOptions['quote']
					. $this->aOptions['delimiter']
					. $this->aOptions['quote']
					,
				3
			);
			// special mode, quoted field -- delimiter + unquoted field
			$aRules[6][] = array( $this->aOptions['quote'] . $this->aOptions['delimiter'], 1 );
			// special mode, quoted field -- newline + quoted field
			$aRules[6][] = array(
				$this->aOptions['quote'] . $this->aOptions['newline'] . $this->aOptions['quote'],
				4
			);
			if ($this->aOptions['newline'] == self::LF)
				$aRules[6][] = array(
					$this->aOptions['quote'] . self::CRLF . $this->aOptions['quote'],
					4
				);
			// special mode, quoted field -- newline + unquoted field
			$aRules[6][] = array( $this->aOptions['quote'] . $this->aOptions['newline'], 2 );
			if ($this->aOptions['newline'] == self::LF)
				$aRules[6][] = array( $this->aOptions['quote'] . self::CRLF, 2 );
		}
		// unquoted field -- delimiter
		$aRules[1][] = array( $this->aOptions['delimiter'], 1 );
		if ($this->aOptions['newline'] == self::LF)
			$aRules[1][] = array( self::CRLF, 2 );
		// unquoted field -- newline
		$aRules[1][] = array( $this->aOptions['newline'], 2 );
		// special mode, unquoted field -- newline
		$aRules[5][] = array( $this->aOptions['newline'], 2 );

		// Finalize parsing rules
		$this->iReadMaxTokenLength = 0;
		$this->aReadParseTree = array();
		$this->aReadParseFind = array();
		foreach ($aRules as $iState => $aSet) {
			$this->aReadParseTree[$iState] = array();
			$this->aReadParseFind[$iState] = '';
			foreach ($aSet as $aRule) {
				$iLength = strlen($aRule[0]);
				if ($iLength > $this->iReadMaxTokenLength)
					$this->iReadMaxTokenLength = $iLength;
				$sChar = $aRule[0][0];
				if (strpos($this->aReadParseFind[$iState], $sChar) === false)
					$this->aReadParseFind[$iState] .= $sChar;
				if (!isset($this->aReadParseTree[$iState][$sChar]))
					$this->aReadParseTree[$iState][$sChar] = array();
				$this->aReadParseTree[$iState][$sChar][] = array(
					$iLength,
					$aRule[0],
					$aRule[1],
					isset($aRule[2]) ? $aRule[2] : null
				);
			}
		}
	}

	// Configure object as a writer
	protected function initWriter($sFilePath)
	{
		$this->bWriter = true;

		// Set handle-header flag
		$this->iRecordIndex =
			($this->aOptions['header'] || $this->aOptions['header'] === null)
			? -1
			: 0
		;

		// Add escapes
		switch ($this->aOptions['quote_mode']) {
			case 'escape':
			case 'escape_and_quote':
				if (!in_array($this->aOptions['newline'], $this->aEscapesFind)) {
					$this->aEscapesFind[] = $this->aOptions['newline'];
					$this->aEscapesReplace[] = $this->aOptions['escaped_newline'];
				}
				if (!in_array($this->aOptions['delimiter'], $this->aEscapesFind)) {
					$this->aEscapesFind[] = $this->aOptions['delimiter'];
					$this->aEscapesReplace[] = $this->aOptions['escaped_delimiter'];
				}
				break;
		}

		// Open file
		if ($sFilePath) {
			// Check whether file exists
			$bExists = false;
			if (file_exists($sFilePath)) {
				$this->sPath = realpath($sFilePath);
				if (!is_file($this->sPath))
					throw new Exception('Path is not a file');
				if (!is_writable($this->sPath))
					throw new Exception('Path is not writable');

				// We only append if file actually has content
				if (filesize($sFilePath))
					$bExists = true;
			}
			else {
				$this->sPath = $sFilePath;
			}

			// Determine default file settings
			$this->iFileType =
				(
					$this->aOptions['gzip']
					|| substr($sFilePath, -3) == '.gz'
					|| substr($sFilePath, -5) == '.gzip'
				)
				? 2
				: 1
			;
			$sMode = 'wb';

			// Check for append to existing file
			if ($this->aOptions['append'] && $bExists) {
				// Open for appending and do not write header
				$this->iRecordIndex = 0;
				$sMode = 'ab';

				// Check whether file is actually gzip
				if ($this->iFileType == 1) {
					$rHandle = fopen($this->sPath, 'rb');
					if ($rHandle && fread($rHandle, 2) == "\x1F\x8B") {
						fclose($rHandle);

						// Set gzip type
						$this->iFileType = 2;
					}
				}
			}

			// Open file for writing
			if ($this->iFileType == 1) {
				$this->mHandle = fopen($this->sPath, $sMode);
				if (!$this->mHandle)
					throw new Exception('Unable to open file');
			}
			else {
				$this->mHandle = gzopen($this->sPath, $sMode);
				if (!$this->mHandle)
					throw new Exception('Unable to open gzip archive');
			}
		}

		// Set up string buffer
		else {
			$this->iFileType = 0;
			$this->mHandle = '';
		}
	}
}

if (!class_exists('Useful\\Exception', false)) {
	class Exception extends \Exception {};
}
