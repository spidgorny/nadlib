<?php

class Proton
{

	public $base = 'http://photon.komoot.de/api/?q=';

	public function get($q): mixed
	{
		$gz = new GuzzleHttp\Client();
		$response = $gz->get($this->base . urlencode($q));
		$json = $response->getBody()->getContents();
		return json_decode($json);
	}

	/**
     * @return list<non-falsy-string>
     */
    public function getCities($q): array
	{
		$set = [];
		$results = $this->get($q);
		foreach ($results->features as $place) {
			if (ifsetor($place->properties->name)) {
				$set[] = $place->properties->name . ' (' . $place->properties->country . ')';
			} elseif (ifsetor($place->properties->city)) {
				$set[] = $place->properties->city . ' (' . $place->properties->country . ')';
			} else {
				debug($place->properties);
			}
		}

		return $set;
	}

	public function getFirst($location)
	{
		$results = $this->get($location);
		return $results->features ? first($results->features) : null;
	}

}
