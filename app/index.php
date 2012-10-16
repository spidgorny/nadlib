<?php

//define('NADLIB', dirname(__FILE__).'/..');
require_once '../init.php';

$i = Index::getInstance();
$i->initController();
echo $i->render();
