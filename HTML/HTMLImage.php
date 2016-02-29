<?php

class HTMLImage extends HTMLTag {

	/**
	 * @var string
	 */
	var $filename;

	function __construct($filename) {
		parent::__construct('img');
		$this->filename = $filename;
	}

	function getTag() {
		return $this->render();
	}

	function render() {
		$this->setAttr('src', $this->getImageLink());
		//debug($this->attr);
		return parent::render();
	}

	function getImageLink() {
		if ($this->isLocalFile()) {
			if ($this->filename[0] == '/') {
				$documentRoot = Autoload::getInstance()->appRoot;
				$documentRoot = str_replace('\\', '/', $documentRoot);
				$realpath = realpath($this->filename);
				$realpath = str_replace('\\', '/', $realpath);
				$fileLink = str_replace($documentRoot, '', $realpath);
				//debug($documentRoot, $realpath, $fileLink);
			} else {
				$fileLink = $this->filename;
			}
		} else {
			$fileLink = $this->filename;
		}
		return $fileLink;
	}

	function isLocalFile() {
		return !contains($this->filename, '://');
	}

	function getBaseName() {
		return basename($this->filename);
	}

	function getExif() {
		$exif = NULL;
		if (file_exists($this->filename)
			&& str_endsWith($this->filename, '.jpeg')
			&& function_exists('exif_read_data')
		) {
			$exif = exif_read_data($this->filename); // warning if PNG
		}
		return $exif;
	}

	function getLatLon() {
		$lat = $lon = NULL;
		$exif = $this->getExif();
		if ($exif && $exif["GPSLatitude"]) {
			$lat = $this->getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
			$lon = $this->getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
		}
		return [$lat, $lon];
	}

	function getGps($exifCoord, $hemi) {
		$degrees = count($exifCoord) > 0 ? $this->gps2Num($exifCoord[0]) : 0;
		$minutes = count($exifCoord) > 1 ? $this->gps2Num($exifCoord[1]) : 0;
		$seconds = count($exifCoord) > 2 ? $this->gps2Num($exifCoord[2]) : 0;

		$flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

		return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
	}

	function gps2Num($coordPart) {
		$parts = explode('/', $coordPart);

		if (count($parts) <= 0)
			return 0;

		if (count($parts) == 1)
			return $parts[0];

		return floatval($parts[0]) / floatval($parts[1]);
	}

	function hasLatLon() {
		list($lat, $lon) = $this->getLatLon();
		return $lat || $lon;
	}

	function getMiniMap() {
		return '<div class="MiniMap">
			<img src="" />
		</div>';
	}

	function exists() {
		return file_exists($this->filename);
	}

	public function getBaseNameWithCorrectExtension() {
		$basename = $this->getBaseName();
		$mimeExt = $this->getMimeExt();
		if ($mimeExt) {
			$oldExt = pathinfo($this->filename, PATHINFO_EXTENSION);
			$correct = str_replace('.' . $oldExt, '.' . $mimeExt, $basename);
		} else {
			$correct = $basename;
		}
		return $correct;
	}

	function getMimeExt() {
		$newExt = NULL;
		$u = new Uploader();
		$mime = $u->get_mime_type($this->filename);
		//debug($mime);
		if ($mime) {
			$map = [
				'image/jpeg' => 'jpeg',
				'image/gif' => 'gif',
				'image/png' => 'png',
				'image/tiff' => 'tiff',
			];
			$newExt = ifsetor($map[$mime]);
		}
		return $newExt;
	}

}
