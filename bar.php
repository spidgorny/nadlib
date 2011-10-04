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

function drawRating($rating) {
   $width = isset($_GET['width']) ? $_GET['width'] : 100;
   $height = isset($_GET['height']) ? $_GET['height'] : 15;
   $ratingbar = (($rating/100)*$width)-2;
   $image = imagecreate($width,$height);
   $fill = ImageColorAllocate($image,0x43,0xB6,0xDF); #43B6DF
   //if ($rating > 49) { $fill = ImageColorAllocate($image,255,255,0); }
   //if ($rating > 74) { $fill = ImageColorAllocate($image,255,128,0); }
   //if ($rating > 89) { $fill = ImageColorAllocate($image,255,0,0); }
   $back = ImageColorAllocate($image,255,255,255);
   $border = ImageColorAllocate($image,127,127,127);
   ImageFilledRectangle($image,0,0,$width-1,$height-1,$back);
   ImageFilledRectangle($image,1,1,$ratingbar,$height-1,$fill);
   ImageRectangle($image,0,0,$width-1,$height-1,$border);
   imagePNG($image);
   imagedestroy($image);
}
Header("Content-type: image/png");
$expires = 60*60*24*365;
header("Pragma: public");
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
drawRating($_GET['rating']);
?>