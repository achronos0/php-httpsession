<?php
/**
* Work with date/time values
*
* @link https://github.com/achronos0/useful
* @copyright Ky Patterson 2016, licensed under Apache 2.0
*/

namespace Useful;

class Date
{
	//////////////////////////////
	// Public static

	/**
	* Ensure given date/time value is a Date object
	*
	* If value is already a Date object, the same object is returned.
	* Otherwise a new Date object is returned.
	*
	* Use this method if the value may already be a Date object, and don't need to create
	* a separate object if it is.
	*
	* @param mixed $mDate date/time value in any acceptable format
	* @return Date date/time object
	* @throws \Exception
	*/
	public static function obj($mDate)
	{
		return ($mDate instanceof self) ? $mDate : new self($mDate);
	}

	/**
	* Create a date/time object from date parts
	*
	* @param int $iYear year part
	* @param int $iMonth month part (1 to 12)
	* @param int $iDay day of month part (1 to 31)
	* @param int $iHour hour part (0 to 23)
	* @param int $iMinute minute part (0 to 60)
	* @param int $iSecond second part (0 to 60)
	* @param mixed $mTimezone timezone:
	*   null
	*     use PHP default timezone
	*   string
	*     timezone name
	*   \DateTimeZone
	*     object of PHP builtin class DateTimeZone
	* @return Date date/time object
	* @throws \Exception
	*/
	public static function createFromParts(
		$iYear, $iMonth = 1, $iDay = 1, $iHour = 0, $iMinute = 0, $iSecond = 0, $mTimezone = null
	)
	{
		$mDate = new self(null);
		if ($mTimezone)
			$mDate->changeTimezone($mTimezone);
		$mDate->setDatePart($iYear, $iMonth, $iDay);
		$mDate->setTimePart($iHour, $iMinute, $iSecond);
		return $mDate;
	}

	/**
	* Return a formatted date/time string for a given value
	*
	* Format a date/time value as a string, using provided date format specifier.
	*
	* @param mixed $mDate date/time value in any acceptable format
	* @param string $sFormat date/time format
	*   can be a date format specifier string per php date() function;
	*   or can be a format mnemonic name
	* @return string formatted date/time value
	* @throws \Exception
	*/
	public static function valueFormat($mDate, $sFormat)
	{
		return self::obj($mDate)->format($sFormat);
	}

	/**
	* Compare two date values
	*
	* @param mixed $mDateOne a date/time value in any acceptable format
	* @param mixed $mDateTwo a second date/time value in any acceptable format
	* @return
	*   (int) 1 if date1 is later than date2
	*   (int) -1 if date1 is earlier than date2
	*   (int) 0 if date1 and date2 represent the same time
	*   false if either date is invalid
	* @throws \Exception
	*/
	public static function valueCompare($mDateOne, $mDateTwo)
	{
		return self::obj($mDateOne)->compare($mDateTwo);
	}

	/**
	* Check whether date value falls within a date range
	*
	* @param mixed $mCheckDate a date/time value in any acceptable format
	* @param mixed $mStartDate range start date/time value in any acceptable format
	* @param mixed $mEndDate range end date/time value in any acceptable format
	* @param bool $bInclusive control behaviour if check date is exactly at the start or end value:
	*   true return true if check date is exactly at start or end date
	*   false return false if check date is exactly at start or end date
	* @return bool
	*   true if check date falls within date range
	*   0 if check date falls outside of date range
	*   false if any date is invalid
	* @throws \Exception
	*/
	public static function valueBetween($mCheckDate, $mStartDate, $mEndDate, $bInclusive = true)
	{
		return self::obj($mCheckDate)->between($mStartDate, $mEndDate, $bInclusive);
	}

	/**
	* Iterate a range of times between two dates
	*
	* Return an array of dates representing steps between the start date/time and end date/time,
	* changing each step date by the provided time interval.
	*
	* @param mixed $mStartDate starting date/time value in any acceptable format
	* @param mixed $mEndDate ending date/time value in any acceptable format
	* @param string $sStepInterval date time interval to adjust date by on each step
	* @return
	*   (array) set of Date objects representing steps between start and end dates
	*   false if either date is invalid
	* @throws \Exception
	*/
	public static function valueRange($mStartDate, $mEndDate, $sStepInterval = '+1 day')
	{
		return self::obj($mStartDate)->range($mEndDate, $sStepInterval);
	}

	/**
	* Check whether given calendar year is a leap year
	*
	* @param int $iYear year to check (default is current year)
	* @return bool true if year is a leap year, false if not
	*/
	public static function intIsLeapYear($iYear = null)
	{
		$oDate = new self(($iYear === null) ? 'now' : sprintf('%04d-01-01', $iYear));
		return $oDate->isLeapYear();
	}

	/**
	* Return number of days in given calendar year
	*
	* @param int $iYear year to check (default is current year)
	* @return int number of days in year
	*/
	public static function intDaysInYear($iYear = null)
	{
		$oDate = new self(($iYear === null) ? 'now' : sprintf('%04d-01-01', $iYear));
		return $oDate->daysInYear();
	}

	/**
	* Return number of days in given calendar month
	*
	* @param int $iMonth month to check (default is current month)
	* @param int $iYear year to check (default is current year)
	* @return int number of days in month
	*/
	public static function intDaysInMonth($iMonth = null, $iYear = null)
	{
		$oNow = new self('now');
		$oDate = new self(sprintf(
			'%04d-%02d-01',
			$iYear ?: $oNow->year(),
			$iMonth ?: $oNow->month()
		));
		return $oDate->daysInMonth();
	}

	/**
	* Set a mnemonic format label
	*
	* @param string $sLabel mnemonic label: 'date', 'display_date', etc.
	* @param string $sFormat date format specifier, per php date() function
	* @return bool true
	*/
	public static function registerFormatLabel($sLabel, $sFormat)
	{
		self::$aFormatLabels[$sLabel] = $sFormat;
		return true;
	}

