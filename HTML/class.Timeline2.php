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

	function __construct($width, $height, Date $start, Date $end) {
		$this->width = $width;
		$this->height = $height;
		$this->start = $start;
		$this->end = $end;
		$this->duration = $this->end->minus($this->start);
	}

	function render(Date $from, Date $till) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if ($this->duration->getTimestamp()) {
			$content = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="'.$this->width.'" height="'.$this->height.'">'."\n";
			$height_10 = $this->height / 3;
			$height_20 = $this->height / 3 * 2;
			$height_30 = $this->height;
			$fontSize = $this->height / 4.0;

			$nextDay = clone $this->start;
			$nextDay = $nextDay->math('+1 day');
			$dayWidth = $this->date2x($nextDay);

			// fill 100%
			$content .= '<rect
				x="'.(0).'"
				y="'.(0).'"
				width="'.$this->width.'"
				height="'.$height_30.'"
				style="fill:'.$this->fillBottomColor.';stroke-width:0" />'."\n";

			// date ticks
			for ($date = clone $this->start/* @var $date Date */;
				 $date->earlier($this->end);
				 $date->add(new Duration('1 day'))) {
				$x = $this->date2x($date);
				if ($dayWidth > 2) {	// px
					$content .= '<line x1="'.$x.'" y1="'.($height_10).'" x2="'.$x.'" y2="'.($height_10+$height_10/2).'"
					style="stroke:'.$this->textColor.';stroke-width:1"/>';
				}
				// if enough space for dates
				if ($dayWidth > ($fontSize*1.5)) {
					$content .= '<text
					x="'.($x+1).'"
					y="'.($height_10 + $fontSize*0.8).'"
					fill="'.$this->textColor.'"
					font-size="'.$fontSize.'"
					>'.$date->format('d').'</text>';
				}
			}

			// week ticks
			for ($date = clone $this->start/* @var $date Date */;
				 $date->earlier($this->end);
				 $date->add(new Duration('1 week'))) {
				$x = $this->date2x($date);
				$content .= '<line x1="'.$x.'" y1="'.$height_10.'" x2="'.$x.'" y2="'.($height_20).'"
					style="stroke:'.$this->textColor.';stroke-width:1"/>';
				/*$content .= '<text
					x="'.($x+1).'"
					y="'.($height_20 + 13).'"
					fill="'.$this->textColor.'">'.$date->format('W').'</text>';*/
			}

			// month ticks and labels
			for ($date = clone $this->start/* @var $date Date */;
				 $date->earlier($this->end);
				 $date->add(new Duration('1 month'))) {
				$x = $this->date2x($date);
				$content .= '<line x1="'.($x+0).'" y1="'.$height_10.'" x2="'.($x+0).'" y2="'.($height_30).'"
					style="stroke:'.$this->textColor.';stroke-width:1"/>';
				if (($dayWidth * 30) > ($fontSize*3)) {	// 3 letters in Jan
					$content .= '<text
					x="'.($x+1).'"
					y="'.($height_20 + $fontSize*0.9).'"
					fill="'.$this->textColor.'"
					font-size="'.$fontSize.'"
					>'.$date->format('M').'</text>';
				}
			}

			// year labels
			//debug($dayWidth, ($dayWidth * 30), $fontSize, ($fontSize*3));
			if (($dayWidth * 30) < ($fontSize*3)) {	// 3 letters in Jan
				for ($date = clone $this->start/* @var $date Date */;
					 $date->earlier($this->end);
					 $date->add(new Duration('1 year'))) {
					$x = $this->date2x($date);
					$content .= '<text
						x="'.($x+1).'"
						y="'.($height_20 + $fontSize*0.9).'"
						fill="'.$this->textColor.'"
						font-size="'.$fontSize.'"
						>'.$date->format('Y').'</text>';

				}
			}

			// fill top background
			$content .= '<rect
				x="'.(0).'"
				y="'.(0).'"
				width="'.$this->width.'"
				height="'.($this->height-$height_20).'"
				style="fill:'.$this->fillTopColor.'" />'."\n";

			$x = $this->date2x($from);
			$width = $this->date2x($till) - $x;
			$content .= '<rect
				x="'.$x.'"
				y="'.(0).'"
				width="'.$width.'"
				height="'.($this->height-$height_20).'"
				style="fill:'.$this->rangeColor.'; stroke-width:0; stroke:rgb(0,0,0)" />'."\n";
			$content .= '</svg>';
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $content;
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

}
