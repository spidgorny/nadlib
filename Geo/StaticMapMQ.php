<?php

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-03-22
 * Time: 17:38
 *
 * Warning: This service requires an API key.
 */
class StaticMapMQ {

	var $key;

	var $secret;

	var $location;

	var $lat;

	var $lon;

	var $size = '640x480';

	function __construct($key, $secret, $location = '', $lat = NULL, $lon = NULL) {
		$this->key = $key;
		$this->secret = $secret;
		$this->location = $location;
		$this->lat = $lat;
		$this->lon = $lon;
	}

	function render() {
		list($width, $height) = trimExplode('x', $this->size);
		$params = [
			'key'    => $this->key,
			'size'   => $width.','.$height,
			'center' => $this->location,
		];
		$src = 'https://beta.mapquestapi.com/staticmap/v5/map?'.http_build_query($params);
		$html = new HTML();
		$img = $html->img($src, [
			'width'  => $width,
			'height' => $height,
		]);
		return $img;
	}

}