	/**
	* Return all defined mnemonic format labels
	*
	* @return array mnemonic labels and corresponding datre format specifiers
	*/
	public static function getRegisteredFormatLabels()
	{
		return self::$aFormatLabels;
	}


	//////////////////////////////
	// Public - main

	/**
	* Create a date/time object
	*
	* @param mixed $mDate date/time value
	*   string
	*     formatted date, capable of being parsed by php strtotime()
	*   int or float
	*     unix timestamp
	*   Useful\Date
	*     copy date value from another Date object
	*   \DateTime
	*     copy date value from an object of php builtin class DateTime
	*   null
	*     current system date/time
	* @param string $sFormat date format specifier
	*   force string date value to be parsed using this format specifier, per php date() function
	* @param mixed $mTimezone timezone
	*   null
	*     use php default timezone
	*   string
	*     timezone name
	*   \DateTimeZone
	*     object of php builtin class DateTimeZone
	* @throws \Exception
	*/
	public function __construct($mDate = null, $sFormat = null, $mTimezone = null)
	{
		$this->oTime = $mDate ? $this->_time($mDate, $sFormat) : new \DateTime();
		if ($mTimezone)
			$this->changeTimezone($mTimezone);
	}


	//////////////////////////////
	// Public - compare/interval

	/**
	* Return a new date/time relative to this object's date/time
	*
	* Create a date/time object, offset from this object's date/time by a time interval
	*
	* @param string $sInterval time interval in format accepted by strtotime
	* @return Date new date/time instance
	* @throws \Exception
	*/
	public function relative($sInterval)
	{
		$oDate = new Date($this);
		$oDate->adjust($sInterval);
		return $oDate;
	}

	/**
	* Return a new date/time aligned to a period boundary
	*
	* Create a date/time object, based on this object's date/time aligned to match a period
	* boundary.
	*
	* @param string $sUnit unit type of time period:
	*   second
	*     Every N seconds part the minute (1 to 30)
	*   minute
	*     Every N minutes past the hour (1 to 30)
	*   hour
	*     Every N hours past midnight (1 to 12)
	*   day
	*     Every N days in the calendar month (1 to 16)
	*   week
	*     Every N weeks in the calendar year (1 to 26)
	*   month
	*     Every N months in the calendar year (1 to 6)
	*   year
	*     Every N years (1+)
	* @param int $iLength length of time period in units
	* @param string $sWhich control which boundary date to calculate:
	*   earlier
	*     align to nearest boundary that is later than (or equal to) the origin date
	*   later
	*     align to nearest boundary that is earlier than (or equal to) the origin date
	*   round
	*     align to nearest boundary (earlier or later, whichever is closer)
	* @return Date new date/time instance
	* @throws \Exception
	*/
	public function align($sUnit, $iLength = 1, $sWhich = 'earlier')
	{
		$oDate = new Date($this);
		$oDate->adjustAlign($sUnit, $iLength, $sWhich);
		return $oDate;
	}

	/**
	* Return a new date/time instance with same value but a different time zone.
	*
	* Create a date/time object that represents the same moment in time as this object, but in a
	* different time zone. (See {@link changeTimezone}.)
	*
	* @param mixed $mTimezone timezone
	*   string
	*     timezone name
	*   \DateTimeZone
	*     object of php builtin class DateTimeZone
	* @return Date new date/time instance
	* @throws \Exception
	*/
	public function relocate($mTimezone)
	{
		$oDate = new Date($this);
		$oDate->changeTimezone($mTimezone);
		return $oDate;
	}

	/**
	* Compare this date to another date
	*
	* Compare this date/time to another date value and determine which one is later.
	*
	* @param mixed $mDate comparison date/time value in any acceptable format
	* @return int
	*   (int) 1 if this date is later than argument date
	*   (int) -1 if this date is earlier than argument date
	*   (int) 0 if this date and argument date represent the same time
	*   false if either date is invalid
	* @throws \Exception
	*/
	public function compare($mDate)
	{
		// Get argument date
		$oCompareDate = self::obj($mDate);

		// Adjust for timezone differences if necessary
		$iOffset = $this->timezone('offset') - $oCompareDate->timezone('offset');
		if ($iOffset > 0)
			$oCompareDate = $oCompareDate->relative('+' . $iOffset . ' seconds');
		elseif ($iOffset < 0)
			$oCompareDate = $oCompareDate->relative($iOffset . ' seconds');

		// Compare parts
		foreach (array( 'Y', 'm', 'd', 'G', 'i', 's' ) as $sFormat) {
			$iTest1 = $this->_formatInt($sFormat);
			$iTest2 = $oCompareDate->_formatInt($sFormat);
			if ($iTest1 < $iTest2)
				return -1;
			elseif ($iTest1 > $iTest2)
				return 1;
		}
		return 0;
	}

	/**
	* Check whether this date is equal to/earlier than another date
	*
	* @param mixed $mDate comparison date/time value in any acceptable format
	* @param bool $bInclusive what to return if this date is exactly equal to argument date:
	*   true
	*     "on or before"
	*     return true if this date is exactly at argument date
	*   false
	*     "before only"
	*     return false if this date is exactly at argument date
	* @return bool
	*   true if this date is on or before argument date
	*   false if this date is after argument date
	* @throws \Exception
	*/
	public function before($mDate, $bInclusive = true)
	{
		$iCompare = $this->compare($mDate);
		return
			($iCompare === false)
			? false
			: ($iCompare === -1 || ($iCompare === 0 && $bInclusive))
		;
	}

	/**
	* Check whether this date is equal to/later than another date
	*
	* @param mixed $mDate comparison date/time value in any acceptable format
	* @param bool $bInclusive what to return if this date is exactly equal to argument date:
	*   true
	*     "on or after"
	*     return true if this date is exactly equal to argument date
	*   false
	*     "after only"
	*     return false if this date is exactly equal to argument date
	* @return bool
	*   true if this date is on or after argument date
	*   false if this date is before argument date
	* @throws \Exception
	*/
	public function after($mDate, $bInclusive = true)
	{
		$iCompare = $this->compare($mDate);
		return
			($iCompare === false)
			? false
			: ($iCompare === 1 || ($iCompare === 0 && $bInclusive))
		;
	}

