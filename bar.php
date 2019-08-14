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

function drawRating($rating)
{
	$width = isset($_GET['width']) ? $_GET['width'] : 100;
	$height = isset($_GET['height']) ? $_GET['height'] : 15;
	$ratingbar = ($rating / 100) * ($width - 5);
	$barDX = 2;
	$image = imagecreate($width, $height);
	$color = $_GET['color'] ? html2rgb($_GET['color']) : array(0x43, 0xB6, 0xDF); #43B6DF
	$fill = ImageColorAllocate($image, $color[0], $color[1], $color[2]);
	//if ($rating > 49) { $fill = ImageColorAllocate($image,255,255,0); }
	//if ($rating > 74) { $fill = ImageColorAllocate($image,255,128,0); }
	//if ($rating > 89) { $fill = ImageColorAllocate($image,255,0,0); }

	$backColor = $_GET['bg'] ? html2rgb($_GET['bg']) : array(0xFF, 0xFF, 0xFF);
	$back = ImageColorAllocate($image, $backColor[0], $backColor[1], $backColor[2]);
	$border = ImageColorAllocate($image, 127, 127, 127);
	ImageFilledRectangle($image, 0, 0, $width - 1, $height - 1, $back);
	if (!$_GET['!border']) {
		ImageRectangle($image, 0, 0, $width - 1, $height - 1, $border);
	} else {
		$ratingbar += 2;
		$barDX = 0;
	}
	ImageFilledRectangle($image, $barDX, $barDX, $barDX + $ratingbar, $height - $barDX - 1, $fill);
	imagePNG($image);
	imagedestroy($image);
}

function html2rgb($color)
{
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

if (0 || !function_exists('imagecreate')) {
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	echo 'PHP: ' . phpversion() . '<br />';
	echo 'GD not installed';
} else {
	error_reporting(0);
	ini_set('display_errors', false);
	header("Content-type: image/png");
}
$expires = 60 * 60 * 24 * 365;        // days
header("Pragma: public");
header("Cache-Control: maxage=" . $expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
drawRating(min(100, intval($_GET['rating'])));
