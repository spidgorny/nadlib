<?php

class Color {

	var $r, $g, $b;

	function __construct($init) {
		if ($init[0] == '#') {
			$colourstr = str_replace('#', '', $init);
			$rhex = substr($colourstr,0,2);
			$ghex = substr($colourstr,2,2);
			$bhex = substr($colourstr,4,2);

			$this->r = hexdec($rhex);
			$this->g = hexdec($ghex);
			$this->b = hexdec($bhex);
		} elseif (is_array($init) && sizeof($init) == 3) {
			if (gettype(first($init)) == 'integer') {
				list($this->r, $this->g, $this->b) = $init;
			} elseif (gettype(first($init)) == 'double') {
				throw new InvalidArgumentException('Please use static constructors for HSL and HSV');
			}
		} else {
			throw new InvalidArgumentException('Unable to understand color ['.$init.']');
		}
	}

	/**
	 * http://stackoverflow.com/questions/5199783/help-needed-with-php-function-brightness-making-rgb-colors-darker-brighter
	 * @param $colourstr
	 * @param $steps
	 * @return string
	 */
	function alter_brightness($colourstr, $steps) {
		$r = max(0,min(255, $this->r + $steps));
		$g = max(0,min(255, $this->g + $steps));
		$b = max(0,min(255, $this->b + $steps));

		return $this->getCSS(array($r, $g, $b));
	}

	function alter_color($deltaHue = 0, $deltaSaturation = 0, $deltaLightness = 0) {
		$hsv = $this->RGB_TO_HSV($this->r, $this->g, $this->b);
		$hsl = $this->hsv_to_hsl($hsv['H'], $hsv['S'], $hsv['V']);

		$hsl2[0] = $hsl[0] + $deltaHue/360;
		$hsl2[1] = $hsl[1] + $deltaSaturation/100;
		$hsl2[2] = $hsl[2] + $deltaLightness/100;

		$hsv2 = $this->hsl_to_hsv($hsl2[0], $hsl2[1], $hsl2[2]);

		$rgb = $this->HSVtoRGB($hsv2);
		nodebug(array(
			'rgb' => array($this->r, $this->g, $this->b),
			'hsv' => $hsv,
			'hsl' => $hsl,
			'hsl2' => $hsl2,
			'hsv2' => $hsv2,
			'rgb2' => $rgb));

		return $this->getCSS($rgb);
	}

	function getCSS($rgb) {
		$rgb = array_values($rgb);
		return '#'.dechex($rgb[0]).dechex($rgb[1]).dechex($rgb[2]);
	}

	/**
	 * http://stackoverflow.com/questions/1773698/rgb-to-hsv-in-php
	 * @param $R
	 * @param $G
	 * @param $B
	 * @return array
	 */
	function RGB_TO_HSV ($R, $G, $B)  // RGB Values:Number 0-255
	{                                 // HSV Results:Number 0-1
		$HSL = array();

		$var_R = ($R / 255);
		$var_G = ($G / 255);
		$var_B = ($B / 255);

		$var_Min = min($var_R, $var_G, $var_B);
		$var_Max = max($var_R, $var_G, $var_B);
		$del_Max = $var_Max - $var_Min;

		$V = $var_Max;

		if ($del_Max == 0)
		{
			$H = 0;
			$S = 0;
		}
		else
		{
			$S = $del_Max / $var_Max;

			$del_R = ( ( ( $var_Max - $var_R ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
			$del_G = ( ( ( $var_Max - $var_G ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
			$del_B = ( ( ( $var_Max - $var_B ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;

			if      ($var_R == $var_Max) $H = $del_B - $del_G;
			else if ($var_G == $var_Max) $H = ( 1 / 3 ) + $del_R - $del_B;
			else if ($var_B == $var_Max) $H = ( 2 / 3 ) + $del_G - $del_R;

			if ($H<0) $H++;
			if ($H>1) $H--;
		}

		$HSL['H'] = $H;
		$HSL['S'] = $S;
		$HSL['V'] = $V;

		return $HSL;
	}

	/**
	 * http://stackoverflow.com/questions/3597417/php-hsv-to-rgb-formula-comprehension
	 * @param array $hsv
	 * @return array
	 */
	function HSVtoRGB(array $hsv) {
		list($H,$S,$V) = $hsv;
		//1
		$H *= 6;
		//2
		$I = floor($H);
		$F = $H - $I;
		//3
		$M = $V * (1 - $S);
		$N = $V * (1 - $S * $F);
		$K = $V * (1 - $S * (1 - $F));
		//4
		switch ($I) {
			case 0:
				list($R,$G,$B) = array($V,$K,$M);
				break;
			case 1:
				list($R,$G,$B) = array($N,$V,$M);
				break;
			case 2:
				list($R,$G,$B) = array($M,$V,$K);
				break;
			case 3:
				list($R,$G,$B) = array($M,$N,$V);
				break;
			case 4:
				list($R,$G,$B) = array($K,$M,$V);
				break;
			case 5:
			case 6: //for when $H=1 is given
				list($R,$G,$B) = array($V,$M,$N);
				break;
		}
		return array($R*255, $G*255, $B*255);
	}

	/**
	 * http://ariya.blogspot.de/2008/07/converting-between-hsl-and-hsv.html
	 * @param $h
	 * @param $s
	 * @param $v
	 * @return array
	 */
	function hsv_to_hsl($h, $s, $v) {
		$hh = $h;
		$ll = (2 - $s) * $v;
		$ss = $s * $v;
		$ss /= ($ll <= 1) ? ($ll) : 2 - ($ll);
		$ll /= 2;
		return array($hh, $ss, $ll);
	}

	function hsl_to_hsv($hh, $ss, $ll) {
		$h = $hh;
		$ll *= 2;
		$ss *= ($ll <= 1) ? $ll : 2 - $ll;
		$v = ($ll + $ss) / 2;
		$s = (2 * $ss) / ($ll + $ss);
		return array($h, $s, $v);
	}

}
