<?php

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-03-22
 * Time: 17:38
 */
class StaticMapOSM
{

	public $location;

	public $lat;

	public $lon;

	public $size = '640x480';

	function __construct($location, $lat = NULL, $lon = NULL)
	{
		$this->location = $location;
		$this->lat = $lat;
		$this->lon = $lon;
	}

	function render()
	{
		if ($this->lat && $this->lon) {
			$content = '<figure>
			<img src="http://staticmap.openstreetmap.de/staticmap.php?center=' . $this->lat . ',' . $this->lon . '&zoom=11&size=' . $this->size . '" />
			<figcaption>' . $this->location . '</figcaption>
		</figure>';
		} else {
			$content = '<figure>
			<img src="http://staticmap.openstreetmap.de/staticmap.php?center=' . $this->location . '&zoom=11&size=' . $this->size . '" />
			<figcaption>' . $this->location . '</figcaption>
		</figure>';
		}
		return $content;
	}

}