	/**
	* Check whether this date falls within a given date range
	*
	* @param mixed $mStartDate range start date/time value in any acceptable format
	* @param mixed $mEndDate range end date/time value in any acceptable format
	* @param bool $bInclusive what to return if this is exactly equal to start or end date:
	*   true
	*     return true if this date is exactly equal to start or end date
	*   false
	*     return false if this date is exactly equal to start or end date
	* @return bool
	*   true if this date falls within date range
	*   false if this date falls outside of date range
	* @throws \Exception
	*/
	public function between($mStartDate, $mEndDate, $bInclusive = true)
	{
		// Compare to start date
		$iStartResult = $this->compare($mStartDate);

		// Compare to end date
		$iEndResult = $this->compare($mEndDate);

		// Return comparison result
		return (
			($iStartResult == 1 || ($iStartResult == 0 && $bInclusive))
			&& ($iEndResult == -1 || ($iEndResult == 0 && $bInclusive))
		);
	}

	/**
	* Return the time interval between another date and this date
	*
	* Calculate difference (interval) between two date/time values.
	*
	* If this date is later than argument date, interval is positive.
	* If this date is earlier than argument date, interval is negative.
	*
	* If this date is exactly equal to argument date, interval is empty.
	*
	* Interval value can be returned in several different ways ($sReturnMode argument):
	*   relative
	*     Returns an relative date string, as accepted by new Date() and PHP builtin
	*      strtotime().
	*     This is the default return mode.
	*     Empty interval (equal dates) is returned as empty string.
	*     Examples:
	*       +2 months +1 day +3 hours
	*       -15 minutes -30 seconds
	*   human
	*     Returns a human-readable relative date string.
	*     Empty interval (equal dates) is returned as "None".
	*     Examples:
	*       2 months, 1 day, 3 hours
	*       -15 minutes, 30 seconds
	*   interval
	*     Returns an interval string as accepted by PHP builtin DateInterval.
	*     Empty interval (equal dates) is returned as empty string.
	*     Interval is always expressed as positive, call {@link before} to check for negative
	*      interval.
	*     Examples:
	*       P2M1DT3H
	*       PT15M30S
	*   parts
	*     Return an array of dateparts that comprise the interval.
	*     Empty interval (equal dates) is returned as empty array.
	*     Part values are always positive, use array key 'compare' to check for negative interval.
	*     Part values of zero are not included in the array
	*     Examples:
	*       array( 'months' => 2, 'days' => 1, 'hours' => 3, 'compare' => 1 )
	*       array( 'minutes' => 15, 'seconds' => 30, 'compare' => -1 )
	*   years
	*   months
	*   weeks
	*   days
	*   hours
	*   minutes
	*   seconds
	*     Calculate difference measured in given datepart, counting whole units only.
	*     Total is always positive, call {@link before} to check for negative interval.
	*   totals
	*     Return array of datepart totals for all dateparts.
	*     Total values are always positive, use array key 'compare' to check for negative interval
	*     Example:
	*       array(
	*         'years' => 0,
	*         'months' => 2,
	*         'days' => 62,
	*         'hours' => 1491,
	*         'minutes' => 89460,
	*         'seconds' => 5367600,
	*         'compare' => 1
	*       )
	*   all
	*     Return all of the above in an array:
	*     Example:
	*       array(
	*         'compare' => 1,
	*         'relative' => '+2 months +1 day +3 hours',
	*         'human' => '2 months, 1 day, 3 hours',
	*         'interval' => 'P2M1DT3H',
	*         'parts' => array( 'months' => 2, 'days' => 1, 'hours' => 3 ),
	*         'totals' => array(
	*           'years' => 0,
	*           'months' => 2,
	*           'days' => 62,
	*           'hours' => 1491,
	*           'minutes' => 89460,
	*           'seconds' => 5367600,
	*           'compare' => 1
	*         )
	*       )
	*     Return array containing parts, totals, relative and interval formats
	*     Use array key 'compare' to check for negative interval
	*
	* Note on parts vs totals:
	*   * Each element in parts represents a fraction of the interval.
	*     All of the parts "added together" equal the interval.
	*   * Each element in totals represents the entire interval.
	*     The totals are the number of date-part boundaries (of that part) crosed during the
	*      complete interval.
	*   * Example, for 2011-02-02 vs 2011-01-01:
	*     * Parts: months=1, days=1
	*     * Totals: months=1, days=32, hours=768, minutes=23040, seconds=1382400
	*
	* @param mixed $mDate comparison date/time value in any acceptable format
	* @param string $sReturnMode type of difference to return. Types are:
	*   relative
	*   human
	*   interval
	*   parts
	*   years (or y)
	*   months (or m)
	*   days (or d)
	*   hours (or h)
	*   minutes (or i)
	*   seconds (or s)
	*   totals
	*   all
	* @return mixed interval in format specified by $sReturnMode
	*   note return may be empty string or empty array if dates are equal
	* @throws \Exception
	*/
	public function difference($mDate, $sReturnMode = 'relative')
	{
		static $aPartData = array(
			'years' => 'Y', 'months' => 'm', 'days' => 'd',
			'hours' => 'G', 'minutes' => 'i', 'seconds' => 's'
		);
		static $aIntervalData = array(
			array( 'years' => 'Y', 'months' => 'M', 'days' => 'D' ),
			array( 'hours' => 'H', 'minutes' => 'M', 'seconds' => 'S' )
		);

		// Get argument date
		$oCompareDate = self::obj($mDate);
		$oCompareDate->changeTimezone($this->timezone());

		// Prep return mode and calculation flags
		$bCalcTotals = false;
		$bCalcRelative = false;
		$bCalcHuman = false;
		$bCalcInterval = false;
		switch (strtolower(substr($sReturnMode, 0, 2))) {
			case 're':
				$bCalcRelative = true;
				$sReturnMode = 'relative';
				break;
			case 'hu':
				$bCalcHuman = true;
				$sReturnMode = 'human';
				break;
			case 'in':
				$bCalcInterval = true;
				$sReturnMode = 'interval';
				break;
			case 'pa':
				$sReturnMode = 'parts';
				break;
			case 'y':
			case 'ye':
				$bCalcTotals = true;
				$sReturnMode = 'years';
				break;
			case 'm':
			case 'mo':
				$bCalcTotals = true;
				$sReturnMode = 'months';
				break;
			case 'd':
			case 'da':
				$bCalcTotals = true;
				$sReturnMode = 'days';
				break;
			case 'h':
			case 'ho':
				$bCalcTotals = true;
				$sReturnMode = 'hours';
				break;
			case 'i':
			case 'mi':
				$bCalcTotals = true;
				$sReturnMode = 'minutes';
				break;
			case 's':
			case 'se':
				$bCalcTotals = true;
				$sReturnMode = 'seconds';
				break;
			case 'to':
				$bCalcTotals = true;
				$sReturnMode = 'totals';
				break;
			case 'al':
				$bCalcRelative = true;
				$bCalcHuman = true;
				$bCalcInterval = true;
				$bCalcTotals = true;
				$sReturnMode = 'all';
				break;
		}

		// Calculate parts
		$iCompare = 0;
		$aParts = array(
			'years' => 0,
			'months' => 0,
			'days' => 0,
			'hours' => 0,
			'minutes' => 0,
			'seconds' => 0
		);
		foreach ($aPartData as $sKey => $sFormat) {
			$iTest1 = $this->_formatInt($sFormat);
			$iTest2 = $oCompareDate->_formatInt($sFormat);
			if (!$iCompare) {
				if ($iTest1 > $iTest2)
					$iCompare = 1;
				elseif ($iTest1 < $iTest2)
					$iCompare = -1;
				else
					continue;
			}
			if ($iCompare == 1)
				$aParts[$sKey] = $iTest1 - $iTest2;
			else
				$aParts[$sKey] = $iTest2 - $iTest1;
		}
		$oLaterDate = ($iCompare == 1) ? $this : $oCompareDate;
		$oEarlierDate = ($iCompare == -1) ? $this : $oCompareDate;

		// Normalize parts
		if ($aParts['seconds'] < 0) {
			$aParts['minutes']--;
			$aParts['seconds'] = 60 + $aParts['seconds'];
		}
		if ($aParts['minutes'] < 0) {
			$aParts['hours']--;
			$aParts['minutes'] = 60 + $aParts['minutes'];
		}
		if ($aParts['hours'] < 0) {
			$aParts['days']--;
			$aParts['hours'] = 24 + $aParts['hours'];
		}
		if ($aParts['days'] < 0) {
			$aParts['months']--;
			$aParts['days'] = $oLaterDate->relative('-1 month')->daysInMonth() + $aParts['days'];
			if ($aParts['days'] < 0) {
				$aParts['months']--;
				$aParts['days'] =
					$oLaterDate->relative('-2 month')->daysInMonth()
					+ $aParts['days']
				;
			}
			/*
				Example of "wrong" 2 month difference: Mar 1 vs Jan 31
				This is actually less than 1 month difference:
					Mar 1 vs Jan 31
					== +2 months (3 minus 1), -30 days (1 minus 31)
					== +1 month, -2 days (-30 plus 28 days in Feb)
					== +0 months, 29 days (-2 plus 31 days in Jan)
				or:
					Mar 1 vs Jan 31
					== Jan 60 (31 + 28 + 1) vs Jan 31
					== 29 days (60 minus 31)
			*/
		}
		if ($aParts['months'] < 0) {
			$aParts['years']--;
			$aParts['months'] = 12 + $aParts['months'];
		}

		// Calculate totals
		if ($bCalcTotals) {
			$aTotals = $aParts;

			// Return years now if that's all we need
			if ($sReturnMode == 'years')
				return $aTotals['years'];

			// Calculate months
			if ($aTotals['years'])
				$aTotals['months'] += 12 * $aTotals['years'];

			// Return months now if that's all we need
			if ($sReturnMode == 'months')
				return $aTotals['months'];

			// Calculate days (this is expensive)
			$aTotals['days'] = $oLaterDate->dayOfYear() - $oEarlierDate->dayOfYear();
			if ($aTotals['days']) {
				/*
					If not same day, and later date is earlier time of day, then days count is
					off by one
				*/
				foreach (
					array( 'hours' => 'G', 'minutes' => 'i', 'seconds' => 's' )
					as $sKey => $sFormat
				) {
					$iTest1 = $oLaterDate->_formatInt($sFormat);
					$iTest2 = $oEarlierDate->_formatInt($sFormat);
					if ($iTest1 < $iTest2) {
						$aTotals['days']--;
						break;
					}
					elseif ($iTest1 > $iTest2)
						break;
					else
						continue;
				}
			}
			for ($i = $oEarlierDate->year(); $i < $oLaterDate->year(); $i++)
				$aTotals['days'] += intval(self::intDaysInYear($i));

			// Calculate hours
			if ($aTotals['days'])
				$aTotals['hours'] += 24 * $aTotals['days'];

			// Calculate minutes
			if ($aTotals['hours'])
				$aTotals['minutes'] += 60 * $aTotals['hours'];

			// Calculate seconds
			if ($aTotals['minutes'])
				$aTotals['seconds'] += 60 * $aTotals['minutes'];
			$aResult['totals'] = $aTotals;
		}

		// Calculate relative
		if ($bCalcRelative) {
			if ($iCompare) {
				$sSign = ($iCompare == 1 ? '-' : '+');
				$aTemp = array();
				foreach ($aPartData as $sKey => $sFormat) {
					if ($aParts[$sKey] > 1)
						$aTemp[] = $sSign . $aParts[$sKey] . ' ' . $sKey;
					elseif ($aParts[$sKey] == 1)
						$aTemp[] = $sSign . $aParts[$sKey] . ' ' . substr($sKey, 0, -1);
				}
				$sRelative = implode(' ', $aTemp);
			}
			else
				$sRelative = '';
		}

		// Calculate human
		if ($bCalcHuman) {
			if ($iCompare) {
				$aTemp = array();
				foreach ($aPartData as $sKey => $sFormat) {
					if ($aParts[$sKey] > 1)
						$aTemp[] = $aParts[$sKey] . ' ' . $sKey;
					elseif ($aParts[$sKey] == 1)
						$aTemp[] = $aParts[$sKey] . ' ' . substr($sKey, 0, -1);
				}
				$sHuman = ($iCompare == 1 ? '' : '-') . implode(', ', $aTemp);
			}
			else
				$sHuman = 'None';
		}

		// Calculate interval
		if ($bCalcInterval) {
			if ($iCompare) {
				$aTemp = array();
				foreach ($aIntervalData[0] as $sKey => $sFormat) {
					if ($aParts[$sKey])
						$aTemp[] = $aParts[$sKey] . $sFormat;
				}
				$sInterval = 'P' . implode('', $aTemp);
				$aTemp = array();
				foreach ($aIntervalData[1] as $sKey => $sFormat) {
					if ($aParts[$sKey])
						$aTemp[] = $aParts[$sKey] . $sFormat;
				}
				if (count($aTemp))
					$sInterval .= 'T' . implode('', $aTemp);
			}
			else
				$sInterval = '';
		}

		// Return result
		switch ($sReturnMode) {
			case 'relative':
				return $sRelative;
			case 'human':
				return $sHuman;
			case 'interval':
				return $sInterval;
			case 'parts':
				$aParts['compare'] = $iCompare;
				return array_filter($aParts);
			case 'totals':
				$aTotals['compare'] = $iCompare;
				return $aTotals;
			case 'all':
				return array(
					'compare' => $iCompare,
					'relative' => $sRelative,
					'human' => $sHuman,
					'interval' => $sInterval,
					'parts' => array_filter($aParts),
					'totals' => $aTotals
				);
		}
		return $aTotals[$sReturnMode];
	}

