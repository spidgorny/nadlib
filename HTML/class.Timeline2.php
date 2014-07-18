<?php

class Timeline2 /*extends AppController */{

	/**
	 * @var Date
	 */
	var $start;

	/**
	 * @var Date
	 */
	var $end;

	var $width;

	var $height;

	var $fillBottomColor = '#EAEAEA';

	var $fillTopColor = '#DADADA';

	var $rangeColor = '#0088CC';

	var $textColor = 'rgb(100,100,100)';

	var $rangeContent = array();

	var $height_10;
	var $height_20;
	var $height_30;
	var $fontSize;
	var $dayWidth;

	function __construct($width, $height, Date $start, Date $end) {
		$this->width = $width;
		$this->height = $height;
		$this->start = $start;
		$this->end = $end;
		$this->duration = $this->end->minus($this->start);
		$this->height_10 = round($this->height / 3, 2);
		$this->height_20 = round($this->height / 3 * 2, 2);
		$this->height_30 = round($this->height, 2);
		$this->fontSize = round($this->height / 4.0, 2);
		$nextDay = clone $this->start;
		$nextDay = $nextDay->math('+1 day');
		$this->dayWidth = $this->date2x($nextDay);
	}

	function date2x(Date $date) {
		$sinceStart = $date->minus($this->start);
		//$tillEnd = $this->end->minus($date);
		$percent = $sinceStart->getTimestamp() / ($this->duration->getTimestamp());
		$rc = $this->duration->getTimestamp() / 60 / 60 / 24;
		$percent = round($percent * $rc) / $rc;
		//debug($date->getTimestamp(), $this->start->getTimestamp(), $sinceStart->getTimestamp(), $this->duration->getTimestamp(), $percent);
		return round($percent * $this->width, 2);
	}

	function render() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if ($this->duration->getTimestamp()) {
			$content = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="'.$this->width.'" height="'.$this->height.'">'."\n";
			// fill 100%
			$content .= '<rect
				x="'.(0).'"
				y="'.(0).'"
				width="'.$this->width.'"
				height="'.$this->height_30.'"
				style="fill:'.$this->fillBottomColor.';stroke-width:0" />'."\n";

			$content .= $this->hourTicks();
			$content .= $this->dateTicks();
			$content .= $this->weekTicks();
			$content .= $this->monthTicks();
			$content .= $this->yearLabels();

			// fill top background
			$content .= '<rect
				x="'.(0).'"
				y="'.(0).'"
				width="'.$this->width.'"
				height="'.($this->height - $this->height_20).'"
				style="fill:'.$this->fillTopColor.'" />'."\n";

			$content .= implode("\n", $this->rangeContent);
			$content .= '</svg>';
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $content;
	}

	function hourTicks() {
		$content = '';
		$every = $this->dayWidth / 24 / 3 / $this->fontSize; // 3 chars for "22h"
		$i = 0;
		/* @var $date Time */
		for ($date = new Time($this->start);
		     $date->earlier($this->end);
		     $date->add(new Duration('1 hour'))) {
			$x = $this->date2xTime($date);
			if ($this->dayWidth > 24 * 2) {	// 24h * 2 pixels
				$content .= '<line x1="'.$x.'" y1="'.($this->height_10).'" x2="'.$x.'"
					y2="'.($this-> height_10 + $this->height_10/2).'"
					style="stroke:'.$this->textColor.';stroke-width:1"/>';
			}
			// if enough space for dates
			//if ($this->dayWidth > (48 * 3 * $this->fontSize*1.5)) {
			if ($every > 1 && !($i++ % $every)) {
				$content .= '<text
					x="'.($x-($this->fontSize/1.5/2)).'"
					y="'.($this->height_10 + $this->fontSize*1.3).'"
					fill="'.$this->textColor.'"
					font-size="'.($this->fontSize/1.5).'"
					>'.$date->format('H\h').'</text>';
			}
		}
		return $content;
	}

