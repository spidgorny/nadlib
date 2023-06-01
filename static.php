<?php

// this file is used to help phpstan understand some undefined constants

define('BR', "<br />\n");
define('TAB', "\t");
define('DEVELOPMENT', true);
if (!defined("LOG")) {
	define("LOG", 1);
}
if (!defined("INFO")) {
	define("INFO", 2);
}
if (!defined("WARN")) {
	define("WARN", 3);
}
if (!defined("ERROR")) {
	define("ERROR", 4);
}

define("NL", "\r\n");