	/**
	* Iterate a range of times between this date and another date
	*
	* Return an array of dates representing steps between this date/time and an end date/time,
	*  changing date value by the provided time interval at each step.
	*
	* Example:
	*   $oStartDate = new Date('2012-01-01 23:00');
	*   foreach ($oStartDate->range('2012-01-05 01:00', '+1 day') as $oDate)
	*     echo $oDate->date()->format('Y-m-d') . "\n";
	* Result:
	*   2012-01-01
	*   2012-01-02
	*   2012-01-03
	*   2012-01-04
	* Doesn't print Jan 5 because the time is later than end date
	*
	* @param mixed $mEndDate ending date/time value in any acceptable format
	* @param string $sStepInterval date time interval to adjust date by on each step
	* @return
	*   (array) set of Date objects representing steps between start and end dates
	*   false if either date is invalid
	* @throws \Exception
	*/
	public function range($mEndDate, $sStepInterval = '+1 day')
	{
		// Get argument date
		$oEndDate = self::obj($mEndDate);

		// Generate step dates
		$aSteps = array();
		$oCurrentDate = new Date($this);
		if (strpos($sStepInterval, 0, 1) == '-') {
			while ($oCurrentDate->compare($oEndDate) >= 0) {
				$aSteps[] = new Date($oCurrentDate);
				$oCurrentDate->adjust($sStepInterval);
			}
		}
		else {
			while ($oCurrentDate->compare($oEndDate) <= 0) {
				$aSteps[] = new Date($oCurrentDate);
				$oCurrentDate->adjust($sStepInterval);
			}
		}
		return $aSteps;
	}


