<?php

class UL {

	var $items = array();

	var $before = '<ul>';

	var $after = '</ul>';

	var $wrap = '<li###ACTIVE###>|</li>';

	var $activeClass = '';

	var $active = ' class="active"';

	var $links = array();

	var $linkWrap = '<a href="###LINK###">|</a>';

	/**
	 * @var callback to link generation function(index, name)
	 */
	public $linkFunc;

	function __construct(array $items) {
		$this->items = $items;
		$this->activeClass = each($this->items);
	}

	function render() {
		$out = array();
		foreach ($this->items as $class => $li) {
			if ($this->links) {
				$link = $this->links[$class];
			} else if ($this->linkFunc) {
				$link = call_user_func($this->linkFunc, $class, $li);
			}
			if ($link) {
				$wrap = Wrap::make(str_replace('###LINK###', $link, $this->linkWrap));
			} else {
				//$wrap = Wrap::make('<a>|</a>');
				$wrap = Wrap::make('|');
			}
			$li = $wrap->wrap(__($li));

			$line = Wrap::make($this->wrap)->wrap($li);
			$line = str_replace('###CLASS###', $class, $line);
			$line = str_replace('###TEXT###', $li, $line);
			$line = str_replace('###ACTIVE###', $class == $this->activeClass ? $this->active : '', $line);
			$out[] = $line;
		}
		$content = $this->before . implode("\n", $out) . $this->after;
		return $content;
	}

	function __toString() {
		return $this->render();
	}

}
