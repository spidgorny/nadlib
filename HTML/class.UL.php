<?php

class UL {

	var $items = array();

	var $before = '<ul>';

	var $after = '</ul>';

	var $wrap = '<li class="###ACTIVE###">|</li>';

	var $activeClass = '';

	var $active = 'class="active"';

	var $links = array();

	function __construct(array $items) {
		$this->items = $items;
		$this->activeClass = each($this->items);
	}

	function render() {
		$out = array();
		foreach ($this->items as $class => $li) {
			if ($this->links) {
				$link = $this->links[$class];
				if ($link) {
					$wrap = Wrap::make('<a href="'.$link.'">|</a>');
				} else {
					$wrap = Wrap::make('<a>|</a>');
				}
				$li = $wrap->wrap($li);
			}
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
