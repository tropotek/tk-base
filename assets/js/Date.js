/**
 * Date extensions
 * 
 * From the site: http://www.xml-blog.com/files/date_extras.js
 * 
 * @author Michael Mifsud
 * @package calendar
 */

// Global Constants
var JANUARY = 0;
var FEBRUARY = 1;
var MARCH = 2;
var APRIL = 3;
var MAY = 4;
var JUNE = 5;
var JULY = 6;
var AUGUST = 7;
var SEPTEMBER = 8;
var OCTOBER = 9;
var NOVEMBER = 10;
var DECEMBER = 11;

var SUNDAY = 0;
var MONDAY = 1;
var TUESDAY = 2;
var WEDNESDAY = 3;
var THURSDAY = 4;
var FRIDAY = 5;
var SATURDAY = 6;


/**
 * Return a date from a string in the format of dd/mm/yyyy
 *
 * @returns Date
 * @todo Would be good to have a method of parsing a date based on its local
 */
String.prototype.toDate = function() {
  var arr = this.match( /^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/ );
  if (arr) {
    return new Date(arr[3], arr[2], arr[1]);
  }
  if (console) console.log('Cannot create Date from string: ' + this);
};




/**
 * Return the timestamp in a format for php
 * @returns {number}
 */
Date.prototype.getPhpTime = function() {
  return Math.round(this.getTime() / 1000);
};
Date.prototype.getCivilianHours = function() {
  return (this.getHours() < 12) ? this.getHours() : this.getHours() - 12;
};
Date.prototype.getMeridiem = function() {
  return (this.getHours() < 12) ? "AM" : "PM";
};

// Non-destructive instance methods
Date.prototype.addMilliseconds = function(ms) {
  return new Date(new Date().setTime(this.getTime() + (ms)));
};
Date.prototype.addSeconds = function(s) {
  return new Date(this.getFullYear(), this.getMonth(), this.getDate(), 
      this.getHours(), this.getMinutes(), this.getSeconds() + s);
};
Date.prototype.addMinutes = function(m) {
  return new Date(this.getFullYear(), this.getMonth(), this.getDate(), 
      this.getHours(), this.getMinutes() + m, this.getSeconds());
};
Date.prototype.addHours = function(h) {
  return new Date(this.getFullYear(), this.getMonth(), this.getDate(), 
      this.getHours() + h, this.getMinutes(), this.getSeconds());
};
Date.prototype.addDays = function(d) {
  return new Date(this.getFullYear(), this.getMonth(), this.getDate() + d, 
      this.getHours(), this.getMinutes(), this.getSeconds());
};
Date.prototype.addWeeks = function(w) {
  d = w * 7;
  return new Date(this.getFullYear(), this.getMonth(), this.getDate() + d, 
      this.getHours(), this.getMinutes(), this.getSeconds());
};
Date.prototype.addMonths = function(m) {
  return new Date(this.getFullYear(), this.getMonth() + m, this.getDate(), 
      this.getHours(), this.getMinutes(), this.getSeconds());
};
Date.prototype.addYears = function(y) {
  return new Date(this.getFullYear() + y, this.getMonth(), this.getDate(), 
      this.getHours(), this.getMinutes(), this.getSeconds());
};

/**
 * Get a months long name.
 * 
 * @param int (optional) The month to get name for.
 */
Date.prototype.getMonthName = function() {
  var index = (0 === arguments.length) ? this.getMonth() : arguments[0];
  switch (index) {
  case JANUARY:
    return "January";
  case FEBRUARY:
    return "February";
  case MARCH:
    return "March";
  case APRIL:
    return "April";
  case MAY:
    return "May";
  case JUNE:
    return "June";
  case JULY:
    return "July";
  case AUGUST:
    return "August";
  case SEPTEMBER:
    return "September";
  case OCTOBER:
    return "October";
  case NOVEMBER:
    return "November";
  case DECEMBER:
    return "December";
  default:
    throw "Invalid month index: " + index.toString();
  }
};

/**
 * Get a months Abbreviated name.
 * 
 * @param int (optional) The month to get name for.
 */
Date.prototype.getMonthAbbreviation = function() {
  var index = (0 === arguments.length) ? this.getMonth() : arguments[0];
  switch (index) {
  case JANUARY:
    return "Jan";
  case FEBRUARY:
    return "Feb";
  case MARCH:
    return "Mar";
  case APRIL:
    return "Apr";
  case MAY:
    return "May";
  case JUNE:
    return "Jun";
  case JULY:
    return "Jul";
  case AUGUST:
    return "Aug";
  case SEPTEMBER:
    return "Sep";
  case OCTOBER:
    return "Oct";
  case NOVEMBER:
    return "Nov";
  case DECEMBER:
    return "Dec";
  default:
    throw "Invalid month index: " + index.toString();
  }
};

