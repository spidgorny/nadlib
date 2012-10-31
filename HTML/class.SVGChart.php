<?php

class SVGChart {

	protected $width;
	protected $height;
	protected $data = array();

	function __construct($width = '100%', $height = '50', array $data = array()) {
		$this->width = $width;
		$this->height = $height;
		$this->setData($data);
	}

	function setData(array $data) {
		$this->data = $data;
	}

	function render() {
		$content = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="'.$this->width.'" height="'.$this->height.'">'."\n";
		$width = round($this->width / sizeof($this->data));
		$max = max($this->data);
		$data = array_values($this->data);
		foreach ($data as $i => $height) {
			$x = $i * $width;
			$height = round($this->height * $height / $max);
  			$content .= '<rect
  				x="'.$x.'"
  				y="'.($this->height - $height).'"
  				width="'.$width.'"
  				height="'.$height.'"
  				style="fill:rgb(128,128,255);stroke-width:0;stroke:rgb(0,0,0)" />'."\n";
		}
		$content .= '</svg>';
		return $content;
	}

	function __toString() {
		return $this->render().'';
	}

}
