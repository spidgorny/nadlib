<?php

require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/TestConfig.php';
require_once __DIR__ . '/../vendor/autoload.php';

class_alias(TestConfig::class, 'Config');
Config::getInstance()->postInit();
require_once __DIR__.'/AppController4Test.php';

function __($a) { return $a; }

echo basename(__DIR__).'/'.basename(__FILE__), ' done', BR;
