<?php

class ReplayHAR
{

	var $file;

	var $request;

	function __construct($file)
	{
		$this->file = $file;
	}

	function readHAR()
	{
		$har = json_decode(file_get_contents($this->file));
		$this->request = $har->log->entries[0]->request;
		return $this->request;
	}

	function getURL()
	{
		if (!$this->request) {
			$this->readHAR();
		}
		$url = new URL($this->request->url);
		$url->clearParams();
		$url->setParamsFromHAR($this->request->queryString);
		return $url;
	}

	function getURLGet()
	{
		$url = $this->getURL();
		$urlget = $url->getURLGet();
		$urlget->context['http']['method'] = $this->request->method;
		foreach ($this->request->headers as $pair) {
			$urlget->headers[$pair->name] = $pair->value;
		}
		return $urlget;
	}

}