/**
 * Get a day name.
 * 
 * @param int (optional) The day to get name for.
 */
Date.prototype.getDayName = function() {
  var index = (0 === arguments.length) ? this.getDay() : arguments[0];
  switch (index) {
  case SUNDAY:
    return "Sunday";
  case MONDAY:
    return "Monday";
  case TUESDAY:
    return "Tuesday";
  case WEDNESDAY:
    return "Wednesday";
  case THURSDAY:
    return "Thursday";
  case FRIDAY:
    return "Friday";
  case SATURDAY:
    return "Saturday";
  default:
    throw "Invalid day index: " + index.toString();
  }
};

/**
 * Get a day Abbreviated name.
 * 
 * @param int (optional) The day to get name for.
 */
Date.prototype.getDayAbbreviation = function() {
  var index = (0 === arguments.length) ? this.getDay() : arguments[0];
  switch (index) {
  case SUNDAY:
    return "Sun";
  case MONDAY:
    return "Mon";
  case TUESDAY:
    return "Tue";
  case WEDNESDAY:
    return "Wed";
  case THURSDAY:
    return "Thu";
  case FRIDAY:
    return "Fri";
  case SATURDAY:
    return "Sat";
  default:
    throw "Invalid day index: " + index.toString();
  }
};

/**
 * Get the number of days in this month or given month.
 * 
 * @param int (optional) The month to get value for
 */
Date.prototype.getDaysInMonth = function() {
  var index = arguments[0] != null ? arguments[0] : this.getMonth();

  switch (this.getMonth()) {
  case JANUARY:
    return 31;
  case FEBRUARY:
    return this.isLeapYear() ? 29 : 28;
  case MARCH:
    return 31;
  case APRIL:
    return 30;
  case MAY:
    return 31;
  case JUNE:
    return 30;
  case JULY:
    return 31;
  case AUGUST:
    return 31;
  case SEPTEMBER:
    return 30;
  case OCTOBER:
    return 31;
  case NOVEMBER:
    return 30;
  case DECEMBER:
    return 31;
  default:
    throw "Invalid month index: " + index.toString();
  }
};

/**
 * Test if this month is a leap year.
 */
Date.prototype.isLeapYear = function() {
  if (0 === this.getFullYear() % 400)
    return true;
  if (0 === this.getFullYear() % 100)
    return false;
  return (0 === this.getFullYear() % 4);
};

/**
 * Get a DAte object of the first day of this objects month.
 * 
 * @return Date
 */
Date.prototype.getFirstDayOfMonth = function() {
  return new Date(this.getFullYear(), this.getMonth(), 1, 12, 0, 0);
};

/**
 * Get a Date object of the last day of this objects month.
 * 
 * @return Date
 */
Date.prototype.getLastDayOfMonth = function() {
  return new Date(this.getFullYear(), this.getMonth(), this.getDaysInMonth(), 12, 0, 0);
};

/**
 * Return a clone of this date
 * 
 * @return Date
 */
Date.prototype.clone = function() {
  var dt = new Date();
  dt.setTime(this.getTime());
  return dt;
};

/**
 * Return a clone date that has a zero value time.
 * 
 * @return Date
 */
Date.prototype.floor = function() {
  var dt = this.clone();
  dt.setHours(0);
  dt.setMinutes(0);
  dt.setSeconds(0);
  dt.setMilliseconds(0);
  return dt;
};

/**
 * Return a clone date that has a time of noon
 * 
 * @return Date
 */
Date.prototype.ceil = function() {
  var dt = this.clone();
  dt.setHours(23);
  dt.setMinutes(59);
  dt.setSeconds(59);
  dt.setMilliseconds(99);
  return dt;
};

/**
 * Return the time difference in seconds
 * 
 * @return Number
 */
Date.prototype.diff = function(date) {
  return parseInt((this.getTime() - date.getTime()) / 1000);
};

