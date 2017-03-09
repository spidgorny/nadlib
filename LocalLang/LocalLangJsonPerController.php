<?php

/**
 * Class LocalLangTest
 * It's reading data from the file corresponding to the controller
 */
class LocalLangJsonPerController extends LocalLangJson {

	/**
	 * @var string
	 */
	var $controller;

	/**
	 * @var LocalLangJson
	 */
	var $general;

	function __construct($langFolder, $controller, LocalLang $general = NULL) {
		parent::__construct($langFolder);
		$this->controller = $controller;
		$this->general = $general ?: new LocalLangJson($this->langFolder);
		//debug($this->lang);
	}

	function setController($class) {
		//debug(__METHOD__, $class);
		$this->controller = $class;
		$this->detectLang();
		$this->readDB();
	}

	function readDB() {
		$file = $this->getFilename();
		if (is_file($file)) {
			$file = file_get_contents($file);
			$this->ll = json_decode($file, true);
		}
	}

	function __destruct() {
		$jsonEncode = json_encode($this->ll, JSON_PRETTY_PRINT);
		$file = $this->getFilename();
		if (!is_file($file) || (filesize($file) < mb_strlen($jsonEncode))) {
			file_put_contents($file, $jsonEncode);
		}
	}

	/**
	 * @return string
	 */
	function getFilename() {
		return $this->langFolder . $this->controller . '-' . $this->lang . '.json';
	}

	function T($text, $replace = NULL, $s2 = NULL, $s3 = NULL) {
		if (isset($this->ll[$text])) {
			return parent::T($text, $replace, $s2, $s3);
		} elseif (isset($this->general->ll[$text])) {
			return $this->general->T($text, $replace, $s2, $s3);
		} else {
			$this->saveMissingMessage($text);
			return $this->Tp($text, $replace, $s2, $s3);
		}
	}

}
