<?php

require_once 'nadlib/init.php';

$i = Index::getInstance();
$i->initController();
echo $i->render();
