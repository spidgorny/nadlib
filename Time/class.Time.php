<?php

/**
 * This is the time inclusive the date.
 *
 */

class Time {

	/**
	 * @var int
	 */
	public $time;

	const HUMAN = 'H:i';

	/**
	 * @var string
	 */
	public $debug;

	/**
	 * @var string
	 */
	public $human;

	protected $format = 'Y-m-d H:i:s Z (U)';

	/**
	 * Append GMT for Greenwich
	 * @param null $input
	 * @param null $relativeTo
	 */
	function __construct($input = NULL, $relativeTo = NULL) {
		//TaylorProfiler::start(__METHOD__.' ('.MySQL::getCaller().')');
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
			} else if (class_exists('Config')) {
				Config::getInstance()->log(__CLASS__.'#'.__LINE__, __('"%1" is unrecognized as a valid date.', $input));
			}
		} else {
			$this->time = time();
		}
		$this->updateDebug();
		//TaylorProfiler::stop(__METHOD__.' ('.MySQL::getCaller().')');
	}

	function updateDebug() {
		//TaylorProfiler::start(__METHOD__);
		$this->debug = $this->getISO();
		$this->human = $this->getHumanDateTime();
		//TaylorProfiler::stop(__METHOD__);
	}

	function __toString() {
		return $this->format($this->format);
	}

	/**
	 * @param null $input
	 * @param null $relativeTo
	 * @return static
	 */
	static function make($input = NULL, $relativeTo = NULL) {
		$self = get_called_class();
		return new $self($input, $relativeTo);
	}

	function toSQL() {
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * DON'T USE getTimestamp() to get amount of seconds as it depends on the TZ
	 *
	 * @return int
	 */
	function getTimestamp() {
		return $this->time;
	}

	/**
	 * @return int
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
		return new self(strtotime($formula, $this->time));
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
/*		debug(array(
			$this->time,
			$than->time,
		));
*/		return $this->time >= $than->time;
	}

	/**
	 * YMDTHISZ
	 *
	 * @return string
	 */
	function getISO() {
		return gmdate('Ymd\THis\Z', $this->time);
	}

	/**
	 * System readable 2009-12-21
	 *
	 * @return string
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
	 * @return string
	 */
	function getHumanDate() {
		return date('d.m.Y', $this->time);
	}

	/**
	 * @return string
	 */
	function getMySQL() {
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * @return string
	 */
	function getMySQLUTC() {
		return gmdate('Y-m-d H:i:s', $this->time);
	}

	/**
	 * @return string
	 */
	function getDate() {
		return date('d.m.Y', $this->time);
	}

	/**
	 * 12:21
	 *
	 * @return string
	 */
	function getHumanTime() {
		return date('H:i', $this->time);
	}

	/**
	 * 12:21
	 *
	 * @return string
	 */
	function getHumanTimeGMT() {
		//$zone = datefmt_get_timezone();
		$zone = date_default_timezone_get();
		//datefmt_set_timezone('GMT');
		date_default_timezone_set('GMT');
		$str = date('H:i', $this->time);
		//datefmt_set_timezone($zone);
		date_default_timezone_set($zone);
		return $str;
	}

	/**
	 * 12:21:15
	 *
	 * @param string $format
	 * @return string
	 */
	function getTime($format = 'H:i:s') {
		return date($format, $this->time);
	}

	/**
	 * This is like ISO but human readable
	 * If you need human-human use getHumanDateTime()
	 * @return string
	 */
	function getDateTime() {
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * @return string
	 */
	function getHumanDateTime() {
		return date('Y-m-d H:i', $this->time);
	}

	/**
	 * (C) yasmary at gmail dot com
	 * Link: http://de.php.net/time
	 *
	 * @return string
	 */
	function in() {
		TaylorProfiler::start(__METHOD__);
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
	    $lengths         = array("60","60","24","7","4.35","12","10");

	    $now             = time();
	    $unix_date       = $this->time;

	    // check validity of date
	    if (empty($unix_date)) {
	        return __("Bad date");
	    }

	    // is it future date or past date
	    if($now > $unix_date) {
	        $difference     = $now - $unix_date;
	        $tense         = __("ago");

	    } else {
	        $difference     = $unix_date - $now;
	        $tense         = __("from now");
	    }

	    for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
	        $difference /= $lengths[$j];
	    }

	    $difference = round($difference);

	    if (!$difference) {
			$content = __('Just now');
		} elseif ($difference != 1) {
    		$period = $pperiods[$j];
			$content = "$difference $period {$tense}";
	    } else {
    		$period = $periods[$j];
			$content = "$difference $period {$tense}";
	    }

		TaylorProfiler::stop(__METHOD__);
	    return $content;
	}

	/**
	 * <span class="time">in 10 hours</span>
	 *
	 * @return htmlString
	 */
	function render() {
		return new htmlString('<time datetime="'.$this->getDateTime().'"
			class="time" title="'.$this->getDateTime().'">'.$this->in().'</span>');
	}

	/**
	 * Displays start of an hour with larger font
	 *
	 * @return string
	 */
	function renderCaps() {
		TaylorProfiler::start(__METHOD__);
		$noe = $this->format('H:i');
		if ($noe{3}.$noe{4} != '00') {
			//$noe = '<small>'.$noe.'</small>';
			$noe = new HTMLTag('small', array(), $noe);
		}
		TaylorProfiler::stop(__METHOD__);
		return $noe;
	}

	/**
	 * Calls the date function
	 * @param $rules
	 * @return string
	 */
	function format($rules) {
		if ($this->time) {
			$content = date($rules, $this->time);
		} else {
			$content = '';
		}
		return $content;
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
	 * @return string
	 */
	function getSystem() {
		return date('Y-m-d H:i:s', $this->time);
	}

	/**
	 * Modifies self!
	 *
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	function add(Time $plus, $debug = FALSE) {
		//TaylorProfiler::start(__METHOD__);
		$this->time = $this->plus($plus, $debug)->time;
		$this->updateDebug();
		//TaylorProfiler::stop(__METHOD__);
		return $this;
	}

	/**
	 * Modifies self!
	 *
	 * @param Duration $plus
	 * @param bool $debug
	 * @return static
	 */
	function addDur(Duration $plus, $debug = FALSE) {
		TaylorProfiler::start(__METHOD__);
		$this->time = $this->time + $plus->getTimestamp();
		$this->updateDebug();
		TaylorProfiler::stop(__METHOD__);
		return $this;
	}

	/**
	 * Modifies self!
	 *
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	function substract(Time $plus, $debug = FALSE) {
		//TaylorProfiler::start(__METHOD__);
		$this->time = $this->minus2($plus, $debug)->time;
		$this->updateDebug();
		//TaylorProfiler::stop(__METHOD__);
		return $this;
	}

	/**
	 * Does not modify itself
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	function plus(Time $plus, $debug = FALSE) {
		//TaylorProfiler::start(__METHOD__);
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

		if (0) {
			echo $this . ' + ' . $format . ' (' . date('Y-m-d H:i:s', is_long($format) ? $format : 0) . ') = [' . $new.']<br>';
		}
		$new = new self($new);
		//TaylorProfiler::stop(__METHOD__);
		return $new;
	}

	function plusDur(Duration $plus) {
		return new self($this->time + $plus->getTimestamp());
	}

	/**
	 * Does not modify itself
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	function minus(Time $plus, $debug = FALSE) {
		return $this->minus2($plus, $debug);
		//TaylorProfiler::start(__METHOD__);
		$format = '- '.
			($plus->format('Y')-1970).' years '.
			($plus->format('m')-1).' months '.
			($plus->format('d')-1).' days '.
			$plus->format('H').' hours '.
			$plus->format('i').' minutes '.
			$plus->format('s').' seconds ago';
		$new = strtotime($format, $this->time);
		$static = get_class($this);
		$new = new $static($new);
		if ($debug) echo $this . ' '. $format.' = '.$new.'<br>';
		//TaylorProfiler::stop(__METHOD__);
		return $new;
	}

	/**
	 * Does not modify itself
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	function minus2(Time $plus, $debug = FALSE) {
		//TaylorProfiler::start(__METHOD__);
		//$format = gmmktime($plus->format('H'), $plus->format('i'), $plus->format('s'), $plus->format('m'), $plus->format('d'), $plus->format('Y'));
		$format = $plus->getTimestamp();
		$new = $this->time - $format;
		if ($debug) echo $this . ' - '. $format.' = '.$new.'<br>';
		$static = get_class($this);
		$new = new $static($new);
		//TaylorProfiler::stop(__METHOD__);
		return $new;
	}

	function getDiff(Time $t2) {
		return $this->time - $t2->time;
	}

	/**
	 * Modifies itself according to the format.
	 * Truncate by hour: Y-m-d H:00:00
	 * @param string $format
	 * @return $this
	 */
	function modify($format) {
		/*$db = $GLOBALS['db'];
		/* @var $db dbLayerPG */
		/*$key = __METHOD__.' ('.$db->getCaller(2).', '.$db->getCaller(3).')';
		*/
		$key = __METHOD__;
		TaylorProfiler::start($key);
		$t = new Time($this->format($format));
		$this->time = $t->getTimestamp();
		$this->updateDebug();
		TaylorProfiler::stop($key);
		return $this;
	}

	/**
	 * Creates a new Time by formatting itself to a string first
	 * @param $format
	 * @return Time
	 */
	function getModify($format) {
		//TaylorProfiler::start(__METHOD__);
		$new = new Time($this->format($format));
		//TaylorProfiler::stop(__METHOD__);
		return $new;
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
		TaylorProfiler::start(__METHOD__);
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
		if ($collect) {
			foreach ($collect as $name => &$val) {
				$val = $val . ' ' . $name . ($val > 1 ? 's' : '');
			}
			$content = implode(', ', $collect);
		} else {
			$content = 'no time';
		}
	    //exit();
		TaylorProfiler::stop(__METHOD__);
	    return $content;
	}

	/**
	 * Modify using strtotime
	 * @param $strtotime
	 * @return Time
	 */
	function adjust($strtotime) {
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
	 * @param null $rel
	 * @return static
	 */
	static function makeInstance($str, $rel = NULL) {
		$static = get_called_class();
		return new $static($str, $rel);
	}

	function getTwo() {
		return strtolower(substr($this->format('D'), 0, 2));
	}

	/**
	 * @return Duration
	 */
	function getDurationObject() {
		return new Duration($this->time);
	}

	function older($sDuration) {
		$duration = new Duration($sDuration);
		$difference = Time::makeInstance('now')->minus($this);
		$older = $difference->later($duration);
		return $older;
	}

	/**
	 * @return Date
	 */
	public function getDateObject() {
		return new Date($this->getTimestamp());
	}

	public function getHTMLDate() {
		return new htmlString('<time datetime="'.$this->getISODateTime().'">'.$this->getHumanDate().'</time>');
	}

	public function getHTMLTime() {
		return new htmlString('<time datetime="'.$this->getISODateTime().'">'.$this->getHumanTime().'</time>');
	}

	public function getHTMLTimeGMT() {
		return new htmlString('<time datetime="'.$this->getISODateTime().'">'.$this->getHumanTimeGMT().'</time>');
	}

	public function setFormat($string) {
		$this->format = $string;
	}

	public function makeGMT() {
		$this->setTimestamp(strtotime(gmdate('Y-m-d H:i:s', $this->time). ' GMT', 0));
	}

	function setTimestamp($time) {
		$this->time = $time;
		$this->updateDebug();
	}

	function addDate(Date $date) {
		$this->setTimestamp(strtotime(date('H:i:s', $this->time), $date->getTimestamp()));
	}

	public function getSince() {
		return new Duration($this->getTimestamp() - time());
	}

	function setHis($H, $i = 0, $s = 0) {
		$this->time = strtotime(date('Y-m-d').' '.$H.':'.$i.':'.$s);
		$this->updateDebug();
	}

	public function getAge() {
		return new Duration(time() - $this->getTimestamp());
	}

	public function getHumanDateOrTime() {
		if ($this->isToday()) {
			return $this->getHumanTime();
		} else {
			return $this->getHumanDate();
		}
	}

	function isToday() {
		return date('Y-m-d', $this->getTimestamp()) == date('Y-m-d');
	}

	public function getDay() {
		return $this->format('d');
	}

	public function isFuture() {
		return $this->time > time();
	}

	public function isPast() {
		return $this->time < time();
	}

	function addTime($sTime) {
		$duration = new Duration($sTime);
		nodebug($duration->getRemHours(),
				$duration->getRemMinutes(),
				$duration->getRemSeconds());
		$base = $this->getTimestamp();
		$time = strtotime(' + ' . $duration->getRemHours() . ' hours', $base);
		$time = strtotime(' + ' . $duration->getRemMinutes() . ' minutes', $time);
		$time = strtotime(' + ' . $duration->getRemSeconds() . ' seconds', $time);
		$this->setTimestamp($time);
		return $this;
	}

	function getYear() {
		return date('Y', $this->time);
	}

	function getMonth() {
		return date('m', $this->time);
	}

	function getWeek() {
		return date('W', $this->time);
	}

}
