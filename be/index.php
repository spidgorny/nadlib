<?php

require_once '../init.php';

$i = IndexBE::getInstance();
$i->initController();
echo $i->render();
