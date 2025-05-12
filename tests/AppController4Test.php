<?php

use AppDev\DCI\AppController;

class AppController4Test extends AppController
{

	public $config;

	public $linker;

	public $request = null;

	public function __construct()
	{
		$this->config = TestConfig::getInstance();
		$this->linker = new Linker('ControllerTest', Request::getInstance());
	}

}
