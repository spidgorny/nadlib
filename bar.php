<?php

// PHP Server Info - A PHP Server Information Script
// http://freewebs.com/phpstatus/
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// Mod by Slawa.

require_once 'init.php';

class BarImage {

	var $expires = 31536000; //60*60*24*365;	// days

	var $width;

	var $height;

	var $color;

	var $backColor;

	var $symmetric = false;

	function __construct() {
		$this->width = isset($_GET['width']) ? $_GET['width'] : 100;
		$this->height = isset($_GET['height']) ? $_GET['height'] : 15;
		$color = isset($_GET['color']) ? $_GET['color'] : NULL;
		$this->color = $color ? $this->html2rgb($color) : array(0x43, 0xB6, 0xDF); #43B6DF
		$bg = isset($_GET['bg']) ? $_GET['bg'] : NULL;
		$this->backColor = $bg ? $this->html2rgb($bg) : array(0xFF, 0xFF, 0xFF);
		$this->symmetric = ifsetor($_REQUEST['symmetric']);
	}

	function setHeaders() {
		error_reporting(E_ALL);
		//ini_set('display_errors', false);
		header("Content-Type: image/png");
		header("Pragma: public");
		header("Cache-Control: maxage=".$this->expires);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$this->expires) . ' GMT');
	}

	function drawRating($rating) {
		$ratingbar = ($rating / 100) * ($this->width - 5);
		$barDX = 2;
		$image = imagecreate($this->width, $this->height);

		$backColor = $this->backColor;
		$back = imagecolorallocate($image, $backColor[0], $backColor[1], $backColor[2]);
		$border = imagecolorallocate($image, 127, 127, 127);
		imagefilledrectangle($image, 0, 0, $this->width - 1, $this->height - 1, $back);

		if (!ifsetor($_GET['!border'])) {
			imagerectangle($image, 0, 0, $this->width - 1, $this->height - 1, $border);
		} else {
			$ratingbar += 2;
			$barDX = 0;
		}

		$color = $this->color;
		$fill = imagecolorallocate($image, $color[0], $color[1], $color[2]);
		//if ($rating > 49) { $fill = ImageColorAllocate($image,255,255,0); }
		//if ($rating > 74) { $fill = ImageColorAllocate($image,255,128,0); }
		//if ($rating > 89) { $fill = ImageColorAllocate($image,255,0,0); }
		if ($this->symmetric) {
			$middle = $this->width / 2;
			imagefilledrectangle($image, $middle + $barDX, $barDX, $middle + $barDX + $ratingbar/2, $this->height - $barDX - 1, $fill);
		} else {
			imagefilledrectangle($image, $barDX, $barDX, $barDX + $ratingbar, $this->height - $barDX - 1, $fill);
		}
		imagepng($image);
		imagedestroy($image);
	}

	function html2rgb($color) {
		if ($color[0] == '#')
			$color = substr($color, 1);

		if (strlen($color) == 6)
			list($r, $g, $b) = array($color[0] . $color[1],
					$color[2] . $color[3],
					$color[4] . $color[5]);
		elseif (strlen($color) == 3)
			list($r, $g, $b) = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
		else
			return false;

		$r = hexdec($r);
		$g = hexdec($g);
		$b = hexdec($b);

		return array($r, $g, $b);
	}

}

if (!function_exists('imagecreate')) {
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	echo 'PHP: '.phpversion().'<br />';
	echo 'GD not installed';
} else {
	$bar = new BarImage();
	$bar->setHeaders();
	$bar->drawRating(min(100, ifsetor($_GET['rating'])));
}
