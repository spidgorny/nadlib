<?php

require_once '../init.php';

$i = Index::getInstance();
$i->initController();
echo $i->render();
