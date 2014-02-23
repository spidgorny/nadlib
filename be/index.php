<?php

require_once '../vendor/autoload.php';
require_once '../../../../vendor/autoload.php';
require_once '../init.php';

$n = new InitNADLIB();
$n->al->debug = false;
$n->init();

require_once 'class/class.IndexBE.php';	// force this Index class
$i = Index::getInstance(true);
$i->initController();
echo $i->render();
AutoLoad::getInstance()->__destruct();
