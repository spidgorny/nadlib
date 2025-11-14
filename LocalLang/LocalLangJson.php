<?php

/**
 * Class LocalLangTest
 * It's reading data from the class/ll-en.json file and writes new messages back to it
 */
class LocalLangJson extends LocalLangDummy
{

	public $langFolder;

	public function __construct($langFolder = 'class/')
	{
		parent::__construct();
		$this->langFolder = $langFolder;
//		$this->log(__METHOD__, $this->langFolder);
//		ob_start();
//		debug_print_backtrace();
//		$bt = ob_get_clean();
//		$this->log(__METHOD__, $bt);
	}

	public function areThereTranslationsFor($lang): bool
	{
		$this->lang = $lang;  // temporary
		$file = $this->getFilename();
		//debug($lang, $file, $ok);
		return is_file($file);
	}

	public function getFilename(): string
	{
		return $this->langFolder . 'll-' . $this->lang . '.json';
	}

	public function readDB(): void
	{
//		$this->log(__METHOD__, $this->getFilename());
		$file = file_get_contents($this->getFilename());
		$this->ll = json_decode($file, true, 512, JSON_THROW_ON_ERROR);
		//debug($file, $this->ll);
	}

	public function __destruct()
	{
		$jsonEncode = json_encode($this->ll, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
		$file = $this->getFilename();
		if (filesize($file) < mb_strlen($jsonEncode)) {
			file_put_contents($file, $jsonEncode);
		}
	}

	public function saveMissingMessage($text): void
	{
		$this->updateMessage([
			//'code' => RandomStringGenerator::likeYouTube(),
			'code' => $text,
			'text' => $text,
		]);
	}

	public function updateMessage(array $data): void
	{
		$this->ll[$data['code']] = $data['text'];
	}

	public function getEditLinkMaybe($text, $id = null, $class = 'untranslatedMessage')
	{
		if ($this->indicateUntranslated) {
			$trans = new HtmlString('<span class="untranslatedMessage">[' . htmlspecialchars($text) . ']</span>');
		} else {
			$trans = $text;
		}

		return $trans;
	}

}
