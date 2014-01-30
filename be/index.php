<?php

require_once '../init.php';

$n = new InitNADLIB();
$n->al->debug = false;
$n->init();

$i = Index::getInstance(true);
$i->initController();
echo $i->render();
