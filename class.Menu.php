<?php

class Menu extends Controller {

	/**
	 * @var array of Recursive
	 */
	protected $items = array();
	
	function __construct(array $items, $level = 0) {
		parent::__construct();
		$this->items = $items;
		$this->level = $level;
	}
	
	function render() {
		$content = '';
		if (!$this->level) {
			$content .= $this->renderLevel($this->items);
		} else {
			$key = $this->request->getURLLevel(0);
			$subMenu = $this->items[$key];
			if ($subMenu) {
				$subMenu = $this->items[$key]->getChildren();
				if (is_array($subMenu)) {
					$content .= $this->renderLevel($subMenu, array($key));
				} else {
					$content .= '<div class="error">No submenu in '.$key.'</div>';
				}
			}
		}
		return $content;
	}

	function renderLevel(array $items, array $root = array()) {
		$content = '';
		$current = $this->request->getURLLevel($this->level);
		$current = $current ? $current : $root[0];
		//debug($this->level, $current);
		foreach ($items as $class => $name) {
			$act = $current == $class ? ' class="act"' : '';
			if ($class != $root[0]) {
				$path = array_merge($root, array($class));
			} else {
				$path = array($class);
			}
			$path = implode('/', $path);
			$content .= '<li><a href="'.$path.'"'.$act.'>'.$name.'</a></li>';
		}
		$content = '<ul>'.$content.'</ul>';
		return $content;
	}

	function __toString() {
		return $this->render();
	}

}
