<?php

/**
 * Class LocalLangTest
 * It's reading data from the class/ll-en.json file and writes new messages back to it
 */
class LocalLangJson extends LocalLangDummy {

	var $langFolder;

	function __construct($langFolder = 'class/') {
		parent::__construct();
		$this->langFolder = $langFolder;
	}

	function areThereTranslationsFor($lang) {
		$this->lang = $lang;	// temporary
		$file = $this->getFilename();
		$ok = is_file($file);
		//debug($lang, $file, $ok);
		return $ok;
	}

	function readDB() {
		$file = file_get_contents($this->getFilename());
		$this->ll = json_decode($file, true);
		//debug($file, $this->ll);
	}

	function updateMessage(array $data) {
		$this->ll[$data['code']] = $data['text'];
	}

	function __destruct() {
		$jsonEncode = json_encode($this->ll, JSON_PRETTY_PRINT);
		$file = $this->getFilename();
		if (filesize($file) < mb_strlen($jsonEncode)) {
			file_put_contents($file, $jsonEncode);
		}
	}

	function saveMissingMessage($text) {
		$this->updateMessage([
			//'code' => RandomStringGenerator::likeYouTube(),
			'code' => $text,
			'text' => $text,
		]);
	}

	function getEditLinkMaybe($text, $id = NULL, $class = 'untranslatedMessage') {
		if ($this->indicateUntranslated) {
			$trans = new htmlString('<span class="untranslatedMessage">['.htmlspecialchars($text).']</span>');
		} else {
			$trans = $text;
		}
		return $trans;
	}

	/**
	 * @return string
	 */
	function getFilename() {
		return $this->langFolder . 'll-' . $this->lang . '.json';
	}

}
