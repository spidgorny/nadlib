<?php

trait HTMLHelper {

	function script($file) {
		$mtime = filemtime($file);
		$file .= '?'.$mtime;
		return '<script src="'.$file.'" type="text/javascript"></script>';
	}

}
