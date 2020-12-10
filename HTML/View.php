<?php

class View extends stdClass
{

	//use HTMLHelper;

	/**
	 * @var string
	 */
	protected $file;

	/**
	 * @var stdClass
	 */
	public $caller;

	/**
	 * @var LocalLang
	 */
	protected $ll;

	/**
	 * @var Request
	 */
	protected $request;

	protected $parts = array();

	protected $folder;

	/**
	 * @var Index
	 */
	public $index;

	/**
	 * Store something here and then @use $this->data('asd') to access it with escaping
	 * @var array
	 */
	public $data = array();

	/**
	 * @var AppController
	 */
	public $controller;

	function __construct($file, $copyObject = NULL)
	{
		TaylorProfiler::start(__METHOD__ . ' (' . $file . ')');
		$config = class_exists('Config') ? Config::getInstance() : new stdClass();
		$this->folder = (ifsetor($config->appRoot) ? cap($config->appRoot, '/') : '')
			. 'template/';
		if (class_exists('Config') && ifsetor($config->config[__CLASS__]['folder'])) {
			$this->folder = dirname(__FILE__) . '/' . $config->config[__CLASS__]['folder'];
		}
		$this->file = $file;
		if (!is_readable($this->folder . $this->file)) {
			//debug(filesize($this->folder.$this->file));
			//throw new Exception('File not readable '.$this->file);
		}
		/*nodebug(
			$config->appRoot,
			$config->config[__CLASS__],
			$config->config[__CLASS__]['folder'],
			$this->folder,
			$this->file);*/
		if (is_object($copyObject)) {
			$this->caller = $copyObject;
		} elseif (is_array($copyObject)) {
			$this->caller = (object)$copyObject;
		}
		$this->ll = (class_exists('Config') && Config::getInstance()->getLL())
			? Config::getInstance()->getLL() : NULL;
		$this->request = Request::getInstance();
		$this->index = class_exists('Index') ? Index::getInstance() : NULL;
		TaylorProfiler::stop(__METHOD__ . ' (' . $file . ')');
	}

	/*	Add as many public properties as you like and use them in the PHTML file. */

	function getFile()
	{
		$file = dirname($this->file) !== '.'
			? $this->file
			: $this->folder . $this->file;
		//debug(dirname($this->file), $this->folder, $this->file, $file, filesize($file));
		return $file;
	}

	function getContent($file, array $vars = [])
	{
		$content = '';
		ob_start();

		/** @noinspection NonSecureExtractUsageInspection */
		extract($vars);

		//debug($file);
		/** @noinspection PhpIncludeInspection */
		$content = require($file);

		if (!$content || $content === 1) {
			$content = ob_get_clean();
		} else {
			ob_end_clean();
		}

		$content = $this->s($content);
		return $content;
	}

	function render($vars = [])
	{
		$key = __METHOD__ . ' (' . basename($this->file) . ')';
		TaylorProfiler::start($key);

		$file = $this->getFile();
		$content = $this->getContent($file, $vars);

		preg_match_all('/__([^ _]+)__/', $content, $matches);
		foreach ($matches[1] as $ll) {
			if ($ll) {
				//debug('__' . $ll . '__', __($ll));
				$content = str_replace('__' . $ll . '__', __($ll), $content);
			}
		}

		if (DEVELOPMENT) {
			// not allowed in MRBS as some templates return OBJECT(!)
			//$content = '<div style="border: solid 1px red;">'.$file.'<br />'.$content.'</div>';
			$content .= '<!-- View template: ' . $this->file . ' -->' . "\n";
		}
		TaylorProfiler::stop($key);
		return $content;
	}

	/**
	 * Really primitive and buggy.
	 * @use markdown() instead
	 * @param $text
	 * @param null $linkCallback
	 * @return mixed|string
	 */
	function wikify($text, $linkCallback = null)
	{
		$inUL = false;
		$lines2 = array();
		$lines = trimExplode("\n", '' . $text);
		foreach ($lines as $line) {
			if ($line[0] == '*' || $line[0] == '-') {
				if (!$inUL) {
					$lines2[] = "<ul>";
					$inUL = true;
				}
			}
			$lines2[] = $inUL
				? '<li>' . substr($line, 2) . '</li>'
				: $line;
			if ($line[0] != '*' && $line[0] != '-') {
				if ($inUL) {
					$lines2[] = "</ul>";
					$inUL = FALSE;
				}
			}
		}
		if ($inUL) {
			$lines2[] = '</ul>';
		}
		$text = implode("\n", $lines2);
		//debug($lines2, $text);
		//$text = str_replace("\n* ", "\n<li> ", $text);
		//$text = str_replace("\n- ", "\n<li> ", $text);
		$text = str_replace("\n<ul>\n", "<ul>", $text);
		$text = str_replace("</ul>\n", "</ul>", $text);
		$text = str_replace("\n\n", "</p>\n<p>", $text);
		$text = str_replace("<p></p>", "", $text);
		$text = str_replace("<p></p>", "", $text);
		if ($linkCallback) {
			$text = preg_replace_callback('/\[\[(.*?)\]\]/', $linkCallback, $text);
		}
		$text = preg_replace('/====(.*?)====/', '<h2>\1</h2>', $text);
		return $text;
	}

