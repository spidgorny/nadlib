<?php

chdir('..');

require_once 'init.php';

require_once 'class.ConfigBase.php';
require_once 'TestConfig.php';
class_alias('TestConfig', 'Config');


$n = new InitNADLIB();
$n->init();

