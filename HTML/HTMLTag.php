<?php

/**
 * General HTML Tag representation.
 */

class HTMLTag implements ArrayAccess {
	public $tag;
	public $attr = array();
	public $content;
	public $isHTML = FALSE;
	public $closingTag = true;

	function __construct($tag, array $attr = array(), $content = '', $isHTML = FALSE) {
		$this->tag = $tag;
		$this->attr = $attr;
		$this->content = $content;
		$this->isHTML = $isHTML;
	}

	function __toString() {
		try {
			return $this->render();
		} catch (Exception $e) {
			debug_pre_print_backtrace();
			die($e->getMessage());
		}
	}

	function render() {
		if (is_array($this->content) || $this->content instanceof MergedContent) {
			$content = MergedContent::mergeStringArrayRecursive($this->content);
		} else {
			$content = ($this->isHTML
				|| $this->content instanceof HTMLTag
				|| $this->content instanceof htmlString)
				? $this->content
				: htmlspecialchars($this->content, ENT_QUOTES);
		}
		$attribs = $this->renderAttr($this->attr);
		$xmlClose = $this->closingTag ? '' : '/';
		$tag = '<'.trim($this->tag.' '. $attribs).$xmlClose.'>';
		$tag .= $content;
		if ($this->closingTag) {
			$tag .= '</' . $this->tag . '>'."\n";
		}
		return $tag;
	}

	function getContent() {
		return $this->content;
	}

	static function renderAttr(array $attr) {
		$set = array();
		foreach ($attr as $key => $val) {
			if (is_array($val) && $key == 'style') {
				$style = ArrayPlus::create($val);
				$style = $style->getHeaders(': ');
				$val = $style; 				   	// for style="a: b; c: d"
			} elseif (is_array($val)) {
				$val = implode(' ', $val);		// for class="a b c"
			}
			$set[] = $key.'="'.htmlspecialchars($val, ENT_QUOTES).'"';
		}
		return implode(' ', $set);
	}

	/**
	 * jQuery style
	 * @param $name
	 * @param null $value
	 * @return mixed
	 */
	function attr($name, $value = NULL) {
		if ($value) {
			$this->attr[$name] = $value;
			return $this;
		} else {
			return ifsetor($this->attr[$name]);
		}
	}

	function setAttr($name, $value) {
		$this->attr[$name] = $value;
		return $this;
	}

	function hasAttr($name) {
		return isset($this->attr[$name]);
	}

	function getAttr($name) {
		return ifsetor($this->attr[$name]);
	}

	/**
	 * <a href="file/20131128/Animal-Planet.xml" target="_blank" class="nolink">32</a>
	 * @param string $str
	 * @param bool   $recursive
	 * @return HTMLTag|null
	 */
	static function parse($str, $recursive = false) {
		$str = trim($str);
		if (strlen($str) && $str{0} != '<') return NULL;
		preg_match('/^(<[^>]*>)(.*?)?(<\/[^>]*>)?$/m', $str, $matches);
		//debug($matches);

		$tagAndAttributes = trimExplode(' ', ifsetor($matches[1]));
		$tag = first($tagAndAttributes);
//		echo $tag, BR;
		//$attributes = trimExplode(' ', $matches[1]);	// rest of the string
		$attributes = implode(' ', array_slice($tagAndAttributes, 1));
		$tag = substr($tag, 1);
		if (str_endsWith($tag, '>')) {
			$tag = substr($tag, 0, -1);
		}
//		echo $tag, BR;
		$obj = new HTMLTag($tag);
		$obj->attr = self::parseAttributes($attributes);
		if ($recursive) {
			// http://stackoverflow.com/a/28671566/417153
			//$innerHTML = preg_replace('/<[^>]*>([\s\S]*)<\/[^>]*>/', '$1', $str);
			//debug($matches, []);
			$innerHTML = ifsetor($matches[2]);
			if ($innerHTML) {
				$obj->content = self::parseDOM($innerHTML);
			}
		} else {
			$obj->content = strip_tags($str);
		}
		return $obj;
	}

	static function parseDOM($html) {
		$content = array();
		if (is_string($html)) {
			$doc = new DOMDocument();
			$doc->loadHTML($html);
			$doc = $doc->getElementsByTagName('body')->item(0);
		} elseif ($html instanceof DOMElement) {
			$doc = $html;
		} else {
			debug($html);
			return $content;
		}
		/** @var DOMElement $child */
		foreach ($doc->childNodes as $child) {
			//echo gettype2($child), BR;
			if ($child instanceof DOMElement) {
				$attributes = array();
				foreach ($child->attributes as $attribute_name => $attribute_node) {
					/** @var  DOMNode    $attribute_node */
					echo $attribute_name, ': ', gettype2($attribute_node), BR;
					$attributes[$attribute_name] = $attribute_node->nodeValue;
				}

				//$hasChildNodes = $child->hasChildNodes();	// incl Text
				$hasChildNodes = 0;
				foreach ($child->childNodes as $node) {
					if (!($node instanceof \DomText)) {
						$hasChildNodes++;
					}
				}

				if ($hasChildNodes) {
					$content[] = new HTMLTag(
						$child->tagName,
						$attributes,
						self::parseDOM($child));
				} else {
					$content[] = new HTMLTag(
						$child->tagName,
						$attributes,
						$child->textContent
					);
				}
			}
		}
		return $content;
	}

	/**
	 * https://gist.github.com/rodneyrehm/3070128
	 * @param string $text
	 * @return array
	 */
	static function parseAttributes($text) {
		if (is_array($text)) {
			return $text;
		}
		$attributes = array();
		$pattern = '#(?(DEFINE)
(?<name>[a-zA-Z][a-zA-Z0-9-:]*)
(?<value_double>"[^"]+")
(?<value_single>\'[^\']+\')
(?<value_none>[^\s>]+)
(?<value>((?&value_double)|(?&value_single)|(?&value_none)))
)
(?<n>(?&name))(=(?<v>(?&value)))?#xs';

		if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$attributes[$match['n']] = isset($match['v'])
					? trim($match['v'], '\'"')
					: null;
			}
		}
		return $attributes;
	}

	public function offsetExists($offset) {
		return isset($this->attr[$offset]);
	}

	public function offsetGet($offset) {
		return $this->getAttr($offset);
	}

	public function offsetSet($offset, $value) {
		$this->setAttr($offset, $value);
	}

	public function offsetUnset($offset) {
		unset($this->attr[$offset]);
	}

	static function __set_state(array $properties) {
		$a = new static($properties['tag']);
		foreach ($properties as $key => $val) {
			$a->$key = $val;
		}
		return $a;
	}

	function getHash($length = null) {
		$hash = spl_object_hash($this);
		if ($length) {
			$hash = sha1($hash);
			$hash = substr($hash, 0, $length);
		}
		return '#'.$hash;
	}

}
