<?php

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
		return '<'.$this->tag.' '.$this->renderAttr().'>'.$content.'</'.$this->tag.'>';
	}

	function renderAttr() {
		$set = array();
		foreach ($this->attr as $key => $val) {
			$set[] = $key.'="'.htmlspecialchars($val, ENT_QUOTES).'"';
		}
		return implode(' ', $set);
	}

}