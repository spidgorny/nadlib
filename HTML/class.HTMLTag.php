<?php

namespace spidgorny\nadlib\HTML;

/**
 * General HTML Tag representation.
 */

class HTMLTag {
	public $tag;
	public $attr = array();
	public $content;
	public $isHTML = FALSE;

	function __construct($tag, array $attr = array(), $content = '', $isHTML = FALSE) {
		$this->tag = $tag;
		$this->attr = $attr;
		$this->content = $content;
		$this->isHTML = $isHTML;
	}

	function __toString() {
		$content = $this->isHTML ? $this->content : htmlspecialchars($this->content, ENT_QUOTES);
		return '<'.$this->tag.' '.$this->renderAttr($this->attr).'>'.$content.'</'.$this->tag.'>';
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

}