	//////////////////////////////
	// Public - get value

	/**
	* Return formatted date and time parts
	*
	* @return string formatted value
	*/
	public function datetime()
	{
		return $this->_format(self::$aFormatLabels['datetime']);
	}

	/**
	* Return formatted date part
	*
	* @return string formatted date part
	*/
	public function date()
	{
		return $this->_format(self::$aFormatLabels['date']);
	}

	/**
	* Return formatted time part
	*
	* @return string formatted time part
	*/
	public function time()
	{
		return $this->_format(self::$aFormatLabels['time']);
	}

	/**
	* Return formatted date/time string
	*
	* Format date/time as a string, using provided date format specifier.
	*
	* @param string $sFormat date/time format
	*   can be a date format specifier string per php date() function;
	*   or can be a format mnemonic name
	* @return string formatted date/time value
	*/
	public function format($sFormat)
	{
		return $this->_format(
			array_key_exists($sFormat, self::$aFormatLabels)
			? self::$aFormatLabels[$sFormat]
			: $sFormat
		);
	}

	/**
	* Return Unix timestamp
	*
	* Get Unix timestamp.
	* This can return a value for dates outside the typical Unix timestamp range.
	*
	* Unix timestamps are stored as signed integers. Possible values are limited by system integer
	*  width.
	* For 64-bit systems there is effectively no limit (293 billion years)
	* For 32-bit systems, an integer (i.e. a Unix timestamp) can only represent dates in the range
	* 	1901-12-13 20:45:54
	* 	to
	* 	2038-01-19 03:14:07
	*
	* On 64-bit systems this method will always return an integer.
	* On 32-bit systems, for dates outside the 32-bit range this method will return a float
	*  instead of int.
	* Note that any external application or process that requests a Unix timestamp will not work
	*  with this float.
	*
	* @return int|float
	*   int
	*     unix timestamp, if timestamp can be represented by this computer's integer
	*   float
	*     unix timestamp equivalent, if timestamp falls outside of valid 32-bit range, on 32-bit
	*      system
	*/
	public function unix()
	{
		$sTime = $this->oTime->format('U');
		$iTime = intval($sTime);
		return
			(($iTime === PHP_INT_MAX || $iTime === self::$iMinIntValue) && PHP_INT_SIZE == 4)
			? floatval($sTime)
			: $iTime
		;
	}

