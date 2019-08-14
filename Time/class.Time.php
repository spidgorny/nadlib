<?php

/**
 * This is the time inclusive the date.
 *
 */

class Time
{

	/**
	 * @var int
	 */
	public $time;
	const HUMAN = 'H:i';
	public $debug;
	public $human;

	/**
	 * Append GMT for Greenwich
	 * @param null $input
	 * @param null $relativeTo
	 */
	function __construct($input = NULL, $relativeTo = NULL)
	{
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__.' ('.MySQL::getCaller().')');
		if (!is_null($input)) { // 0 is 1970-01-01 00:00:00
			if (is_string($input)) {
				if (is_null($relativeTo)) {
					$this->time = strtotime($input);
				} else {
					$this->time = strtotime($input, $relativeTo);
				}
			} else if ($input instanceof Time) {
				$this->time = $input->getTimestamp(); // clone
				//debug('clone '.$this->getHumanDateTime());
			} else if (is_numeric($input)) {
				$this->time = $input;
			} else {
				Config::getInstance()->log(__CLASS__ . '#' . __LINE__, __('"%1" is unrecognized as a valid date.', $input));
			}
		} else {
			$this->time = time();
		}
		$this->updateDebug();
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__.' ('.MySQL::getCaller().')');
	}

	function updateDebug()
	{
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->debug = $this->getISO();
		$this->human = $this->getHumanDateTime();
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	function __toString()
	{
		return date('Y-m-d H:i:s Z', $this->time) . ' (' . $this->time . ')';
	}

	static function make($input = NULL, $relativeTo = NULL)
	{
		return new self($input, $relativeTo);
	}

	function toSQL()
	{
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * DON'T USE getTimestamp() to get amount of seconds as it depends on the TZ
	 *
	 * @return int
	 */
	function getTimestamp()
	{
		return $this->time;
	}

	/**
	 *
	 * @return int
	 */
	function getGMTTimestamp()
	{
		return strtotime($this->getISODate() . ' ' . $this->getTime() . ' GMT');
	}

	/**
	 * Doesn't modify self
	 *
	 * @param string $formula
	 * @return Time
	 */
	function math($formula)
	{
		return new self(strtotime($formula, $this->time));
	}

	function earlier(Time $than)
	{
		return $this->time < $than->time;
	}

	function earlierOrEqual(Time $than)
	{
		return $this->time <= $than->time;
	}

	function later(Time $than)
	{
		return $this->time > $than->time;
	}

	function laterOrEqual(Time $than)
	{
		/*		debug(array(
					$this->time,
					$than->time,
				));
		*/
		return $this->time >= $than->time;
	}

	/**
	 * YMDTHISZ
	 *
	 * @return string
	 */
	function getISO()
	{
		return gmdate('Ymd\THis\Z', $this->time);
	}

	/**
	 * System readable 2009-12-21
	 *
	 * @return string
	 */
	function getISODate()
	{
		return date('Y-m-d', $this->time);
	}

	function getISODateTime()
	{
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * Human readable 21.02.1979
	 *
	 * @return string
	 */
	function getHumanDate()
	{
		return date('d.m.Y', $this->time);
	}

	/**
	 * @return string
	 */
	function getMySQL()
	{
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * @return string
	 */
	function getMySQLUTC()
	{
		return gmdate('Y-m-d H:i:s', $this->time);
	}

	/**
	 * @return string
	 */
	function getDate()
	{
		return date('d.m.Y', $this->time);
	}

	/**
	 * 12:21
	 *
	 * @return string
	 */
	function getHumanTime()
	{
		return date('H:i', $this->time);
	}

	/**
	 * 12:21:15
	 *
	 * @param string $format
	 * @return string
	 */
	function getTime($format = 'H:i:s')
	{
		return date($format, $this->time);
	}

	/**
	 * @return string
	 */
	function getDateTime()
	{
		return date('d.m.Y H:i:s', $this->time);
	}

	/**
	 * @return string
	 */
	function getHumanDateTime()
	{
		return date('d.m.Y H:i', $this->time);
	}

	/**
	 * (C) yasmary at gmail dot com
	 * Link: http://de.php.net/time
	 *
	 * @return string
	 */
	function in()
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$periods = array(
			"second",
			"minute",
			"hour",
			"day",
			"week",
			"month",
			"year",
			"decade"
		);
		$pperiods = array(
			"seconds",
			"minutes",
			"hours",
			"days",
			"weeks",
			"months",
			"years",
			"decades"
		);
		foreach ($periods as &$period) {
			$period = __($period);
		}
		foreach ($pperiods as &$period) {
			$period = __($period);
		}
		$lengths = array("60", "60", "24", "7", "4.35", "12", "10");

		$now = time();
		$unix_date = $this->time;

		// check validity of date
		if (empty($unix_date)) {
			return __("Bad date");
		}

		// is it future date or past date
		if ($now > $unix_date) {
			$difference = $now - $unix_date;
			$tense = __("ago");

		} else {
			$difference = $unix_date - $now;
			$tense = __("from now");
		}

		for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) {
			$difference /= $lengths[$j];
		}

		$difference = round($difference);

		if ($difference != 1) {
			$period = $pperiods[$j];
		} else {
			$period = $periods[$j];
		}

		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return "$difference $period {$tense}";
	}

	/**
	 * <span class="time">in 10 hours</span>
	 *
	 * @return htmlString
	 */
	function render()
	{
		return new htmlString('<span class="time" title="' . $this->getDateTime() . '">' . $this->in() . '</span>');
	}

	/**
	 * Displays start of an hour with larger font
	 *
	 * @return string
	 */
	function renderCaps()
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$noe = $this->format('H:i');
		if ($noe{3} . $noe{4} != '00') {
			$noe = '<small>' . $noe . '</small>';
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $noe;
	}

	/**
	 * Calls the date function
	 * @param $rules
	 * @return string
	 */
	function format($rules)
	{
		if ($this->time) {
			$content = date($rules, $this->time);
		}
		return $content;
	}

	/**
	 * Use it for all the duration times
	 */
	function gmFormat($rules)
	{
		return gmdate($rules, $this->time);
	}

	/**
	 * Almost like getISO() but without timezone: 'Y-m-d H:i:s'
	 *
	 * @return string
	 */
	function getSystem()
	{
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * Modifies self!
	 *
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	function add(Time $plus, $debug = FALSE)
	{
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->time = $this->plus($plus, $debug)->time;
		$this->updateDebug();
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $this;
	}

	/**
	 * Modifies self!
	 *
	 * @param Duration $plus
	 * @param bool $debug
	 * @return $this
	 */
	function addDur(Duration $plus, $debug = FALSE)
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->time = $this->time + $plus->getTimestamp();
		$this->updateDebug();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $this;
	}

	/**
	 * Modifies self!
	 *
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	function substract(Time $plus, $debug = FALSE)
	{
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->time = $this->minus2($plus, $debug)->time;
		$this->updateDebug();
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $this;
	}

	/**
	 * Does not modify itself
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	function plus(Time $plus, $debug = FALSE)
	{
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		/*$format = '+ '.
			($plus->format('Y')-1970).' years '.
			($plus->format('m')-1).' months '.
			($plus->format('d')-1).' days '.
			$plus->format('H').' hours '.
			$plus->format('i').' minutes '.
			$plus->format('s').' seconds';
		$new = strtotime($format, $this->time);*/
		//$format = gmmktime($plus->format('H'), $plus->format('i'), $plus->format('s'), $plus->format('m'), $plus->format('d'), $plus->format('Y'));
		$format = $plus->getTimestamp();
		$new = $this->time + $format;

		if ($debug) {
			echo $this . ' + ' . $format . ' (' . date('Y-m-d H:i:s', is_long($format) ? $format : 0) . ') = [' . $new . ']<br>';
		}
		$new = new self($new);
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $new;
	}

	function plusDur(Duration $plus)
	{
		return new self($this->time + $plus->getTimestamp());
	}

	/**
	 * Does not modify itself
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	function minus(Time $plus, $debug = FALSE)
	{
		return $this->minus2($plus, $debug);
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$format = '- ' .
			($plus->format('Y') - 1970) . ' years ' .
			($plus->format('m') - 1) . ' months ' .
			($plus->format('d') - 1) . ' days ' .
			$plus->format('H') . ' hours ' .
			$plus->format('i') . ' minutes ' .
			$plus->format('s') . ' seconds ago';
		$new = strtotime($format, $this->time);
		$static = get_class($this);
		$new = new $static($new);
		if ($debug) echo $this . ' ' . $format . ' = ' . $new . '<br>';
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $new;
	}

	/**
	 * Does not modify itself
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	function minus2(Time $plus, $debug = FALSE)
	{
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		//$format = gmmktime($plus->format('H'), $plus->format('i'), $plus->format('s'), $plus->format('m'), $plus->format('d'), $plus->format('Y'));
		$format = $plus->getTimestamp();
		$new = $this->time - $format;
		if ($debug) echo $this . ' - ' . $format . ' = ' . $new . '<br>';
		$static = get_class($this);
		$new = new $static($new);
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $new;
	}

	/**
	 * Modifies itself according to the format.
	 * Truncate by hour: Y-m-d H:00:00
	 * @param string $format
	 * @return $this
	 */
	function modify($format)
	{
		/*$db = $GLOBALS['db'];
		/* @var $db dbLayerPG */
		/*$key = __METHOD__.' ('.$db->getCaller(2).', '.$db->getCaller(3).')';
		*/
		$key = __METHOD__;
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer($key);
		$t = new Time($this->format($format));
		$this->time = $t->getTimestamp();
		$this->updateDebug();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer($key);
		return $this;
	}

	/**
	 * Creates a new Time by formatting itself to a string first
	 * @param $format
	 * @return Time
	 */
	function getModify($format)
	{
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$new = new Time($this->format($format));
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $new;
	}

	function getAdjustedForTZ()
	{
		$isoWithoutZ = date('Y-m-d H:i:s', $this->getTimestamp()) . ' UTC';
		//debug($isoWithoutZ);
		return strtotime($isoWithoutZ);
	}

	function getAdjustedForUTC()
	{
		$isoWithoutZ = gmdate('Y-m-d H:i:s', $this->getTimestamp());
		$newTS = strtotime($isoWithoutZ);
		//debug($this, $isoWithoutZ, $newTS, date('Y-m-d H:i:s', $newTS));
		return $newTS;
	}

	function getDuration()
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$periods = array(
			"second" => 1,
			"minute" => 60,
			"hour" => 60,
			"day" => 24,
			"week" => 7,
			"month" => 30.5,
			"year" => 12,
			"decade" => 10,
		);
		$pmultiple = 1;
		foreach ($periods as $name => &$multiple) {
			$multiple *= $pmultiple;
			$pmultiple = $multiple;
		}
		unset($multiple); // just in case
		$periods = array_reverse($periods);

		$collect = array();
		//$timestamp = $this->getAdjustedForTZ();
		$timestamp = $this->time;
		//debug($this->time, $timestamp); exit();
		foreach ($periods as $name => $pe) {
			$result = $timestamp / $pe;
			//debug(array($timestamp, $result));
			if ($result >= 1) {
				$collect[$name] = floor($result);
				$timestamp = $timestamp % $pe;
			}
		}

		//debug($collect);
		if ($collect) {
			foreach ($collect as $name => &$val) {
				$val = $val . ' ' . $name . ($val > 1 ? 's' : '');
			}
			$content = implode(', ', $collect);
		} else {
			$content = 'no time';
		}
		//exit();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $content;
	}

	/**
	 * Modify using strtotime
	 * @param $strtotime
	 * @return Time
	 */
	function adjust($strtotime)
	{
		$newTime = strtotime($strtotime, $this->time);
		//debug($this->time, $strtotime, $newTime);
		$this->time = $newTime;
		$this->updateDebug();
		return $this;
	}

	/**
	 * Combines date and time and creates a new Time object
	 * @param $date
	 * @param $time
	 * @return Time
	 */
	static function combine($date, $time)
	{
		if ($date instanceof Time) {
			$date = $date->getISODate();
		}
		if ($time instanceof Time) {
			$time = $time->getTime();
		}
		$string = $date . ' ' . $time;
		//debug($string);
		return new self($string);
	}

	function getTimeIn($tz)
	{
		$temp = date_default_timezone_get();
		date_default_timezone_set($tz);
		$content = $this->format('H:i');
		date_default_timezone_set($temp);
		return $content;
	}

	/**
	 * Only to chain methods
	 *
	 * @static
	 * @param $str
	 * @param null $rel
	 * @return Time
	 */
	static function makeInstance($str, $rel = NULL)
	{
		return new Time($str, $rel);
	}

	function getTwo()
	{
		return strtolower(substr($this->format('D'), 0, 2));
	}

	/**
	 * @return Duration
	 */
	function getDurationObject()
	{
		return new Duration($this->time);
	}

	function older($sDuration)
	{
		$duration = new Duration($sDuration);
		$difference = Time::makeInstance('now')->minus($this);
		$older = $difference->later($duration);
		return $older;
	}

}
