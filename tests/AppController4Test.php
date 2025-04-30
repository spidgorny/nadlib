<?php

use App\Config;
use App\Controller\BaseController;

class AppController4Test extends BaseController
{

	public Config|TestConfig $config;

	public Linker $linker;

	public ?Request $request = null;

	public function __construct()
	{
		$this->config = TestConfig::getInstance();
		$this->linker = new Linker('ControllerTest', Request::getInstance());
	}

}
