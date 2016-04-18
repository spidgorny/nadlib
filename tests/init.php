<?php

echo 'cwd: ', basename(getcwd()), "\n";
if (basename(getcwd()) == 'tests') {
}

require_once __DIR__.'/../init.php';

require_once __DIR__.'/../class.ConfigBase.php';
//require_once 'TestConfig.php';
//class_alias('TestConfig', 'Config');

$_COOKIE['debug'] = 1;

require_once __DIR__.'/../class.InitNADLIB.php';
$n = new InitNADLIB();
$n->init();

define('DS', DIRECTORY_SEPARATOR);
/** @noinspection PhpIncludeInspection */
$globalAutoload = getenv('USERPROFILE') . DS . 'AppData' . DS . 'Roaming' . DS . 'Composer' . DS . 'vendor' . DS . 'autoload.php';
echo $globalAutoload, BR;

/** @noinspection PhpIncludeInspection */
require_once $globalAutoload;
