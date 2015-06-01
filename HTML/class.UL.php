<?php

class UL {

	var $items = array();

	var $before = '<ul>';

	var $after = '</ul>';

	var $wrap = '<li###ACTIVE###>|</li>';

	/**
	 * Should be equal to an $this->items key which is selected
	 * @var string
	 */
	var $activeClass = '';

	/**
	 * Piece of HTML to mark active items
	 * @var string
	 */
	var $active = ' class="active"';

	var $links = array();

	/**
	 * <a href="###LINK###">|</a>
	 * @var string
	 */
	var $linkWrap = '';

	/**
	 * @var callback to link generation function(index, name)
	 */
	public $linkFunc;

	function __construct(array $items = array()) {
		$this->items = $items;
		$this->activeClass = each($this->items);
	}

	public function add($value, $key = NULL) {
		if ($key) {
			$this->items[$key] = $value;
		} else {
			$this->items[] = $value;
		}
	}

	function render() {
		$out = array();
		foreach ($this->items as $class => $li) {
			if ($this->links) {
				$link = $this->links[$class];
			} elseif ($this->linkFunc) {
				$link = call_user_func($this->linkFunc, $class, $li);
			} elseif ($this->linkWrap) {
				$link = $class;
				$wrap = Wrap::make($this->linkWrap);
				// don't translate __() because the values may come from DB
				$li = $wrap->wrap($li);
			} else {
				$link = NULL;
			}

			$line = Wrap::make($this->wrap)->wrap($li);
			$line = str_replace('%23%23%23LINK%23%23%23', $link, $line);
			$line = str_replace('###LINK###', $link, $line);
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

	public static function DL(array $assoc) {
		$ul = new UL($assoc);
		$links = array_keys($assoc);
		$ul->links = array_combine($links, $links);
		$ul->wrap = '<dt>###CLASS###</dt>';
		$ul->linkWrap = '<dd>|</dd>';
		$ul->before = '<dl class="dl-horizontal">';
		$ul->after = '</dl>';
		return $ul;
	}

}
