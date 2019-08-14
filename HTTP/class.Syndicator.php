<?php

define('LOWERCASE', 3);
define('UPPERCASE', 1);

class Syndicator
{

	/**
	 * @var string
	 */
	var $url;

	/**
	 * @var bool
	 */
	var $isCaching = FALSE;

	/**
	 * @var string
	 */
	var $html;

	var $tidy;

	/**
	 * @var SimpleXMLElement
	 */
	var $xml;

	/**
	 * @var array
	 */
	public $json;

	var $xpath;    // last used, for what?

	var $recodeUTF8;

	/**
	 *
	 * @var FileCache
	 */
	var $cache;

	/**
	 * @var Proxy|bool
	 */
	public $useProxy = NULL;

	public $input = 'HTML';

	/**
	 * @var array
	 */
	public $log = array();

	function __construct($url = NULL, $caching = TRUE, $recodeUTF8 = 'utf-8')
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if ($url) {
			$this->url = $url;
			$this->isCaching = $caching;
			$this->recodeUTF8 = $recodeUTF8;
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	/**
	 * @param $url
	 * @param bool $caching
	 * @param string $recodeUTF8
	 * @return Syndicator
	 */
	static function readAndParseHTML($url, $caching = true, $recodeUTF8 = 'utf-8')
	{
		$s = new self($url, $caching, $recodeUTF8);
		$s->html = $s->retrieveFile();
		$s->xml = $s->processFile($s->html);
		return $s;
	}

	/**
	 * @param $url
	 * @param bool $caching
	 * @param string $recodeUTF8
	 * @return Syndicator
	 */
	static function readAndParseXML($url, $caching = true, $recodeUTF8 = 'utf-8')
	{
		$s = new self($url, $caching, $recodeUTF8);
		$s->input = 'XML';
		$s->html = $s->retrieveFile();
		$s->xml = $s->processFile($s->html);
		return $s;
	}

	/**
	 * @param $url
	 * @param bool $caching
	 * @param string $recodeUTF8
	 * @return Syndicator
	 */
	static function readAndParseJSON($url, $caching = true, $recodeUTF8 = 'utf-8')
	{
		$s = new self($url, $caching, $recodeUTF8);
		$s->input = 'JSON';
		$s->html = $s->retrieveFile();
		Index::getInstance()->controller->log('Downloaded', __METHOD__);
		$s->json = json_decode($s->html);
		Index::getInstance()->controller->log('JSON decoded', __METHOD__);
		return $s;
	}

	function retrieveFile($retries = 1)
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if ($this->isCaching) {
			$this->cache = new FileCache();
			if ($this->cache->hasKey($this->url)) {
				$html = $this->cache->get($this->url);
				$this->log('<a href="' . $this->cache->map($this->url) . '">' . $this->cache->map($this->url) . '</a> Size: ' . strlen($html), __CLASS__);
			} else {
				$this->log('No cache. Download File.');
				$html = $this->downloadFile($this->url, $retries);
				$this->cache->set($this->url, $html);
				//debug($cache->map($this->url).' Size: '.strlen($html), 'Set cache');
			}
		} else {
			$html = $this->downloadFile($this->url, $retries);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $html;
	}

	function log($msg)
	{
		$this->log[] = $msg;
		if (class_exists('Index')) {
			$c = Index::getInstance()->controller;
			$c->log($msg);
		} else {
			echo $msg . BR;
		}
	}

	function downloadFile($href, $retries = 1)
	{
		if (startsWith($href, 'http')) {
			$ug = new URLGet($href);
			$ug->timeout = 10;
			$ug->fetch($this->useProxy, $retries);
			return $ug->getContent();
		} else {
			return file_get_contents($href);
		}
	}

	/**
	 * http://code.google.com/p/php-excel-reader/issues/attachmentText?id=8&aid=2334947382699781699&name=val_patch.php&token=45f8ef6a787d2ab55cb821688e28142d
	 * @param $str
	 * @return mixed
	 */
	function detect_cyr_charset($str)
	{
		$charsets = Array(
			'koi8-r' => 0,
			'Windows-1251' => 0,
			'CP866' => 0,
			'ISO-8859-5' => 0,
			'MAC' => 0
		);
		for ($i = 0, $length = strlen($str); $i < $length; $i++) {
			$char = ord($str[$i]);
			//non-russian characters
			if ($char < 128 || $char > 256) continue;

			//CP866
			if (($char > 159 && $char < 176) || ($char > 223 && $char < 242))
				$charsets['CP866'] += LOWERCASE;
			if (($char > 127 && $char < 160)) $charsets['CP866'] += UPPERCASE;

			//KOI8-R
			if (($char > 191 && $char < 223)) $charsets['koi8-r'] += LOWERCASE;
			if (($char > 222 && $char < 256)) $charsets['koi8-r'] += UPPERCASE;

			//WIN-1251
			if ($char > 223 && $char < 256) $charsets['Windows-1251'] += LOWERCASE;
			if ($char > 191 && $char < 224) $charsets['Windows-1251'] += UPPERCASE;

			//MAC
			if ($char > 221 && $char < 255) $charsets['MAC'] += LOWERCASE;
			if ($char > 127 && $char < 160) $charsets['MAC'] += UPPERCASE;

			//ISO-8859-5
			if ($char > 207 && $char < 240) $charsets['ISO-8859-5'] += LOWERCASE;
			if ($char > 175 && $char < 208) $charsets['ISO-8859-5'] += UPPERCASE;

		}
		arsort($charsets);
		return key($charsets);
	}

	function processFile($html)
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		//debug(substr($html, 0, 1000));
		if ($this->input == 'HTML') {
			$html = html_entity_decode($html, ENT_COMPAT, $this->recodeUTF8);
		}

		if ($this->recodeUTF8) {
			$detect = mb_detect_encoding($html, $this->recodeUTF8 === TRUE ? NULL : $this->recodeUTF8);
			//debug($detect, "mb_detect_encoding($this->recodeUTF8)");
			if (!$detect) {
				$detect = $this->detect_cyr_charset($html);
				debug($detect, "detect_cyr_charset");
			}
			$utf8 = mb_convert_encoding($html, 'UTF-8', $this->recodeUTF8 === TRUE ? 'Windows-1251' : $detect);
			$utf8 = str_replace(0x20, ' ', $utf8);
		} else {
			$utf8 = $html;
		}
		$utf8 = str_replace('&#151;', '-', $utf8);
		//debug(substr($utf8, 0, 1000));

		// new
		//$utf8 = $this->strip_html_tags($utf8);
		//$utf8 = str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">', '', $utf8);
		//debug($utf8);

		$this->tidy = $this->tidy($utf8);
		//$this->tidy = $utf8;
		//debug(substr($tidy, 0, 1000));
		//exit();
		$this->tidy = preg_replace('/<meta name="description"[^>]*>/', '', $this->tidy);

		$recode = $this->recode($this->tidy);
		//debug($recode, 'Recode');

		//$recode = preg_replace('/<option value="0">.*?<\/option>/is', '', $recode);

		$xml = $this->getXML($recode);
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $xml;
	}

