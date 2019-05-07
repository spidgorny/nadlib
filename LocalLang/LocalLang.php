<?php

/**
 * Singleton
 *
 */
abstract class LocalLang
{
	/**
	 * actual messages
	 * @var array
	 */
	var $ll = array();

	protected $defaultLang = 'en';

	public $possibleLangs = array('en', 'de', 'es', 'ru', 'uk');

	/**
	 * name of the selected language
	 * @var string
	 */
	public $lang;

	public $indicateUntranslated = false;

	protected $codeID = array();

	public $editMode = false;

	/**
	 * @var bool
	 */
	public $debug = false;

	public $saveMissingMessages = true;

	/**
	 * Will detect the language by the cookie or browser sniffing
	 * @param null $forceLang
	 */
	function __construct($forceLang = NULL)
	{
		if (isset($_REQUEST['setLangCookie']) && $_REQUEST['setLangCookie']) {
			$_COOKIE['lang'] = $_REQUEST['setLangCookie'];
			setcookie('lang', $_REQUEST['setLangCookie'], time() + 365 * 24 * 60 * 60, dirname($_SERVER['PHP_SELF']));
		}

		// detect language
		if ($forceLang) {
			$this->lang = $forceLang;
		} else {
			$this->detectLang();
			$this->lang = isset($_COOKIE['lang']) && $_COOKIE['lang'] && in_array($_COOKIE['lang'], $this->possibleLangs)
				? $_COOKIE['lang']
				: $this->lang;
		}

		if (class_exists('Config')) {
			$c = Config::getInstance();
			if (isset($c->config[__CLASS__])) {
				foreach ($c->config[__CLASS__] as $key => $val) {
					$this->$key = $val;
				}
			}
			//debug($c->config, $c->config[__CLASS__], $this);
		}

		// Read language data from somewhere in a subclass
	}

	function detectLang()
	{
		$l = new LanguageDetect();
		//debug($this->ll);
		//debug($l->languages);
		$replace = false;
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
			*/
			$this->lang = $this->defaultLang;
			//debug('firstKey: '.$firstKey);
		}
		//debug($this->ll);
	}

	static function getInstance()
	{
		debug_pre_print_backtrace();
		static $instance = NULL;
		if (!$instance) {
			//$instance = new static(); // PHP 5.2
		}
		return $instance;
	}

	/**
	 *
	 * @param $text
	 * @param null $replace
	 * @param null $s2
	 * @param null $s3
	 * @return string translated message
	 * @internal param $ <type> $replace
	 * @internal param $ <type> $s2
	 * @internal param $ <type> $text
	 */
	function T($text, $replace = NULL, $s2 = NULL, $s3 = NULL)
	{
		if (isset($this->ll[$text])) {
			$trans = ifsetor($this->ll[$text], $text);
			$trans = $this->Tp($trans, $replace, $s2, $s3);
			$trans = $this->getEditLinkMaybe($trans, $text, '');
			//if ($text == 'Search') { debug($text, $trans); }
		} else {
			//debug($this->ll);
			//debug($text, $this->ll[$text], spl_object_hash($this));
			$this->saveMissingMessage($text);
			$trans = $this->Tp($text, $replace, $s2, $s3);
			$trans = $this->getEditLinkMaybe($trans);
		}
		if ($this->debug = $text == 'asd') {
			debug($text, isset($this->ll[$text]), $this->ll[$text], $trans);
		}
		return $trans;
	}

	/**
	 * Bare plain-text localization without outputting any HTML
	 * @param $trans
	 * @param null $replace
	 * @param null $s2
	 * @param null $s3
	 * @return mixed|null
	 */
	static function Tp($trans, $replace = NULL, $s2 = NULL, $s3 = NULL)
	{
		if (is_array($replace)) {
			foreach ($replace as $key => $val) {
				$trans = str_replace('{' . $key . '}', $val, $trans);
			}
		} else {
			$trans = str_replace('%s', $replace, $trans);
			$trans = str_replace('%1', $replace, $trans);
			$trans = str_replace('%2', $s2, $trans);
			$trans = str_replace('%3', $s3, $trans);
		}
		return $trans;
	}

	function getEditLinkMaybe($text, $id = NULL, $class = 'untranslatedMessage')
	{
		if ($this->editMode && $id) {
			$trans = '<span class="' . $class . ' clickTranslate" rel="' . htmlspecialchars($id) . '">' . $text . '</span>';
			$al = AutoLoad::getInstance();
			$index = Index::getInstance();
			$index->addJQuery();
			$index->addJS($al->nadlibFromDocRoot . 'js/clickTranslate.js');
			$index->addCSS($al->nadlibFromDocRoot . 'CSS/clickTranslate.css');
		} else if ($this->indicateUntranslated) {
			$trans = '<span class="untranslatedMessage">[' . $text . ']</span>';
		} else {
			$trans = $text;
		}
		return $trans;
	}

	abstract function saveMissingMessage($text);

	function M($text)
	{
		return $this->T($text);
	}

	function getMessages()
	{
		return $this->ll;
	}

	function id($code)
	{
		return ifsetor($this->codeID[$code]);
	}

	/**
	 * This doesn't work in Chrome somehow
	 * @return string
	 */
	function showLangSelectionDropDown()
	{
		$options = '';
		foreach ($this->possibleLangs as $code) {
			$selected = $this->lang == $code ? ' selected="selected"' : '';
			$options .= '<option value="' . $code . '"' . $selected . '>' . __($code) . '</option>';
		}
		$content = '
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="POST">
			<select class="input-small langMenu" name="setLangCookie">' . $options . '
			</select>
		</form>';
		Index::getInstance()->addCSS('vendor/jquery-switch-master/jquery.switch/jquery.switch.css');
		Index::getInstance()->addJS('vendor/jquery-switch-master/jquery.switch/jquery.switch.min.js');
		//Index::getInstance()->addJS('vendor/jquery-switch-master/jquery.switch/jquery.switch.js');
		return $content;
	}

}

if (!function_exists('__')) {    // conflict with cakePHP

	function __($code, $r1 = null, $r2 = null, $r3 = null)
	{
		if (class_exists('Config')) {
			$config = Config::getInstance();
		} else {
			$config = NULL;
		}
		nodebug($code, !!$config,
			is_object($config) ? get_class($config) : gettype($config),
			!!$config->getLL());
		if (!empty($config) && $config->getLL()) {
			$text = $config->getLL()->T($code, $r1, $r2, $r3);
			//echo '<pre>', get_class($index->ll), "\t", $code, "\t", $text, '</pre><br />', "\n";
			return $text;
		} else {
			$code = LocalLang::Tp($code, $r1, $r2, $r3);
			return $code;
		}
	}

	/**
	 * Same as __(), but calls only str_replace() without translating
	 * @param $code
	 * @param null $r1
	 * @param null $r2
	 * @param null $r3
	 * @return mixed|null
	 */
	function __p($code, $r1 = null, $r2 = null, $r3 = null)
	{
		if (class_exists('Config')) {
			$index = Config::getInstance();
		}
		//debug(!!$index, get_class($index), !!$index->ll, get_class($index->ll));
		if ($index && $index->getLL()) {
			$text = $index->getLL()->Tp($code, $r1, $r2, $r3);
			//echo '<pre>', get_class($index->ll), "\t", $code, "\t", $text, '</pre><br />', "\n";
			return $text;
		} else {
			return $code;
		}
	}

}