	/**
	* Return array of date parts
	*
	* @return array parts: array(
	*   year => (int)
	*   month => (int) 1 to 12
	*   month_name => (string) "January" to "December"
	*   month_short => (string) "Jan" to "Dec"
	*   day => (int) 1 to 31
	*   day_ordinal => (string) "1st", "2nd", "3rd", etc.
	*   week => (int) week number of year, per ISO-8601, 1 to 53
	*   weekday => (int) 1 (Monday) to 7 (Sunday)
	*   weekday_name => (string) "Monday" to "Sunday"
	*   weekday_short => (string) "Mon" to "Sun"
	*   //
	*   hour => (int) 0 to 23
	*   minute => (int) 0 to 60
	*   second => (int) 0 to 60
	*   //
	*   tz_offset => (int) timezone offset to UTC in seconds, -43200 to 43200
	*   tz_diff => (string) timezone offset to UTC in form "+HHMM"
	*   tz_code => (string) timezone abbreviation, e.g. "PST"
	*   tz_name => (string) timezone full name, e.g. "America/Los Angeles"
	* ) or false if this date is invalid
	*/
	public function parts()
	{
		$sVal = $this->_format('e T O Z a s i H D l N W jS d M F m Y');
		$aParts = explode(' ', $sVal);
		return array(
				'year' => intval(array_pop($aParts)),
				'month' => intval(array_pop($aParts)),
				'month_name' => array_pop($aParts),
				'month_short' => array_pop($aParts),
				'day' => intval(array_pop($aParts)),
				'day_ordinal' => array_pop($aParts),
				'week' => intval(array_pop($aParts)),
				'weekday' => intval(array_pop($aParts)),
				'weekday_name' => array_pop($aParts),
				'weekday_short' => array_pop($aParts),
				'hour' => intval(array_pop($aParts)),
				'minute' => intval(array_pop($aParts)),
				'second' => intval(array_pop($aParts)),
				'tz_offset' => intval(array_pop($aParts)),
				'tz_diff' => array_pop($aParts),
				'tz_code' => array_pop($aParts),
				'tz_name' => array_pop($aParts)
			);
	}

	/**
	* Return year
	*
	* @return int part
	*/
	public function year()
	{
		return $this->_formatInt('Y');
	}

	/**
	* Return month as number, 1 to 12
	*
	* @return int part
	*/
	public function month()
	{
		return $this->_formatInt('m');
	}

	/**
	* Return month as name, "January" to "December"
	*
	* @return string part
	*/
	public function monthName()
	{
		return $this->_format('F');
	}

	/**
	* Return month as abbreviation, "Jan" to "Dec"
	*
	* @return string part
	*/
	public function monthShort()
	{
		return $this->_format('M');
	}

	/**
	* Return week number within year, 1 to 53
	*
	* @return int part
	*/
	public function week()
	{
		return $this->_formatInt('W');
	}

	/**
	* Return day of week as number, 1 (for Monday) to 7 (for Sunday)
	*
	* @return int part
	*/
	public function weekday()
	{
		return $this->_formatInt('N');
	}

	/**
	* Return day of week as name, "Monday" to "Sunday"
	*
	* @return string part
	*/
	public function weekdayName()
	{
		return $this->_format('l');
	}

	/**
	* Return day of week as abbreviation, "Mon" to "Sun"
	*
	* @return string part
	*/
	public function weekdayShort()
	{
		return $this->_format('D');
	}

	/**
	* Return day of month
	*
	* @return int part
	*/
	public function day()
	{
		return $this->_formatInt('d');
	}

	/**
	* Return day of month with ordinal ("st", "nd", "rd")
	*
	* @return string part
	*/
	public function dayOrdinal()
	{
		return $this->_format('jS');
	}

	/**
	* Return day of year (0 to 365)
	*
	* @return int part
	*/
	public function dayOfYear()
	{
		return $this->_formatInt('z');
	}

	/**
	* Return hour, 0 to 23
	*
	* @return int part
	*/
	public function hour()
	{
		return $this->_formatInt('H');
	}

	/**
	* Return hour within meridian of day, 1 to 12
	*
	* @return int part
	*/
	public function hour12()
	{
		return $this->_formatInt('h');
	}

	/**
	* Return minute
	*
	* @return int part
	*/
	public function minute()
	{
		return $this->_formatInt('i');
	}

	/**
	* Return second
	*
	* @return int part
	*/
	public function second()
	{
		return $this->_formatInt('s');
	}

	/**
	* Return ante-meridian or post-meridian descriptor: "am" or "pm"
	*
	* @return string part
	*/
	public function meridian()
	{
		return $this->_format('a');
	}

	/**
	* Return timezone
	*
	* @param string $sFormat how to format timezone:
	*   code
	*     Return shortform code of timezone, e.g. "PST" or "GMT"
	*   name
	*     Return longform name of timezone, e.g. "America/Los Angeles"
	*   diff
	*     Return difference to UTC in hours, format "+HH00" e.g. "-7000"
	*   offset
	*     Return difference to UTC in seconds, as int
	*   object
	*     Return instance of php builtin class DateTimeZone
	* @return mixed timezone value
	*/
	public function timezone($sFormat = 'name')
	{
		switch ($sFormat) {
			case 'code':
				return $this->_format('T');
			case 'name':
				return $this->_format('e');
			case 'offset':
				return $this->_formatInt('Z');
			case 'object':
				return $this->oTime->getTimezone();
		}
		// Return difference to UTC
		return $this->_format('O');
	}

	/**
	* Check whether this date is in a leap year
	*
	* @return bool true if year is a leap year, false if not
	*/
	public function isLeapYear()
	{
		return $this->_format('L') ? true : false;
	}

