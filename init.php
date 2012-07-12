<?php

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', TRUE);
header('Cache-Control: max-age=0');
header('Expires: Tue, 19 Oct 2010 13:24:46 GMT');

if ($_COOKIE['debug']) {
	define('DEVELOPMENT', true);
}

// remove cookies from $_REQUEST
//debug($_COOKIE);
foreach ($_COOKIE as $key => $_) {
	if ($_GET[$key]) {
		$_REQUEST[$key] = $_GET[$key];
	} else if ($_POST[$key]) {
		$_REQUEST[$key] = $_POST[$key];
	} else {
		unset($_REQUEST[$key]);
	}
}

function __autoload($class) {
	if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
	$folders = array(
		'../class',
		'../nadlib',
		'../model',
		'../model/Tag',
		'../../class',
	);
	foreach ($folders as $path) {
		$file = dirname(__FILE__).DIRECTORY_SEPARATOR.$path.'/class.'.$class.'.php';
		//debug($file, file_exists($file));
		if (file_exists($file)) {
			include_once($file);
			break;
		}
	}
	if (!class_exists($class)) throw new Exception('Class '.$class.' not found.');
	if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
}

function debug($a) {
	print('<pre style="background-color: #EEEEEE; border: dotted 1ps silver; width: auto; '.$preMore.'">');
	if ($name) {
		print('<div style="background-color: #888888; color: white;">'.$name.'</div>');
	}
	$output = var_export(func_num_args() > 1 ? func_get_args() : $a, TRUE);
	$output = str_replace("\n(", " (", $output);
	$output = str_replace("\n        (", " (", $output);
	$output = str_replace(")\n", ")", $output);
	print htmlspecialchars($output);
	print('<div style="background-color: #888888; color: white;">');
		debug_print_backtrace();
	print('</div>');
	print('</pre>');
}

function nodebug() {
}

function getDebug($a, $b = NULL, $c = '') {
	ob_start();
	debug($a, $b, $c);
	return ob_get_clean();
}

function trimExplode($sep, $str) {
	$parts = explode($sep, $str);
	$parts = array_map('trim', $parts);
	$parts = array_filter($parts);
	$parts = array_values($parts);
	return $parts;
}

function debug_pre_print_backtrace() {
	print '<pre>';
	debug_print_backtrace();
	print '</pre>';
}
