<?php

/**
 * Class LocalLangTest
 * It's reading data from the file corresponding to the controller
 */
class LocalLangJsonPerController extends LocalLangJson
{

	/**
	 * @var string
	 */
	public $controller;

	/**
	 * @var LocalLangJson
	 */
	public $general;

	public function __construct($langFolder = null, $controller = null, ?LocalLangJson $general = null)
	{
		parent::__construct($langFolder);
		$this->controller = $controller;
		$this->general = $general ?: new LocalLangJson($this->langFolder);
		//debug($this->lang);
		if ($this->controller) {
			$this->detectLang();
			$this->readDB();
		}

		$this->log(__METHOD__, $this->controller);
		LocalLang::$instance = $this;
	}

	public function readDB(): void
	{
		$this->log(__METHOD__, $this->getFilename());
		$file = $this->getFilename();
		if (is_file($file)) {
			$file = file_get_contents($file);
			$this->ll = json_decode($file, true, 512, JSON_THROW_ON_ERROR);
			$this->log(__METHOD__, count($this->ll));
		}
	}

	public function getFilename(): string
	{
		return $this->langFolder . $this->controller . '-' . $this->lang . '.json';
	}

	public function setController($class): void
	{
		$this->log(__METHOD__, $class);
		//debug(__METHOD__, $class);
		$this->controller = $class;
		$this->detectLang();
		$this->readDB();
	}

	public function __destruct()
	{
		parent::__destruct();
		$jsonEncode = json_encode($this->ll, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
		$file = $this->getFilename();
		if (!is_file($file) || (filesize($file) < mb_strlen($jsonEncode))) {
			file_put_contents($file, $jsonEncode);
		}
	}

	public function T($text, $replace = null, $s2 = null, $s3 = null)
	{
		if (isset($this->ll[$text])) {
			return parent::T($text, $replace, $s2, $s3);
		}

		if (isset($this->general->ll[$text])) {
			return $this->general->T($text, $replace, $s2, $s3);
		}

		$this->saveMissingMessage($text);
		return self::Tp($text, $replace, $s2, $s3);
	}

}
