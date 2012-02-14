<?php

/**
 * Singleton
 *
 */
class LocalLang {
	protected $filename = 'lib/LocalLang.object';
	protected $excel = 'lib/translation.xml';
	var		  $ll = array();									// actual messages
	protected $defaultLang = 'en';
	public	  $possibleLangs = array('en', 'de', 'es', 'ru', 'uk');
	public	  $lang;											// name of the selected language
	protected $isCache = TRUE;
	public    $indicateUntranslated = true;
	protected $codeID = array();

	function __construct($forceLang = NULL) {
		if ($_REQUEST['setLangCookie']) {
			$_COOKIE['lang'] = $_REQUEST['setLangCookie'];
			setcookie('lang', $_REQUEST['setLangCookie'], time()+365*24*60*60, dirname($_SERVER['PHP_SELF']));
		}

		// detect language
		if ($forceLang) {
			$this->lang = $forceLang;
		} else {
			$this->detectLang();
			$this->lang = $_COOKIE['lang'] && in_array($_COOKIE['lang'], $this->possibleLangs)
				? $_COOKIE['lang']
				: $this->lang;
		}

/*		// read from excel
		$this->ll = $this->readPersistant();
		if (!$this->ll) {
			$this->ll = $this->readExcel(array_merge(array('code'), $this->possibleLangs));
			if ($this->ll) {
				$this->savePersistant($this->ll);
			}
		}
		$this->ll = $this->ll[$this->lang];
*/
		// read from DB
		$rows = $this->readDB($this->lang);
		if ($rows) {
			$this->codeID = ArrayPlus::create($rows)->column_assoc('code', 'id')->getData();
			$this->ll = ArrayPlus::create($rows)->column_assoc('code', 'text')->getData();
		}
	}

	function readPersistant() {
		return NULL; // temporary until this is rewritten to read data from DB
		if (file_exists($this->filename)) {
			if (filemtime($this->filename) > filemtime($this->excel) && $this->isCache) {
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
						$cellIndex = $cell['ss:Index']+0;
						//debug($cell->attributes()->asXML(), $i);
						if (!$cellIndex) {
							$cellIndex = sizeof($data[$key]);
						}
						if ($cellText) {
							$data[$key][$cellIndex] = $cellText;
						}
					}
				}
				//debug($dataLine);
			}
		}
		//debug($data);
		foreach ($data as $lang => &$trans) {
			if ($lang != 'code') {
				//$trans = array_unique($trans);
				//debug(sizeof($trans));
				$trans = array_slice($trans, 0, sizeof($data['code']));
/*				debug(array(
					'array_combine',
					$data['code'],
					$trans,
				));
*/
				if (sizeof($data['code']) == sizeof($trans)) {
					$trans = array_combine($data['code'], $trans);
				} else {
					$diff = array_diff_key($data['code'], $trans);
					debug($diff, 'Error in '.__METHOD__);
				}
			}
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

	/**
	 *
	 * @param <type> $text
	 * @param <type> $replace
	 * @param <type> $s2
	 * @return string translated message
	 */
	function T($text, $replace = NULL, $s2 = NULL) {
		if (isset($this->ll[$text])) {
			if ($this->ll[$text] && $this->ll[$text] != '.') {
				$trans = $this->ll[$text];
			} else {
				if ($this->indicateUntranslated) {
					$trans = '<span class="untranslatedMessage">{'.$text.'}</span>';
				} else {
					$trans = $text;
				}
			}
		} else {
			if ($this->indicateUntranslated) {
				$trans = '<span class="missingMessage">['.$text.']</span>';
			} else {
				$trans = $text;
			}
			$this->saveMissingMessage($text);
		}
		$trans = str_replace('%s', $replace, $trans);
		$trans = str_replace('%1', $replace, $trans);
		$trans = str_replace('%2', $s2, $trans);
		return $trans;
	}

	function saveMissingMessage($text) {
		if ($GLOBALS['i']->development) {
			$missingWords = array();
			$fp = fopen('lib/missing.txt', 'r');
			while (!feof($fp)) {
				$line = fgets($fp);
				$line = trim($line);
				$missingWords[$line] = $line;
			}
			fclose($fp);
			//debug($missingWords);

			if (!isset($missingWords[$text])) {
				$fp = fopen('lib/missing.txt', 'a');
				fputs($fp, $text."\n");
				fclose($fp);
			}
		}
	}

	function M($text) {
		return $this->T($text);
	}

	function getMessages() {
		return $this->ll;
	}

	function id($code) {
		return $this->codeID[$code];
	}

	function readDB($lang) {
		try {
			$db = Config::getInstance()->db;
			$rows = $db->fetchSelectQuery('app_interface', array(
				'lang' => $lang,
			), 'ORDER BY id');
		} catch (Exception $e) {
			// read from DB failed, continue
			//throw new Exception('Reading locallang from DB failed.');
			// throwing exception leads to making a new instance of LocalLang and it masks DB error
		}
		return $rows;
	}

	function showLangSelection() {
		$en = $this->readDB('en');
		$countEN = sizeof($en) ? sizeof($en) : 1;
		foreach ($this->possibleLangs as $lang) {
			$rows = $this->readDB($lang);
			$u = new URL();
			$u->setParam('setLangCookie', $lang);
			echo '<a href="?'.$u->buildQuery().'" title="'.$lang.' ('.
				number_format(sizeof($rows)/$countEN*100, 0).'%)">
				<img src="img/'.$lang.'.gif" width="20" height="12">
			</a>';
		}
	}

}

function __($code, $r1 = null, $r2 = null, $r3 = null) {
	$ll = LocalLang::getInstance();
	return $ll->T($code, $r1, $r2, $r3);
}

