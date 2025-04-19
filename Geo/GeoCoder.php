<?php

class GeoCoder
{

	public function query($address): mixed
	{
		$url = 'https://maps.googleapis.com/maps/api/geocode/json?&address=' . urlencode($address);
		$json = file_get_contents($url);
		return json_decode($json);
	}

	public function queryFirst($address)
	{
		$json = $this->query($address);
//		debug($json);
		if ($json->status == 'OK') {
			return $json->results[0];
		}

		return null;
	}

}
