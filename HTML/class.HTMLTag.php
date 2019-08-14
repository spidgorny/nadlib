<?php

/**
 * General HTML Tag representation.
 */

class HTMLTag
{
	public $tag;
	public $attr = array();
	public $content;
	public $isHTML = FALSE;

	function __construct($tag, array $attr = array(), $content = '', $isHTML = FALSE)
	{
		$this->tag = $tag;
		$this->attr = $attr;
		$this->content = $content;
		$this->isHTML = $isHTML;
	}

	function __toString()
	{
		$content = ($this->isHTML || $this->content instanceof HTMLTag)
			? $this->content
			: htmlspecialchars($this->content, ENT_QUOTES);
		return '<' . $this->tag . ' ' . $this->renderAttr($this->attr) . '>' . $content . '</' . $this->tag . '>';
	}

	static function renderAttr(array $attr)
	{
		$set = array();
		foreach ($attr as $key => $val) {
			if (is_array($val)) {
				$val = implode(' ', $val);    // for class="a b c"
			}
			$set[] = $key . '="' . htmlspecialchars($val, ENT_QUOTES) . '"';
		}
		return implode(' ', $set);
	}

	function attr($name)
	{
		return $this->attr[$name];
	}

	/**
	 * <a href="file/20131128/Animal-Planet.xml" target="_blank" class="nolink">32</a>
	 * @param string $str
	 * @return null|HTMLTag
	 */
	static function parse($str)
	{
		$str = trim($str);
		if ($str{0} != '<') return NULL;
		$parts = trimExplode(' ', $str);
		$tag = substr($parts[0], 1, -1);
		$attributes = str_replace('<' . $tag . '>', '', $str);
		$attributes = str_replace('</' . $tag . '>', '', $attributes);
		$obj = new HTMLTag($tag);
		$obj->attr = $obj->parseAttributes($attributes);
		$obj->content = strip_tags($str);
		return $obj;
	}

	/**
	 * https://gist.github.com/rodneyrehm/3070128
	 * @param string $text
	 * @return array
	 */
	function parseAttributes($text)
	{
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
