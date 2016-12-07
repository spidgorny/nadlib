<?php

require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/TestConfig.php';
//class_alias(TestConfig::class, 'Config');
Config::getInstance()->postInit();