	/**
	* Return number of days in this date's year
	*
	* Get number of days in a year.
	* This can be called statically: pass an integer year as argument.
	*
	* @param int $iYear year number to check (default is to check this date)
	* @return int days in year
	*/
	public function daysInYear()
	{
		$oDate = new self(sprintf('%04d-12-31', $this->year());
		return $oDate->_formatInt('z') + 1;
	}

	/**
	* Return number of days in this date's month
	*
	* Get number of days in a month
	*
	* @return int number of days in month
	*/
	public function daysInMonth()
	{
		return $this->_formatInt('t');
	}


	//////////////////////////////
	// Public - set value

	/**
	* Set date/time value of this instance
	*
	* @param mixed $mDate date/time value in any acceptable format
	* @param string $sFormat force string date value to be parsed using this format specifier
	* @return bool true on success, false if date value is invalid
	* @throws \Exception
	*/
	public function set($mDate, $sFormat = null)
	{
		$this->oTime = $this->_time($mDate, $sFormat);
		return true;
	}

	/**
	* Change date part of date/time value
	*
	* Change date part of this instance's date/time value.
	*
	* @param int $iYear year part
	* @param int $iMonth month part (1 to 12)
	* @param int $iDay day of month part (1 to 31)
	* @return bool true on success, false if arguments are invalid
	* @throws \Exception
	*/
	public function setDatePart($iYear, $iMonth = 1, $iDay = 1)
	{
		if (!$this->oTime->setDate($iYear, $iMonth, $iDay))
			throw new \Exception('setDatePart: invalid date arguments given');
		return true;
	}

	/**
	* Change time part of date/time value
	*
	* Change time part of this instance's date/time value.
	*
	* @param int $iHour hour part (0 to 23)
	* @param int $iMinute minute part (0 to 60)
	* @param int $iSecond second part (0 to 60)
	* @return bool true on success, false if arguments are invalid
	* @throws \Exception
	*/
	public function setTimePart($iHour = 0, $iMinute = 0, $iSecond = 0)
	{
		if (!$this->oTime->setTime($iHour, $iMinute, $iSecond))
			throw new \Exception('setTimePart: invalid time arguments given');
		return true;
	}

	/**
	* Change timezone of date/time value
	*
	* Change timezone for this instance's date/time value.
	*
	* Note that this will adjust the listed date/time so as to continue to represent the same
	*  moment in time.
	* For example, given this original value:
	*   2001-01-01 01:00:00+0100
	* If time zone is changed from +0100 to +0200, the new final value will be:
	*   2001-01-01 02:00:00+0200
	*
	* @param mixed $mTimezone timezone
	*   string
	*     timezone name
	*   \DateTimeZone
	*     object of php builtin class DateTimeZone
	* @returns bool true on success, false if argument is invalid
	* @throws \Exception
	*/
	public function changeTimezone($mTimezone)
	{
		// Get DateTimeZone object
		if (is_object($mTimezone) && $mTimezone instanceof \DateTimeZone)
			$oTimeZone = $mTimezone;
		else {
			try {
				$oTimeZone = new \DateTimeZone($mTimezone);
			}
			catch (Exception $e) {
				throw new \Exception(
					'setTimezone: invalid timezone argument given (' . $e->getMessage() . ')',
					null,
					$e
				);
			}
		}

		// Change timezone
		if (!$this->oTime->setTimezone($oTimeZone))
			throw new \Exception('setTimezone: an unknown error occured while setting timezone');
		return true;
	}

	/**
	* Change this date value by a time interval
	*
	* @param string $sInterval time interval in format accepted by php strtotime()
	* @return bool true
	* @throws \Exception
	*/
	public function adjust($sInterval)
	{
		if (!$this->oTime->modify($sInterval))
			throw new \Exception('modify: invalid interval argument given');
		return true;
	}

	/**
	* Change this date value to align to a period boundary
	*
	* @param string $sUnit unit type of time period:
	*   second (or s)
	*     Every N seconds past the minute (1 to 30)
	*     (Note: if $iLength is 1 this does nothing)
	*   minute (or i)
	*     Every N minutes past the hour (1 to 30)
	*   hour (or h)
	*     Every N hours past midnight (1 to 12)
	*   day (or d)
	*     Every N days in the calendar month (1 to 16)
	*   week (or w)
	*     Every N weeks in the calendar year (1 to 26)
	*   month (or m)
	*     Every N months in the calendar year (1 to 6)
	*   year (or y)
	*     Every N years (1+)
	* @param int $iLength length of time period in units
	* @param string $sWhich control which boundary date to calculate:
	*   earlier
	*     align to nearest boundary that is later than (or equal to) the origin date
	*   later
	*     align to nearest boundary that is earlier than (or equal to) the origin date
	*   round
	*     align to nearest boundary (earlier or later, whichever is closer)
	* @return bool true
	* @throws \Exception
	*/
	public function adjustAlign($sUnit, $iLength = 1, $sWhich = 'earlier')
	{
		// Get boundary unit type
		$sUnit = strtolower($sUnit);
		if ($sUnit == 'minute' || $sUnit == 'min')
			$sUnit = 'i';
		$sCheckUnit = substr($sUnit, 0, 1);

		// Get origin boundary date-part value
		switch ($sCheckUnit) {
			case 's':
				$fOriginValue = $this->second();
				break;
			case 'i':
				$fOriginValue =
					$this->minute()
					+ ($this->second() / 60)
				;
				break;
			case 'h':
				$fOriginValue =
					$this->hour()
					+ ($this->minute() / 60)
					+ ($this->second() / 3600)
				;
				break;
			case 'd':
				$fOriginValue =
					$this->day()
					+ ($this->hour() / 24)
					+ ($this->minute() / 1440)
					+ ($this->second() / 86400)
				;
				break;
			case 'w':
				$fOriginValue =
					$this->week() - 1
					+ (($this->weekday() - 1) / 7)
					+ ($this->hour() / 168)
					+ ($this->minute() / 10080)
					+ ($this->second() / 604800)
				;
				break;
			case 'm':
				$iDivisor = $this->daysInMonth();
				$fOriginValue =
					$this->month() - 1
					+ ($this->day() / $iDivisor)
					+ ($this->hour() / (24 * $iDivisor))
					+ ($this->minute() / (1440 * $iDivisor))
					+ ($this->second() / (86400 * $iDivisor))
				;
				break;
			case 'y':
				$iDivisor = $this->daysInYear();
				$fOriginValue =
					$this->year()
					+ (($this->dayOfYear() - 1) / $iDivisor)
					+ ($this->hour() / (24 * $iDivisor))
					+ ($this->minute() / (1440 * $iDivisor))
					+ ($this->second() / (86400 * $iDivisor))
				;
				break;
			default:
				throw new \Exception('adjustAlign: invalid unit argument given');
		}

		// Determine new boundary date-part value
		switch (strtolower(substr($sWhich, 0, 1))) {
			case 'e':
				$iBoundaryValue = $iLength * floor($fOriginValue / $iLength);
				break;
			case 'l':
				$iBoundaryValue = $iLength * ceil($fOriginValue / $iLength);
				break;
			case 'r':
				$iBoundaryValue = $iLength * round($fOriginValue / $iLength);
				break;
		}

		// Alter date
		switch ($sCheckUnit) {
			case 's':
				$this->setTimePart($this->hour(), $this->minute(), $iBoundaryValue);
				break;
			case 'i':
				$this->setTimePart($this->hour(), $iBoundaryValue, 0);
				break;
			case 'h':
				$this->setTimePart($iBoundaryValue, 0, 0);
				break;
			case 'd':
				$this->setTimePart(0, 0, 0);
				$this->setDatePart($this->year(), $this->month(), $iBoundaryValue);
				break;
			case 'w':
				$this->setTimePart(0, 0, 0);
				// adjust to week boundary (Monday)
				$iDay = $this->weekday() - 1;
				if ($iDay)
					$this->adjust('-' . $iDay . ' days');
				// calculate # weeks +/-
				$iWeeks = $iBoundaryValue - intval($fOriginValue);
				// adjust by X weeks
				if ($iWeeks > 0)
					$this->adjust('+' . $iWeeks . ' weeks');
				elseif ($iWeeks < 0)
					$this->adjust($iWeeks . ' weeks');
				break;
			case 'm':
				$this->setTimePart(0, 0, 0);
				$this->setDatePart($this->year(), $iBoundaryValue + 1, 1);
				break;
			case 'y':
				$this->setTimePart(0, 0, 0);
				$this->setDatePart($iBoundaryValue, 1, 1);
				break;
			default:
				throw new \Exception('adjustAlign: invalid unit argument given');
		}
		return true;
	}


	//////////////////////////////
	// Special

	/**
	* Internal use only, do not call directly. Convert to string
	*
	* @internal
	*/
	public function __toString()
	{
		$sValue = $this->datetime();
		return $sValue ?: '';
	}

	/**
	* Internal use only, do not call directly. Return dump of internal value
	*
	* @internal
	*/
	public function dump()
	{
		return $this->format('Y-m-d H:i:s T(O)');
	}


	//////////////////////////////
	// Internal static

	protected static $aFormatLabels = array(
		'datetime' => 'Y-m-d H:i:sO',
		'date' => 'Y-m-d',
		'time' => 'H:i:s',
		'display_date' => 'l F j, Y',
		'short_display_date' => 'D M j Y'
	);
	protected static $sInternalFormat = 'Y m d H i s O e';
	protected static $iMinIntValue;

	/**
	* Internal use only, do not call directly. Class initializer
	*
	* @internal
	* @return void
	*/
	public static function _init()
	{
		self::$iMinIntValue = -2147483648;
		if (!is_int(self::$iMinIntValue))
			self::$iMinIntValue = intval(self::$iMinIntValue);
	}


	//////////////////////////////
	// Internal

	protected $oTime;

	// Get php DateTime object for date/time value
	protected function _time($mValue, $sFormat = null)
	{
		// Empty: invalid
		if (!$mValue)
			throw new \Exception('value is empty');

		// Object: Date or DateTime instance
		if (is_object($mValue)) {
			if ($mValue instanceof Date)
				$mValue = $mValue->format(self::$sInternalFormat);
			elseif ($mValue instanceof \DateTime)
				$mValue = $mValue->format(self::$sInternalFormat);
			else
				throw new \Exception('value is object of unsupported class ' . get_class($mValue));
			$sFormat = self::$sInternalFormat;
		}

		// Numeric: unix timestamp
		elseif (
			is_int($mValue)
			|| is_float($mValue)
			|| (is_numeric($mValue) && strlen($mValue) > 8)
		) {
			$mValue = '@' . strval($mValue);
			$sFormat = null;
		}

		// String: formatted date
		elseif (is_string($mValue)) {
			// Empty date, e.g. "0000-00-00" or "00/00/00"
			if (preg_match('#^(?:0000|00[/\-]00)[/\-]#', $mValue))
				throw new \Exception('value is empty');
		}

		// Boolean, resource, etc.: invalid
		else
			throw new \Exception('value is wrong type ' . gettype($mValue));

		// Create new DateTime object using value and specific format
		// [2012-06-11 kp] we use procedural calls to avoid throwing an exception for invalid dates
		// because exceptions examine the stack and can have unintended side-effects
		// such as PHP bug #50688 which affects Dataset::sort()
		// see https://bugs.php.net/bug.php?id=50688
		$oTime =
			$sFormat
			? date_create_from_format($sFormat, $mValue)
			: date_create($mValue)
		;
		if (!$oTime)
			throw new \Exception('not a valid date');

		// Return DateTime object
		return $oTime;
	}

	// Return formatted date/time value
	protected function _format($sFormat)
	{
		return $this->oTime->format($sFormat);
	}
	protected function _formatInt($sFormat)
	{
		return intval($this->oTime->format($sFormat));
	}
}

// Initialize class
Date::_init();
