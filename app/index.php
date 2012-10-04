<?php

require_once 'nadlib/init.php';

$i = new Index();
$i->initController();
echo $i->render();
