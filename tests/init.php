<?php

echo basename(getcwd()), "\n";
if (basename(getcwd()) == 'tests') {
}

require_once __DIR__.'/../init.php';

require_once __DIR__.'/../class.ConfigBase.php';
//require_once 'TestConfig.php';
//class_alias('TestConfig', 'Config');

require_once __DIR__.'/../class.InitNADLIB.php';
$n = new InitNADLIB();
$n->init();