	function tidy($html)
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		//debug(extension_loaded('tidy'));
		if ($this->input == 'HTML') {
			if (extension_loaded('tidy')) {
				$config = array(
					'clean' => true,
					'indent' => true,
					'output-xhtml' => true,
					//'output-html'		=> true,
					//'output-xml' 		=> true,
					'wrap' => 1000,
					'numeric-entities' => true,
					'char-encoding' => 'raw',
					'input-encoding' => 'raw',
					'output-encoding' => 'raw',

				);
				$tidy = new tidy;
				$tidy->parseString($html, $config);
				$tidy->cleanRepair();
				//$out = tidy_get_output($tidy);
				$out = $tidy->value;
			} else {
				$out = htmLawed($html, array(
					'valid_xhtml' => 1,
					'tidy' => 1,
				));
			}
		} elseif ($this->input == 'XML') {
			$out = $html;    // hope that XML is valid
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $out;
	}

	function recode($xml)
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		//$enc = mb_detect_encoding($xml);
		//debug($enc);
		//$xml = mb_convert_encoding($xml, 'UTF-8', 'Windows-1251');
		$xml = mb_convert_encoding($xml, 'UTF-8', 'UTF-8');
		//$NewEncoding = new ConvertCharset('Windows-1251', 'UTF-8');
		//$xml = $NewEncoding->Convert($xml);
		//$xml2 = mb_convert_encoding($xml, 'UTF-8', 'CP1252');
		//debug(array(strlen($xml), strlen($xml2)));
		//$xml = $xml2;