//Date.prototype.to_s = Date.prototype.toString;
/**
 * Ultra-flexible date formatting
 * 
 *   %YYYY = 4 digit year (2005)
 *   %YY = 2 digit year (05)
 *   %MMMM = Month name (March)
 *   %MMM = Month abbreviation (March becomes Mar)
 *   %MM = 2 digit month number (March becomes 03)
 *   %M = 1 or 2 digit month (March becomes 3)
 *   %DDDD = Day name (Thursday)
 *   %DDD = Day abbreviation (Thu)
 *   %DD = 2 digit day (09)
 *   %D = 1 or 2 digit day (9)
 *   %HH = 2 digit 24 hour (13)
 *   %H = 1 or 2 digit 24 hour (9)
 *   %hh = 2 digit 12 Hour (01)
 *   %h = 1 or 2 digit 12 Hour (01)
 *   %mm = 2 digit  minute (02)
 *   %m = 1 or 2 digit minute (2)
 *   %ss = 2 digit second (59)
 *   %s = 1 or 2 digit second (1)
 *   %nnn = milliseconds
 *   %p = AM/PM indicator
 *
 * EG:
 *   '%DDD %DD %MMM %YYYY %HH:%mm::%ss' = 'Thu 09 Mar 2013 12:22:25'
 *   '%DD-%MM-%YYYY' = '02-25-2003'
 *   '%YYYY-%MM-%DD %HH:%mm::%ss' = '2003-25-02 12:22:25'
 *   '%YYYY-%MM-%DD' = '2003-25-02'
 *   '%HH:%mm::%ss' = '12:22:25'
 *
 * @param format
 * @return string|{*}
 */
Date.prototype.format = function(format) {

  format = format.replace(/%YYYY/, this.getFullYear().toString());
  format = format.replace(/%YY/, this.getFullYear().toString().substr(2, 2));

  format = format.replace(/%MMMM/, this.getMonthName(this.getMonth()).toString());
  format = format
      .replace(/%MMM/, this.getMonthAbbreviation(this.getMonth()).toString());
  format = format.replace(/%MM/, (this.getMonth() + 1) > 9 ? (this.getMonth() + 1)
      .toString() : "0" + (this.getMonth() + 1).toString());
  format = format.replace(/%M/, (this.getMonth() + 1).toString());

  format = format.replace(/%DDDD/, this.getDayName(this.getDay()).toString());
  format = format.replace(/%DDD/, this.getDayAbbreviation(this.getDay()).toString());
  format = format.replace(/%DD/, this.getDate() > 9 ? this.getDate().toString() : "0"
      + this.getDate().toString());
  format = format.replace(/%D/, this.getDate().toString());

  format = format.replace(/%HH/, this.getHours() > 9 ? this.getHours().toString() : "0"
      + this.getHours().toString());
  format = format.replace(/%H/, this.getHours().toString());
  format = format.replace(/%hh/, this.getCivilianHours() > 9 ? this.getCivilianHours()
      .toString() : "0" + this.getCivilianHours().toString());
  format = format.replace(/%h/, this.getCivilianHours());

  format = format.replace(/%mm/, this.getMinutes() > 9 ? this.getMinutes().toString()
      : "0" + this.getMinutes().toString());
  format = format.replace(/%m/, this.getMinutes().toString());

  format = format.replace(/%ss/, this.getSeconds() > 9 ? this.getSeconds().toString()
      : "0" + this.getSeconds().toString());
  format = format.replace(/%s/, this.getSeconds().toString());

  format = format.replace(/%nnn/, this.getMilliseconds().toString());
  format = format.replace(/%p/, this.getMeridiem());
  return format;
};


/**
 * Create a javascript date from a PHP timestamp
 *
 * @param phpTimestamp int
 * @returns {Date}
 */
Date.createPhpDate = function(phpTimestamp) {
  return new Date(phpTimestamp * 1000);
};

/**
 * @param dateFrom
 * @param dateTo
 * @param unitLabel
 * @return Number
 */
Date.getUnitValueFromDates = function(dateFrom, dateTo, unitLabel) {

  var units = 0;
  if (!dateFrom) return 0;
  dateFrom = dateFrom.floor();
  if (!dateTo) return 0;
  dateTo = dateTo.ceil();
  var secs = dateTo.diff(dateFrom);

  switch(unitLabel) {
    case 'Hours':
      units = Math.round(secs/(60*60));
      break;
    case 'Days':
      units = Math.round(secs/(60*60*24));
      break;
    case 'Weeks':
      units = Math.round(secs/(60*60*24*7));
      break;
    case 'Months':
      units = Math.round(secs/(60*60*24*28));
      break;
    case 'Years':
      units = Math.round(secs/(60*60*24*365));
      break;
  }

  return units;
};

