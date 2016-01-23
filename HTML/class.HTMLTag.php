<?php

/**
 * General HTML Tag representation.
 */

class HTMLTag {
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
		return $this->render();
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
			if (is_array($val)) {
				$val = implode(' ', $val);	// for class="a b c"
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
		} else {
			return ifsetor($this->attr[$name]);
		}
	}

	function setAttr($name, $value) {
		$this->attr[$name] = $value;
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
		preg_match('/^(<[^>]*>)(.*?)?(<\/[^>]*>)?$/', $str, $matches);

		$tagAndAttributes = trimExplode(' ', $matches[1]);
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
			$innerHTML = $matches[2];
			$obj->content = self::parseDOM($innerHTML);
		} else {
			$obj->content = strip_tags($str);
		}
		return $obj;
	}

	static function parseDOM($html) {
		$content = [];
		if (is_string($html)) {
			$doc = new DOMDocument();
			$doc->loadHTML($html);
			$doc = $doc->getElementsByTagName('body')->item(0);
		} else {
			$doc = $html;
		}
		/** @var DOMElement $child */
		foreach ($doc->childNodes as $child) {
			echo gettype2($child), BR;
			if ($child instanceof DOMElement) {
				$attributes = [];
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

}