		//$xml = html_entity_decode($xml);
		//$xml = str_replace('//<![CDATA[', '<![CDATA[', $xml);
		//$xml = preg_replace("/<html[^>]*>/", "<html>", $xml);
		//$xml = $this->strip_html_tags($xml);
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $xml;
	}

	function getXML($recode)
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		try {
			if ($recode{0} == '<') {
				$xml = new SimpleXMLElement($recode);
				//$xml['xmlns'] = '';
				$namespaces = $xml->getNamespaces(true);
				//debug($namespaces, 'Namespaces');
				//Register them with their prefixes
				foreach ($namespaces as $prefix => $ns) {
					$xml->registerXPathNamespace('default', $ns);
					break;
				}
			} else {
				$xml = new SimpleXMLElement('');
			}
		} catch (Exception $e) {
			//debug($recode);
//			$qb = new SQLBuilder();
			/*			$query = $qb->getInsertQuery('error_log', array(
							'type' => 'Parse XML',
							'method' => __METHOD__,
							'line' => __LINE__,
							'exception' => $e,
							//'trace' => substr(print_r(debug_backtrace(), TRUE), 0, 1024*8),
						));
						debug($e, 'getXML');
						$db->perform($query);
			*/
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $xml;
	}

	function getElements($xpath)
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if ($this->xml) {
			//debug($this->xml);
			$this->xpath = $xpath;
			$target = $this->xml->xpath($this->xpath);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $target;
	}

	/**
	 *
	 * @param type $xpath
	 * @return simple_xml_element
	 */
	function getElement($xpath)
	{
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$res = $this->getElements($xpath);
		if ($res) {
			reset($res);
			$first = current($res);
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $first;
	}

	/**
	 * No XPATH
	 *
	 * @param unknown_type $xml
	 * @return unknown
	 */
	function getSXML($xml)
	{
		$xml_parser = new sxml();
		$xml_parser->parse($xml);
		$xml = $xml_parser->datas;
		//debug($xml_parser);
		return $xml;
	}

	function getDOM($xml)
	{
		$dom = domxml_xmltree($xml);
		return $dom;
	}

	/**
	 * Remove HTML tags, including invisible text such as style and
	 * script code, and embedded objects.  Add line breaks around
	 * block-level tags to prevent word joining after tag removal.
	 */
	function strip_html_tags($text)
	{
		return preg_replace('/<script[^>]*?>.*?<\/script>/is', '', $text);
		$text = preg_replace(
			array(
				// Remove invisible content
				'@<head[^>]*?>.*?</head>@siu',
				'@<style[^>]*?>.*?</style>@siu',
				'@<script[^>]*?.*?</script>@siu',
				'@<object[^>]*?.*?</object>@siu',
				'@<embed[^>]*?.*?</embed>@siu',
				'@<applet[^>]*?.*?</applet>@siu',
				'@<noframes[^>]*?.*?</noframes>@siu',
				'@<noscript[^>]*?.*?</noscript>@siu',
				'@<noembed[^>]*?.*?</noembed>@siu',
				// Add line breaks before and after blocks
				'@</?((address)|(blockquote)|(center)|(del))@iu',
				'@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
				'@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
				'@</?((table)|(th)|(td)|(caption))@iu',
				'@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
				'@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
				'@</?((frameset)|(frame)|(iframe))@iu',
			),
			array(
				' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
				"\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
				"\n\$0", "\n\$0",
			),
			$text);
		return /*strip_tags*/ ($text);
	}

	function trimArray(array $elements)
	{
		foreach ($elements as &$e) {
			$e = trim(strip_tags($e));
		}
		return $elements;
	}

}

/*
		//$xml = $this->getSXML($recode);
		//$xml = $this->getDOM($recode);

/*		$html = new DOMDocument('1.0', 'UTF-8');
		// fetch drupal planet and parse it (@ suppresses warnings).
		@$html->loadHTMLFile($this->url);
		// convert DOM to SimpleXML
		$xml = simplexml_import_dom($html);
*/
//return $xml;
//debug($xml);
//debug($xml->body->pre[1]->a[14]);
/**/
