<?php
# PHP Calendar (version 2.3), written by Keith Devens
# http://keithdevens.com/software/php_calendar
#  see example at http://keithdevens.com/weblog
# License: http://keithdevens.com/software/license

class KDCalendar {

	var $year, $month;

	var $days;

	var $day_name_length;

	var $month_href;

	var $first_day;

	var $pn;

	var $weekTotalCallback;

	var $titleLinkMore;

	var $weekNrLink;

	/**
	 * @param        $year
	 * @param        $month
	 * @param array  $days day-indexed array of [$link, $classes, $content]
	 * @param int    $day_name_length
	 * @param null   $month_href
	 * @param int    $first_day
	 * @param array  $pn
	 * @param null   $callback
	 * @param string $titleLinkMore
	 * @param null   $weekNrLink <a href="bla">|</a>
	 */
	function __construct($year, $month, $days = [],
						 $day_name_length = 3,
						 $month_href = NULL,
						 $first_day = 0,
						 $pn = [],
						 $callback = NULL,
						 $titleLinkMore = '',
						 $weekNrLink = NULL) {
		$this->year = $year;
		$this->month = $month;
		$this->days = $days;
		$this->day_name_length = $day_name_length;
		$this->month_href = $month_href;
		$this->first_day = $first_day;
		$this->pn = $pn;
		$this->weekTotalCallback = $callback;
		$this->titleLinkMore = $titleLinkMore;
		$this->weekNrLink = $weekNrLink;
	}

	function generate_calendar() {
		$first_of_month = gmmktime(0, 0, 0, $this->month, 1, $this->year);
		//debug(date('Y-m-d H:i:s', $first_of_month));
		#remember that mktime will automatically correct if invalid dates are entered
		# for instance, mktime(0,0,0,12,32,1997) will be the date for Jan 1, 1998
		# this provides a built in "rounding" feature to generate_calendar()

		$day_names = []; #generate all the day names according to the current locale
		for ($n = 0, $t = (3 + $this->first_day) * 86400; $n < 7; $n++, $t += 86400) { #January 4, 1970 was a Sunday
			$day_names[$n] = ucfirst(gmstrftime('%A', $t)); #%A means full textual day name
		}

		list($month, $year, $month_name, $weekday) = explode(',', gmstrftime('%m,%Y,%B,%w', $first_of_month));
		$weekday = ($weekday + 7 - $this->first_day) % 7; #adjust for $first_day
		$title = htmlentities(ucfirst($month_name)) . '&nbsp;' . $year;  #note that some locales don't capitalize month and day names

		#Begin calendar. Uses a real <caption>. See http://diveintomark.org/archives/2002/07/03
		@list($p, $pl) = each($this->pn);
		@list($n, $nl) = each($this->pn); #previous and next links, if applicable
		if ($p) {
			$p = '<span class="calendar-prev">' . ($pl ? '<a href="' . htmlspecialchars($pl) . '">' . $p . '</a>' : $p) . '</span>&nbsp;';
		}
		if ($n) $n = '&nbsp;<span class="calendar-next">' . ($nl ? '<a href="' . htmlspecialchars($nl) . '">' . $n . '</a>' : $n) . '</span>';
		$monthLink = $this->month_href
				? '<a href="' . htmlspecialchars($this->month_href) . '" ' . $this->titleLinkMore . '>' . $title . '</a>'
				: $title;
		$calendar = '<table class="calendar">' . "\n" .
				'<tr><th colspan="8" class="calendar-month">'
				. $p
				. $monthLink
				. $n
				. "</th></tr>\n<tr>";

		if ($this->day_name_length) { #if the day names should be shown ($day_name_length > 0)
			#if day_name_length is >3, the full name of the day will be printed
			$calendar .= '<th class="weeknumber">#</td>';
			foreach ($day_names as $d) {
				$dayName = $this->day_name_length < 4
						? substr($d, 0, $this->day_name_length)
						: $d;
				$calendar .= '<th abbr="' . htmlentities($d) . '">' . htmlentities($dayName) . '</th>';
			}
			//		$calendar .= '<th class="weektotal">T.</td>';
			$calendar .= "</tr>\n<tr>";
		}

		$weekNr = $this->getWeekNr($first_of_month);
		$calendar .= '<td class="weeknumber">' . $weekNr . '</td>';
		if ($weekday > 0) $calendar .= '<td colspan="' . $weekday . '" class="empty">&nbsp;</td>'; #initial 'empty' days
		for ($day = 1, 
			 $days_in_month = gmdate('t', $first_of_month);
			 $day <= $days_in_month; $day++, $weekday++) {
			if ($weekday == 7) {
				$weekday = 0; #start a new week
				$today = strtotime('+' . ($day - 1) . ' days', $first_of_month);
				if ($this->weekTotalCallback) {
					$calendar .= '<td class="weektotal">'.call_user_func($this->weekTotalCallback, $today).'</td>';
				} else {
					//$calendar .= '<td class="weektotal"></td>';
				}
				$calendar .= "</tr>\n<tr>";
				$weekNr = $this->getWeekNr($today);
				$calendar .= '<td class="weeknumber">' . $weekNr . '</td>';
			}
			//$weekend = ($weekday == 5 || $weekday == 6) ? 'class="weekend"' : '';
			$weekend = '';
			if (isset($this->days[$day]) and is_array($this->days[$day])) {
				@list($link, $classes, $content, $linkClass) = $this->days[$day];
				if (is_null($content)) $content = $day;
				$calendar .= '<td' .
					($classes
						? ' class="' . htmlspecialchars($classes) . '"'
						: ' ' . $weekend) . '>' .
					($link
						? '<a 
							href="' . htmlspecialchars($link) . '" 
							class="'.$linkClass.'">' . $content . '</a>'
						: $content) . '</td>';
			} else {
				$calendar .= "<td {$weekend}>$day</td>";
			}
		}
		if ($weekday != 7) {
			$calendar .= '<td colspan="' . (7 - $weekday) . '" class="empty">&nbsp;</td>';
		} #remaining "empty" days
		/*	if ($callback) {
				$today = strtotime('+'.($day).' days', $first_of_month);
				$calendar .= '<td class="weektotal">'.call_user_func($callback, $today).'</td>';
			} else {
				$calendar .= '<td class="weektotal"></td>';
			}
		*/
		return $calendar . "</tr>\n</table>\n";
	}

	function getWeekNr($today) {
		$weekNr = date('W', $today);
		if ($this->weekNrLink) {
			$weekNrLink = str_replace('|', $weekNr, $this->weekNrLink);
			$weekNrLink = str_replace('%7C', $weekNr, $weekNrLink);
		} else {
			$weekNrLink = $weekNr;
		}
		return $weekNrLink;
	}

}
