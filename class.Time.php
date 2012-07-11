<?php

class Time {
	protected $time;
	public $debug;
	public $human;

	function __construct($input = NULL, $relativeTo = NULL) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__.' ('.MySQL::getCaller().')');
		if (!is_null($input)) { // 0 is 1970-01-01 00:00:00
			if (is_string($input)) {
				if (is_null($relativeTo)) {
					$this->time = strtotime($input);
				} else {
					$this->time = strtotime($input, $relativeTo);
				}
			} else if ($input instanceof Time) {
				$this->time = $input->getTimestamp(); // clone
			} else {
				$this->time = $input;
			}
		} else {
			$this->time = time();
		}
		$this->updateDebug();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__.' ('.MySQL::getCaller().')');
	}

	function updateDebug() {
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->debug = $this->getISO();
		$this->human = $this->getHumanDateTime();
		//if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	function __toString() {
		return date('Y-m-d H:i:s Z', $this->time).' ('.$this->time.')';
	}

	function toSQL() {
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * DON'T USE getTimestamp() to get amount of seconds as it depends on the TZ
	 *
	 * @return unknown
	 */
	function getTimestamp() {
		return $this->time;
	}

	/**
	 *
	 * @return unknown
	 */
	function getGMTTimestamp() {
		return strtotime($this->getISODate().' '.$this->getTime().' GMT');
	}

	/**
	 * Doesn't modify self
	 *
	 * @param string $formula
	 * @return Time
	 */
	function math($formula) {
		return new Time(strtotime($formula, $this->time));
	}

	function earlier(Time $than) {
		return $this->time < $than->time;
	}

	function earlierOrEqual(Time $than) {
		return $this->time <= $than->time;
	}

	function later(Time $than) {
		return $this->time > $than->time;
	}

	function laterOrEqual(Time $than) {
		return $this->time >= $than->time;
	}

	/**
	 * YMDTHISZ
	 *
	 * @return unknown
	 */
	function getISO() {
		return gmdate('Y-m-d H:i \G\M\T', $this->time);
	}

	/**
	 * System readable 2009-12-21
	 *
	 * @return unknown
	 */
	function getISODate() {
		return date('Y-m-d', $this->time);
	}

	function getISODateTime() {
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * Human readable 21.02.1979
	 *
	 * @return unknown
	 */
	function getHumanDate() {
		return date('d.m.Y', $this->time);
	}

	function getHumanDateTime() {
		return date('d.m.Y', $this->time).' '.date('H:i', $this->time);
	}

	/**
	 * (C) yasmary at gmail dot com
	 * Link: http://de.php.net/time
	 *
	 * @return unknown
	 */
	function in() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
	    $periods         = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	    $lengths         = array("60","60","24","7","4.35","12","10");

	    $now             = time();
	    $unix_date       = $this->time;

	       // check validity of date
	    if(empty($unix_date)) {
	        return "Bad date";
	    }

	    // is it future date or past date
	    if($now > $unix_date) {
	        $difference     = $now - $unix_date;
	        $tense         = "ago";

	    } else {
	        $difference     = $unix_date - $now;
	        $tense         = "from now";
	    }

	    for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
	        $difference /= $lengths[$j];
	    }

	    $difference = round($difference);

	    if($difference != 1) {
	        $periods[$j].= "s";
	    }

		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	    return "$difference $periods[$j] {$tense}";
	}

	/**
	 * <span class="time">in 10 hours</span>
	 *
	 * @return unknown
	 */
	function render() {
		return '<span class="time">'.$this->in().'</span>';
	}

	/**
	 * Displays start of an hour with larger font
	 *
	 * @return unknown
	 */
	function renderCaps() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$noe = $this->format('H:i');
		if ($noe{3}.$noe{4} != '00') {
			$noe = '<small>'.$noe.'</small>';
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $noe;
	}

	function format($rules) {
		return date($rules, $this->time);
	}

	/**
	 * Use it for all the duration times
	 */
	function gmFormat($rules) {
		return gmdate($rules, $this->time);
	}

	/**
	 * Almost like getISO() but without timezone: 'Y-m-d H:i:s'
	 *
	 * @return unknown
	 */
	function getSystem() {
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * Modifies self!
	 *
	 * @param Time $plus
	 * @return unknown
	 */
	function add(Time $plus, $debug = FALSE) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->time = $this->plus($plus, $debug)->time;
		$this->updateDebug();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $this;
	}

	/**
	 * Modifies self!
	 *
	 * @param Time $plus
	 * @return unknown
	 */
	function substract(Time $plus, $debug = FALSE) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->time = $this->minus2($plus, $debug)->time;
		$this->updateDebug();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $this;
	}

	/**
	 *
	 * @param Time $plus
	 * @return Time
	 */
	function plus(Time $plus, $debug = FALSE) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		//$format = gmmktime($plus->format('H'), $plus->format('i'), $plus->format('s'), $plus->format('m'), $plus->format('d'), $plus->format('Y'));
		$format = $plus->getTimestamp();
		$new = $this->time + $format;

		if ($debug) {
			echo $this . ' + ' . $format . ' (' . date('Y-m-d H:i:s', is_long($format) ? $format : 0) . ') = [' . $new.']<br>';
		}
		$new = new Time($new);
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $new;
	}

	/**
	 *
	 * @param Time $plus
	 * @return Time
	 */
	function minus(Time $plus, $debug = FALSE) {
		return $this->minus2($plus, $debug);
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$format = '- '.
			($plus->format('Y')-1970).' years '.
			($plus->format('m')-1).' months '.
			($plus->format('d')-1).' days '.
			$plus->format('H').' hours '.
			$plus->format('i').' minutes '.
			$plus->format('s').' seconds ago';
		$new = strtotime($format, $this->time);
		$new = new Time($new);
		if ($debug) echo $this . ' '. $format.' = '.$new.'<br>';
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $new;
	}

	/**
	 *
	 * @param Time $plus
	 * @return Time
	 */
	function minus2(Time $plus, $debug = FALSE) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		//$format = gmmktime($plus->format('H'), $plus->format('i'), $plus->format('s'), $plus->format('m'), $plus->format('d'), $plus->format('Y'));
		$format = $plus->getTimestamp();
		$new = $this->time - $format;
		if ($debug) echo $this . ' '. $format.' = '.$new.'<br>';
		$new = new Time($new);
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $new;
	}

	/**
	 * Modifies itself according to the format. Truncate by hour: Y-m-d H:00:00
	 *
	 * @param unknown_type $format
	 */
	function modify($format) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$new = new Time($this->format($format));
		$this->time = $new->getTimestamp();
		$this->updateDebug();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	function getModify($format) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$new = new Time($this->format($format));
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $new;
	}

	/**
	 * 12:21:15
	 *
	 * @return unknown
	 */
	function getTime() {
		return date('H:i:s', $this->time);
	}

	/**
	 * 12:21
	 *
	 * @return unknown
	 */
	function getHumanTime() {
		return date('H:i', $this->time);
	}

	function getAdjustedForTZ() {
		$isoWithoutZ = date('Y-m-d H:i:s', $this->getTimestamp()).' UTC';
		//debug($isoWithoutZ);
		return strtotime($isoWithoutZ);
	}

	function getAdjustedForUTC() {
		$isoWithoutZ = gmdate('Y-m-d H:i:s', $this->getTimestamp());
		$newTS = strtotime($isoWithoutZ);
		//debug($this, $isoWithoutZ, $newTS, date('Y-m-d H:i:s', $newTS));
		return $newTS;
	}

	function getDuration() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
	    $periods = array(
	    	"second" => 1,
	    	"minute" => 60,
	    	"hour"   => 60,
	    	"day"    => 24,
	    	"week"   => 7,
	    	"month"  => 30.5,
	    	"year"   => 12,
	    	"decade" => 10,
	    );
	    $pmultiple = 1;
	    foreach ($periods as $name => &$multiple) {
	    	$multiple *= $pmultiple;
	    	$pmultiple = $multiple;
	    } unset($multiple); // just in case
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
	    foreach ($collect as $name => &$val) {
	    	$val = $val . ' ' . $name . ($val > 1 ? 's' : '');
	    }
	    $content = implode(', ', $collect);
	    //exit();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	    return $content;
	}

	static function combine($date, $time) {
		if ($date instanceof Time) {
			$date = $date->getISODate();
		}
		if ($time instanceof Time) {
			$time = $time->getTime();
		}
		$string = $date.' '.$time;
		//debug($string);
		return new self($string);
	}

	function getTimeIn($tz) {
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
	 * @return Time
	 */
	static function makeInstance($str, $rel = NULL) {
		return new Time($str, $rel);
	}

}