	function dateTicks() {
		$content = '';
		for ($date = clone $this->start/* @var $date Date */;
			 $date->earlier($this->end);
			 $date->add(new Duration('1 day'))) {
			$x = $this->date2x($date);
			if ($this->dayWidth > 2) {	// px
				$content .= '<line x1="'.$x.'" y1="'.($this->height_10).'" x2="'.$x.'"
					y2="'.($this-> height_10 + $this->height_10/2).'"
					style="stroke:'.$this->textColor.';stroke-width:1"/>';
			}
			// if enough space for dates
			if ($this->dayWidth > ($this->fontSize*1.5)) {
				$content .= '<text
					x="'.($x+1).'"
					y="'.($this->height_10 + $this->fontSize*0.8).'"
					fill="'.$this->textColor.'"
					font-size="'.$this->fontSize.'"
					>'.$date->format('d').'</text>';
			}
		}
		return $content;
	}

	function weekTicks() {
		$content = '';
		for ($date = clone $this->start/* @var $date Date */;
			 $date->earlier($this->end);
			 $date->add(new Duration('1 week'))) {
			$x = $this->date2x($date);
			$content .= '<line x1="'.$x.'"
				y1="'.$this->height_10.'"
				x2="'.$x.'"
				y2="'.($this->height_20).'"
				style="stroke:'.$this->textColor.';stroke-width:1"/>';
			/*$content .= '<text
				x="'.($x+1).'"
				y="'.($height_20 + 13).'"
				fill="'.$this->textColor.'">'.$date->format('W').'</text>';*/
		}
		return $content;
	}

	function monthTicks() {
		$content = '';
		for ($date = clone $this->start/* @var $date Date */;
			 $date->earlier($this->end);
			 $date->add(new Duration('1 month'))) {
			$x = $this->date2x($date);
			$content .= '<line x1="'.($x+0).'" y1="'.$this->height_10.'"
				x2="'.($x+0).'" y2="'.($this->height_30).'"
				style="stroke:'.$this->textColor.';stroke-width:1"/>';
			if (($this->dayWidth * 30) > ($this->fontSize*3)) {	// 3 letters in Jan
				$content .= '<text
					x="'.($x+1).'"
					y="'.($this->height_20 + $this->fontSize*0.9).'"
					fill="'.$this->textColor.'"
					font-size="'.$this->fontSize.'"
					>'.$date->format('M').'</text>';
			}
		}
		return $content;
	}

	function yearLabels() {
		$content = '';
		//debug($dayWidth, ($dayWidth * 30), $fontSize, ($fontSize*3));
		if (($this->dayWidth * 30) < ($this->fontSize*3)) {	// 3 letters in Jan
			for ($date = clone $this->start/* @var $date Date */;
				 $date->earlier($this->end);
				 $date->add(new Duration('1 year'))) {
				$x = $this->date2x($date);
				$content .= '<text
						x="'.($x+1).'"
						y="'.($this->height_20 + $this->fontSize*0.9).'"
						fill="'.$this->textColor.'"
						font-size="'.$this->fontSize.'"
						>'.$date->format('Y').'</text>';

			}
		}
		return $content;
	}

	function renderRange(Date $from, Date $till) {
		$x = $this->date2x($from);
		$width = $this->date2x($till) - $x;
		$this->rangeContent[] = '<rect
				x="'.$x.'"
				y="'.(0).'"
				width="'.$width.'"
				height="'.($this->height - $this->height_20).'"
				style="fill:'.$this->rangeColor.'; stroke-width:0; stroke:rgb(0,0,0)" />';
	}

	function date2xTime(Time $time) {
		$sinceStart = $time->minus($this->start);
		//$tillEnd = $this->end->minus($date);
		$percent = $sinceStart->getTimestamp() / ($this->duration->getTimestamp());
		return round($percent * $this->width, 2);
	}

	function renderTimeRange(Time $from, Time $till,
	                         $style = 'fill: #0088CC; stroke-width:0; stroke:rgb(0,0,0)') {
		$x = $this->date2xTime($from);
		$width = $this->date2xTime($till) - $x;
		$id = uniqid('rect_');
		$this->rangeContent[] = '<rect
				id="'.$id.'"
				x="'.$x.'"
				y="'.(0).'"
				width="'.$width.'"
				height="'.($this->height - $this->height_20).'"
				style="'.$style.'"
				startTime="'.$from->getDateTime().'"
				endTime="'.$till->getDateTime().'"
				/>';
		return $id;
	}

}
