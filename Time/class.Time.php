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
	 * Note: used name getRefactoredTime instead of getTime as getTime was already used
	 * @return int
	 */
	public function getRefactoredTime()
	{
		return $this->time;
	}

	/**
	 * @param int $time
	 */
	public function setTime($time)
	{
		$this->time = $time;
	}

	/**
	 * @return string
	 */
	public function getDebug()
	{
		return $this->debug;
	}

	/**
	 * @param string $debug
	 */
	public function setDebug($debug)
	{
		$this->debug = $debug;
	}

	/**
	 * @return string
	 */
	public function getHuman()
	{
		return $this->human;
	}

	/**
	 * @param string $human
	 */
	public function setHuman($human)
	{
		$this->human = $human;
	}

	/**
	 * Append GMT for Greenwich
	 * @param null $input
	 * @param null $relativeTo
	 */
	public function __construct($input = NULL, $relativeTo = NULL)
	{
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
				Config::getInstance()->log(__CLASS__ . '#' . __LINE__, __('"%1" is unrecognized as a valid date.', $input));
			}
		} else {
			$this->time = time();
		}
		$this->updateDebug();
		//TaylorProfiler::stop(__METHOD__.' ('.MySQL::getCaller().')');
	}

	public function updateDebug()
	{
		//TaylorProfiler::start(__METHOD__);
		$this->debug = $this->getISO();
		$this->human = $this->getHumanDateTime();
		//TaylorProfiler::stop(__METHOD__);
	}

	function __toString()
	{
		return $this->format($this->format);
	}

	/**
	 * @param null $input
	 * @param null $relativeTo
	 * @return static
	 */
	public static function make($input = NULL, $relativeTo = NULL)
	{
		$self = get_called_class();
		return new $self($input, $relativeTo);
	}

	public function toSQL()
	{
		return date('Y-m-d H:i:s', $this->getRefactoredTime());
	}

	/**
	 * DON'T USE getTimestamp() to get amount of seconds as it depends on the TZ
	 *
	 * @return int
	 */
	public function getTimestamp()
	{
		return $this->getRefactoredTime();
	}

	/**
	 * @return int
	 */
	public function getGMTTimestamp()
	{
		return strtotime($this->getISODate() . ' ' . $this->getTime() . ' GMT');
	}

	/**
	 * Doesn't modify self
	 *
	 * @param string $formula
	 * @return Time
	 */
	public function math($formula)
	{
		return new self(strtotime($formula, $this->getRefactoredTime()));
	}

	/**
	 * @param Time $than
	 * @return bool
	 */
	public function earlier(Time $than)
	{
		return $this->getRefactoredTime() < $than->time;
	}

	/**
	 * @param Time $than
	 * @return bool
	 */
	public function earlierOrEqual(Time $than)
	{
		return $this->getRefactoredTime() <= $than->time;
	}

	/**
	 * @param Time $than
	 * @return bool
	 */
	public function later(Time $than)
	{
		return $this->getRefactoredTime() > $than->time;
	}

	/**
	 * @param Time $than
	 * @return bool
	 */
	public function laterOrEqual(Time $than)
	{
		return $this->getRefactoredTime() >= $than->time;
	}

	/**
	 * YMDTHISZ
	 *
	 * @return string
	 */
	public function getISO()
	{
		return gmdate('Ymd\THis\Z', $this->time);
	}

	/**
	 * System readable 2009-12-21
	 *
	 * @return string
	 */
	public function getISODate()
	{
		return date('Y-m-d', $this->getRefactoredTime());
	}

	/**
	 * @return bool|string
	 */
	public function getISODateTime()
	{
		return date('Y-m-d H:i:s', $this->getRefactoredTime());
	}

	/**
	 * Human readable 21.02.1979
	 *
	 * @return string
	 */
	public function getHumanDate()
	{
		return date('d.m.Y', $this->getRefactoredTime());
	}

	/**
	 * @return string
	 */
	public function getMySQL()
	{
		return date('Y-m-d H:i:s', $this->getRefactoredTime());
	}

	/**
	 * @return string
	 */
	public function getMySQLUTC()
	{
		return gmdate('Y-m-d H:i:s', $this->getRefactoredTime());
	}

	/**
	 * @return string
	 */
	public function getDate()
	{
		return date('d.m.Y', $this->getRefactoredTime());
	}

	/**
	 * 12:21
	 *
	 * @return string
	 */
	public function getHumanTime()
	{
		return date('H:i', $this->getRefactoredTime());
	}

	/**
	 * 12:21
	 *
	 * @return string
	 */
	public function getHumanTimeGMT()
	{
		//$zone = datefmt_get_timezone();
		$zone = date_default_timezone_get();
		//datefmt_set_timezone('GMT');
		date_default_timezone_set('GMT');
		$str = date('H:i', $this->getRefactoredTime());
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
	public function getTime($format = 'H:i:s')
	{
		return date($format, $this->getRefactoredTime());
	}

	/**
	 * This is like ISO but human readable
	 * If you need human-human use getHumanDateTime()
	 * @return string
	 */
	public function getDateTime()
	{
		return date('Y-m-d H:i:s', $this->getRefactoredTime());
	}

	/**
	 * @return string
	 */
	function getHumanDateTime()
	{
		return date('Y-m-d H:i', $this->getRefactoredTime());
	}

	/**
	 * (C) yasmary at gmail dot com
	 * Link: http://de.php.net/time
	 *
	 * @return string
	 */
	public function in()
	{
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
	public function render()
	{
		return new htmlString('<time datetime="' . $this->getDateTime() . '"
			class="time" title="' . $this->getDateTime() . '">' . $this->in() . '</span>');
	}

	/**
	 * Displays start of an hour with larger font
	 *
	 * @return string
	 */
	public function renderCaps()
	{
		TaylorProfiler::start(__METHOD__);
		$noe = $this->format('H:i');
		if ($noe{3} . $noe{4} != '00') {
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
	public function format($rules)
	{
		if ($this->time) {
			$content = date($rules, $this->getRefactoredTime());
		} else {
			$content = '';
		}
		return $content;
	}

	/**
	 * Use it for all the duration times
	 */
	public function gmFormat($rules)
	{
		return gmdate($rules, $this->getRefactoredTime());
	}

	/**
	 * Almost like getISO() but without timezone: 'Y-m-d H:i:s'
	 *
	 * @return string
	 */
	public function getSystem()
	{
		return date('Y-m-d H:i:s', $this->getRefactoredTime());
	}

	/**
	 * Modifies self!
	 *
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	public function add(Time $plus, $debug = FALSE)
	{
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
	public function addDur(Duration $plus, $debug = FALSE)
	{
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
	public function substract(Time $plus, $debug = FALSE)
	{
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
	public function plus(Time $plus, $debug = FALSE)
	{
		$format = $plus->getTimestamp();
		$new = $this->time + $format;

		if (0) {
			echo $this . ' + ' . $format . ' (' . date('Y-m-d H:i:s', is_long($format) ? $format : 0) . ') = [' . $new . ']<br>';
		}
		$new = new self($new);
		//TaylorProfiler::stop(__METHOD__);
		return $new;
	}

	public function plusDur(Duration $plus)
	{
		return new self($this->getRefactoredTime() + $plus->getTimestamp());
	}

	/**
	 * Does not modify itself
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	public function minus(Time $plus, $debug = FALSE)
	{
		return $this->minus2($plus, $debug);
	}

	/**
	 * Does not modify itself
	 * @param Time $plus
	 * @param bool $debug
	 * @return Time
	 */
	public function minus2(Time $plus, $debug = FALSE)
	{
		$format = $plus->getTimestamp();
		$new = $this->getRefactoredTime() - $format;
		if ($debug) echo $this . ' - ' . $format . ' = ' . $new . '<br>';
		$static = get_class($this);
		$new = new $static($new);
		return $new;
	}

	public function getDiff(Time $t2)
	{
		return $this->getRefactoredTime() - $t2->time;
	}

	/**
	 * Modifies itself according to the format.
	 * Truncate by hour: Y-m-d H:00:00
	 * @param string $format
	 * @return $this
	 */
	public function modify($format)
	{
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
	public function getModify($format)
	{
		//TaylorProfiler::start(__METHOD__);
		$new = new Time($this->format($format));
		//TaylorProfiler::stop(__METHOD__);
		return $new;
	}

	public function getAdjustedForTZ()
	{
		$isoWithoutZ = date('Y-m-d H:i:s', $this->getTimestamp()) . ' UTC';
		//debug($isoWithoutZ);
		return strtotime($isoWithoutZ);
	}

	public function getAdjustedForUTC()
	{
		$isoWithoutZ = gmdate('Y-m-d H:i:s', $this->getTimestamp());
		$newTS = strtotime($isoWithoutZ);
		//debug($this, $isoWithoutZ, $newTS, date('Y-m-d H:i:s', $newTS));
		return $newTS;
	}

	/**
	 * @return string
	 */
	public function getDuration()
	{
		$time = time() - $this->getRefactoredTime();
		$diff = abs($time);
		$tokens = array(
			'decade' => 315360000,
			'year' => 31536000,
			'month' => 2592000,
			'week' => 604800,
			'day' => 86400,
			'hour' => 3600,
			'minute' => 60,
			'second' => 1,
		);
		$result = [];
		foreach ($tokens as $id => $length) {
			$value = floor($diff / $length);
			if ($value) {
				$result[] = "$value $id" . ($value > 1 ? 's' : '');
			}
			$diff -= $length * $value;
		}
		if (!count($result)) {
			return 'just now';
		}
		return join(', ', $result) . ($time < 0 ? ' later' : ' ago');
	}

	/**
	 * @info this was the old function to get duration string, it was not working fine and should not be used
	 * @return string
	 */
	function getDurationOld()
	{
		TaylorProfiler::start(__METHOD__);
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
		$timestamp = $this->getRefactoredTime();
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
	public function adjust($strtotime)
	{
		$newTime = strtotime($strtotime, $this->getRefactoredTime());
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
	public static function combine($date, $time)
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

	public function getTimeIn($tz)
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
	 * @return static
	 */
	public static function makeInstance($str, $rel = NULL)
	{
		$static = get_called_class();
		return new $static($str, $rel);
	}

	public function getTwo()
	{
		return strtolower(substr($this->format('D'), 0, 2));
	}

	/**
	 * @return Duration
	 */
	public function getDurationObject()
	{
		return new Duration($this->getRefactoredTime());
	}

	public function older($sDuration)
	{
		$duration = new Duration($sDuration);
		$difference = Time::makeInstance('now')->minus($this);
		$older = $difference->later($duration);
		return $older;
	}

	/**
	 * @return Date
	 */
	public function getDateObject()
	{
		return new Date($this->getTimestamp());
	}

	/**
	 * @return htmlString
	 */
	public function getHTMLDate()
	{
		return new htmlString('<time datetime="' . $this->getISODateTime() . '">' . $this->getHumanDate() . '</time>');
	}

	/**
	 * @return htmlString
	 */
	public function getHTMLTime()
	{
		return new htmlString('<time datetime="' . $this->getISODateTime() . '">' . $this->getHumanTime() . '</time>');
	}

	/**
	 * @return htmlString
	 */
	public function getHTMLTimeGMT()
	{
		return new htmlString('<time datetime="' . $this->getISODateTime() . '">' . $this->getHumanTimeGMT() . '</time>');
	}

	/**
	 * @param $string
	 */
	public function setFormat($string)
	{
		$this->format = $string;
	}

	public function makeGMT()
	{
		$this->setTimestamp(strtotime(gmdate('Y-m-d H:i:s', $this->time) . ' GMT', 0));
	}

	/**
	 * @param $time
	 */
	public function setTimestamp($time)
	{
		$this->time = $time;
		$this->updateDebug();
	}

	/**
	 * @param Date $date
	 */
	public function addDate(Date $date)
	{
		$this->setTimestamp(strtotime(date('H:i:s', $this->time), $date->getTimestamp()));
	}

	/**
	 * @return Duration
	 */
	public function getSince()
	{
		return new Duration($this->getTimestamp() - time());
	}

	/**
	 * @param $H
	 * @param int $i
	 * @param int $s
	 */
	public function setHis($H, $i = 0, $s = 0)
	{
		$this->time = strtotime(date('Y-m-d') . ' ' . $H . ':' . $i . ':' . $s);
		$this->updateDebug();
	}

	/**
	 * @return Duration
	 */
	public function getAge()
	{
		return new Duration(time() - $this->getTimestamp());
	}

	/**
	 * @return string
	 */
	public function getHumanDateOrTime()
	{
		if ($this->isToday()) {
			return $this->getHumanTime();
		} else {
			return $this->getHumanDate();
		}
	}

	/**
	 * @return bool
	 */
	public function isToday()
	{
		return date('Y-m-d', $this->getTimestamp()) == date('Y-m-d');
	}

	/**
	 * @return string
	 */
	public function getDay()
	{
		return $this->format('d');
	}

	/**
	 * @return bool
	 */
	public function isFuture()
	{
		return $this->getRefactoredTime() > time();
	}

	/**
	 * @return bool
	 */
	public function isPast()
	{
		return $this->getRefactoredTime() < time();
	}

	/**
	 * @param $sTime
	 * @return $this
	 */
	public function addTime($sTime)
	{
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

}
