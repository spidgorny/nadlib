<?php

class SVGChart {

	protected $width;
	protected $height;
	protected $data = array();

	function __construct($width = '100%', $height = '50', array $data = array()) {
		$this->width = $width;
		$this->height = $height;
		$this->setData($data);
		$this->id = uniqid();
	}

	function setData(array $data) {
		$this->data = $data;
	}

	function render() {
		$content = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="'.$this->width.'" height="'.$this->height.'">'."\n";
		if (sizeof($this->data)) {
			$max = max($this->data);
			$width = max(2, round($this->width / sizeof($this->data)));
			$labels = array_keys($this->data);
			$every = 0;
			$data = array_values($this->data);
			$often = ceil(sizeof($data) / 4);
			$text_size = 13;
			foreach ($data as $i => $height) {
				$x = $i * $width;
				$height = round(($this->height-$text_size) * $height / $max);
				$content .= '<rect
					id="rect_'.$this->id.'_'.$i.'"
					x="'.$x.'"
					y="'.($this->height - max(1, $height) - $text_size).'"
					width="'.($width-1).'"
					height="'.max(1, $height).'"
					style="fill:#a9d1df;stroke-width:0;stroke:rgb(0,0,0)" />'."\n";
				if (!($every++ % $often)) {
					$content .= '<text
						x="'.($x).'" y="'.($this->height-5).'"
						font-size="'.($text_size-3).'">'.$labels[$i].'</text>'."\n";
				}
			}
			foreach ($data as $i => $height) {
				$x = $i * $width;
				$height = round(($this->height-20) * $height / $max);
				$content .= '<text id="thepopup"
					x="'.$x.'"
					y="'.($this->height - max(1, $height) - 7).'"
					fill="black" visibility="hidden">'.$height.'
					<set attributeName="visibility" from="hidden" to="visible"
					begin="rect_'.$this->id.'_'.$i.'.mouseover" end="rect_'.$this->id.'_'.$i.'.mouseout" />
				</text>';
			}
		}
		$content .= '</svg>';
		return $content;
	}

	function __toString() {
		return $this->render().'';
	}

}
