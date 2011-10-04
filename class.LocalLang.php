<?php

/**
 * Singleton
 *
 */
class LocalLang {
	protected $filename = 'lib/LocalLang.object';
	protected $excel = 'lib/translation.xml';
	var $ll = array();
	protected $defaultLang = 'en';
	protected $possibleLangs = array('en', 'de', 'es', 'uk', 'ru');
	public $lang;

	function __construct() {
		if ($_REQUEST['setLangCookie']) {
			$_COOKIE['lang'] = $_REQUEST['setLangCookie'];
			setcookie('lang', $_REQUEST['setLangCookie'], time()+365*24*60*60);
		}

    		$this->ll = $this->readPersistant();
		if (!$this->ll) {
			$this->ll = $this->readExcel($this->possibleLangs);
			if ($this->ll) {
				$this->savePersistant($this->ll);
			}
		}
		$this->detectLang();
		$this->lang = $_COOKIE['lang'] && in_array($_COOKIE['lang'], $this->possibleLangs)? $_COOKIE['lang'] : $this->lang;
		$this->ll = $this->ll[$this->lang];
	}

	function readPersistant() {
		if (file_exists($this->filename)) {
			if (filemtime($this->filename) > filemtime($this->excel)) {
				$data = file_get_contents($this->filename);
				$data = unserialize($data);
			}
		}
		return $data;
	}

	function savePersistant($data) {
		$data = serialize($data);
		$data = file_put_contents($this->filename, $data);
		//debug('save');
	}

	function readExcel(array $keys) {
		//debug($keys);
		$data = array();
		if (file_exists($this->excel)) {
			$filedata = file_get_contents($this->excel);
			$filedata = str_replace('xmlns="http://www.w3.org/TR/REC-html40"', '', $filedata);
			$xml = simplexml_load_string($filedata);
			$namespaces = $xml->getNamespaces(true);
			//debug($namespaces);
			foreach ($namespaces as $prefix => $ns) {
			    $xml->registerXPathNamespace($prefix, $ns);
			}
			$s = $xml->Worksheet[0]->Table;
			foreach ($s->Row as $row) {
				//debug($row);
				//$dataLine = array();
				//debug(sizeof($row->Cell));
				$i = 0;
				foreach ($row->Cell as $cell) {
					//debug(array($i, $cell));
					$key = $keys[$i++];
					if ($key) {
						//$dataLine[$key] = utf8_decode($cell->Data);
						$cellText = $cell->Data;
						if (!$cellText) {
							//$cellText = $cell->Data->children('http://www.w3.org/TR/REC-html40');
							$cellText = $cell->asXML();
							$cellText = strip_tags($cellText);
							//debug($cellText);
						}
						//$cellText = mb_convert_encoding($cellText, 'Windows-1251', 'UTF-8');
						$cellText = trim($cellText);
						if ($cellText) {
							$data[$key][] = $cellText;
						}
					}
				}
				//debug($dataLine);
			}
		}
		//debug($data);
		foreach ($data as $lang => &$trans) {
			$trans = array_unique($trans);
			//debug(sizeof($trans));
			$trans = array_slice($trans, 0, sizeof($data[$this->defaultLang]));
			$trans = array_combine($data[$this->defaultLang], $trans);
		}
		//debug($data);
		return $data;
	}

	function detectLang() {
		$l = new LanguageDetect();
		//debug($this->ll);
		//debug($l->languages);
		foreach ($l->languages as $lang) {
			//debug(array($lang => isset($this->ll[$lang])));
			if (isset($this->ll[$lang])) {
				//debug($lang.' - '.sizeof($this->ll));
				$this->lang = $lang;
				$replace = TRUE;
				break;
			}
		}
		if (!$replace) {
/*			$firstKey = array_keys($this->ll);
			reset($firstKey);
			$firstKey = current($firstKey);
			$this->ll = $this->ll[$firstKey];
*/			$this->lang = $this->defaultLang;
			//debug('firstKey: '.$firstKey);
		}
		//debug($this->ll);
	}

	static function getInstance() {
		static $instance = NULL;
		if (!$instance) {
			$instance = new LocalLang();
		}
		return $instance;
	}

	function T($text, $replace = NULL) {
		$trans = $this->ll[$text] ? $this->ll[$text] : $text;
		$trans = str_replace('%s', $replace, $trans);
		return $trans;
	}

}