	/**
	 * Will load the template file and split it by the divisor.
	 * Use renderPart($i) to render the corresponding part.
	 *
	 * @param string $sep
	 */
	function splitBy($sep)
	{
		$file = 'template/' . $this->file;
		$content = file_get_contents($file);
		$this->parts = explode($sep, $content);
	}

	/**
	 * http://www.php.net/manual/en/function.eval.php#88820
	 *
	 * @param int $i
	 * @return string
	 */
	function renderPart($i)
	{
		//debug($this->parts[$i]);
		return eval('?>' . $this->parts[$i]);
	}

	/**
	 * Uses htmlspecialchars()
	 * @param $str
	 * @return string
	 */
	function escape($str)
	{
		return htmlspecialchars($str, ENT_QUOTES);
	}

	function e($str)
	{
		return $this->escape($str);
	}

	function data($key)
	{
		return $this->e(ifsetor($this->caller->data[$key]));
	}

	function __toString()
	{
//		debug($this->file);
//		debug_pre_print_backtrace(); die();
		return $this->render() . '';
	}

	/**
	 * Use this helper to make URL (makeURL, getURL)
	 * @param array $params
	 * @return URL
	 */
	function link(array $params)
	{
		return Index::getInstance()->controller->getURL($params);
	}

	function __call($func, array $args)
	{
		$method = array($this->caller, $func);
		if (!is_callable($method) || !method_exists($this->caller, $func)) {
			//$method = array($this->caller, end(explode('::', $func)));
			$methodName = get_class($this->caller) . '::' . $func;
			throw new Exception('View: Method ' . $func . ' (' . $methodName . ') doesn\'t exists.');
		}
		return call_user_func_array($method, $args);
	}

	function &__get($var)
	{
		return $this->caller->$var;
	}

	/*	function __set($var, $val) {
			$this->caller->$var = &$val;
		}
	*/

	/**
	 * NAME        : autolink()
	 * VERSION     : 1.0
	 * AUTHOR      : J de Silva
	 * DESCRIPTION : returns VOID; handles converting
	 * URLs into clickable links off a string.
	 * TYPE        : functions
	 * http://www.gidforums.com/t-1816.html
	 * ======================================*/

	function autolink(&$text, $target = '_blank', $nofollow = true)
	{
		// grab anything that looks like a URL...
		$urls = $this->_autolink_find_URLS($text);
		if (!empty($urls)) // i.e. there were some URLS found in the text
		{
			array_walk($urls, array($this, '_autolink_create_html_tags'), array(
				'target' => $target,
				'nofollow' => $nofollow,
			));
			$text = str_replace(array_keys($urls), array_values($urls), $text);
		}
		return $text;
	}

	static function _autolink_find_URLS($text)
	{
		// build the patterns
		$scheme = '(http:\/\/|https:\/\/)';
		$www = 'www\.';
		$ip = '\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}';
		$subdomain = '[-a-z0-9_]+\.';
		$name = '[a-z][-a-z0-9]+\.';
		$tld = '[a-z]+(\.[a-z]{2,2})?';
		$the_rest = '\/?[a-z0-9._\/~#&=;%+?-]+[a-z0-9\/#=?]{1,1}';
		$pattern = "$scheme?(?(1)($ip|($subdomain)?$name$tld)|($www$name$tld))$the_rest";

		$pattern = '/' . $pattern . '/is';
		$c = preg_match_all($pattern, $text, $m);
		unset($text, $scheme, $www, $ip, $subdomain, $name, $tld, $the_rest, $pattern);
		if ($c) {
			return (array_flip($m[0]));
		}
		return (array());
	}

	function _autolink_create_html_tags(&$value, $key, $other = NULL)
	{
		$target = $nofollow = NULL;
		if (is_array($other)) {
			$target = ($other['target'] ? " target=\"$other[target]\"" : NULL);
			// see: http://www.google.com/googleblog/2005/01/preventing-comment-spam.html
			$nofollow = ($other['nofollow'] ? ' rel="nofollow"' : NULL);
		}
		$value = "<a href=\"$key\"$target$nofollow>$key</a>";
	}

