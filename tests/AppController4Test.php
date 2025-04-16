<?php

use App\Controller\BaseController;

class AppController4Test extends BaseController
{

	public function __construct()
	{
		$this->config = TestConfig::getInstance();
		parent::__construct();
	}

}
