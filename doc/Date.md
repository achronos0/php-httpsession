# Date #

This class is a php DateTime wrapper, designed to make date manipulation more convenient.

## Overview ##

Create a date object:

	// From string
	$oDate = new Date('2016-12-31 14:36');
	$oDate = new Date('Dec 31, 2016 2:36 PM');
	$oDate = new Date('midnight');

	// From unix timestamp
	$oDate = new Date(time() + 30);

	// Copy an existing date object
	$oPhpDate = new DateTime('now');
	$oDate = new Date($oPhpDate);
	$oDateCopy = new Date($oDate);

	// From separate parts
	$oDate = Date::createFromParts(2016, 12, 31, 14, 36, 0);

	// Ensure value is an object (if it already is object, do not copy, return original)
	$oDate = Date::obj($mMaybeDateObject);

Get formatted date value:

	$sValue = $oDate->datetime();
	$sDateOnly = $oDate->date();
	$sValue = $oDate->format('M j Y, g:i a');

Extract date information:

	// Get date part
	$iYear = $oDate->year();
	$sMonthName = $oDate->monthName();
	$sTimezone = $oDate->timezone();
	// etc

	// Get array of date parts
	$aParts = $oDate->parts();

	// Get info about calendar date
	$iMonthDays = $oDate->daysInMonth();
	$iYearDays = $oDate->daysInYear();
	$bLeapYear = $oDate->isLeapYear();

Modify date:

	// Add/subtract an interval
	$oDate->adjust('+2 months +1 day midnight');

	// Change timezone
	$oDate->changeTimezone('America/New_York');

	// Adjust date to closest 10-minute interval
	$oDate->adjustAlign('minute', 10);

	// As above, but return new date object instead of changing $oDate
	$oNewDate = $oDate->relative('+2 months +1 day midnight');
	$oNewDate = $oDate->relocate('America/New_York');
	$oNewDate = $oDate->align('minute', 10);

Compare/contrast dates:

	// Compare date to another date
	$bEarlier = $oDate->before('2016-12-31');
	$bLater = $oDate->after('2016-12-31');
	switch ($oDate->compare('2016-12-31') {
		case -1:
			$sResult = 'Value is earlier than Dec 31, 2016';
			break;
		case 1:
			$sResult = 'Value is later than Dec 31, 2016';
			break;
		case 0:
			$sResult = 'Value is exactly Dec 31, 2016 midnight';
			break;
	}

	// Check if date is in given range
	$bWithin = $oDate->between('2016-01-01', '2016-12-31 23:59:59');

	// Calculate difference between dates
	$iMonths = $oDate->difference('2016-01-01', 'months');

	// Get a list of intermediate steps between two dates
	$oDate = new Date('2016-01-01');
	foreach ($oDate->range('2016-01-31', '+1 day') as $oNextDate) {
		// ...
	}
