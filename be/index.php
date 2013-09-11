<?php

require_once '../init.php';

$n = new InitNADLIB();
$n->init();

$i = IndexBE::getInstance();
$i->initController();
echo $i->render();
