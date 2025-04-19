<?php

use App\Controller\BaseController;

class AppController4Test extends BaseController
{

	/**
     * @var \TestConfig
     */
    public $config;

    public function __construct()
	{
		$this->config = TestConfig::getInstance();
	}

}