	function linkBIDs($text)
	{
		$text = preg_replace('/\[#(\d+)\]/', '<a href="?bid=$1">$1</a>', $text);
		return $text;
	}

	function money($val)
	{
		return number_format(floatval($val), 2, '.', '');
	}

	function euro($val, $noCent = false)
	{
		$money = $this->money($val) . '&nbsp;&euro;';
		if ($noCent) {
			$money = str_replace('.00', '.-', $money);
		}
		return $money;
	}

	static function bar($percent, array $params = array(), $attr = array())
	{
		$percent = round($percent);
		$src = AutoLoad::getInstance()->nadlibFromDocRoot . 'bar.php?' . http_build_query($params + array(
					'rating' => $percent,
					'color' => '6DC5B4',
				));
		$attr += array(
			'src' => $src,
			'alt' => $percent . '%',
		);
		return new HTMLTag('img', $attr, NULL);
	}

	function purifyLinkify($comment)
	{
		$comment = preg_replace("/#(\w+)/", "<a href=\"Search?q=\\1\" target=\"_blank\">#\\1</a>", $comment);
		$comment = $this->cleanComment($comment);
		$comment = nl2br($comment);
		$comment .= $this->getEmbeddables($comment);
		return $comment;
	}

	/**
	 * @param string $comment
	 * @return string
	 */
	function cleanComment($comment)
	{
		//$v = new View('');
		//$comment = $v->autolink($comment);
		$config = HTMLPurifier_Config::createDefault();
		//debug($config);
		$cc = new CommentCollection();
		$config->set('HTML.Allowed', $cc->allowedTags);
		$config->set('Attr.AllowedFrameTargets', array('_blank'));
		$config->set('Attr.AllowedRel', array('nofollow'));
		$config->set('AutoFormat.Linkify', true);
		$config->set('HTML.TargetBlank', true);
		$config->set('HTML.Nofollow', true);
		$purifier = new HTMLPurifier($config);
		$clean_html = $purifier->purify($comment);
		return $clean_html;
	}

	function getEmbeddables($comment)
	{
		$content = '';
		$links = $this->getLinks($comment);
		foreach ($links as $link => $_) {
			/** @noinspection PhpUndefinedNamespaceInspection */
			$Essence = @Essence\Essence::instance();
			$Media = $Essence->embed($link);

			if ($Media) {
				$content .= $Media->html;
			}
		}
		return $content;
	}

	/**
	 * @param $comment
	 * @return array
	 */
	function getLinks($comment)
	{
		return View::_autolink_find_URLS($comment);
	}

	function s($a)
	{
		return MergedContent::mergeStringArrayRecursive($a);
	}

	static function markdown($text)
	{
		$my_html = \Michelf\Markdown::defaultTransform($text);
		return $my_html;
	}

	/**
	 * PHP 5.5
	 * @param array ...$variables
	 */
	/*	public function set(...$variables) {
			// returns just ['variables']
	/*		$ReflectionMethod =  new \ReflectionMethod(__CLASS__, __FUNCTION__);
			$params = $ReflectionMethod->getParameters();
			$paramNames = array_map(function( $item ) {
				/** @var ReflectionParameter $item */
	/*			return $item->getName();
			}, $params);*/
	/*
			$bt = debug_backtrace();
			$caller = $bt[0];
	//		debug($caller);
			$file = $caller['file'];
			$fileLines = file($file);
			$line = $fileLines[$caller['line']-1];
			preg_match('#\((.*?)\)#', $line, $match);
			$varList = $match[1];
			$varList = str_replace('$', '', $varList);
			$paramNames = trimExplode(',', $varList);
	//		debug($line, $paramNames);
			$variables = array_combine($paramNames, $variables);
			foreach ($variables as $key => $val) {
				$this->$key = $val;
			}
		}
	*/

	/**
	 * @param array $some
	 */
	function setSome(array $some)
	{
		foreach ($some as $key => $val) {
			$this->key = $val;
		}
	}

	function replace(array $map)
	{
		$file = $this->getFile();
		$content = $this->getContent($file);
		return str_replace(
			array_keys($map),
			array_values($map),
			$content);
	}

	function curly()
	{
		$file = $this->getFile();
		$template = $this->getContent($file);
		preg_match_all('/\{([^}]+)\}/m', $template, $matches);
//		debug($matches);
		foreach ($matches[1] as $i => $m) {
			$val = eval(' return ' . $m . ';');
			$template = str_replace('{' . $m . '}', $val, $template);
		}
		return $template;
	}

}
