<?php

class Menu extends Controller {

	/**
	 * @var array of Recursive
	 */
	protected $items = array(
		'default' => 'Default Menu Item',
	);
	/**
	 * Set to not NULL to see only specific level
	 * @var int|null
	 */
	public $level = NULL;

	protected $current;
	
	function __construct(array $items, $level = NULL) {
		parent::__construct();
		$this->items = $items;
		$this->level = $level;
		$this->request = new Request();
		//$this->tryInstance();
	}

	function filterACL() {
		$user = Config::getInstance()->user;
		foreach ($this->items as $class => &$item) {
			if (!$user->can($class, '__construct')) {
				unset($this->items[$class]);
			}
		}
	}

	function render() {
		$content = '';
		if (!is_null($this->level)) {
			$itemsOnLevel = $this->getItemsOnLevel();
			$content .= $this->renderLevel($itemsOnLevel, array(), $this->level, false);
		} else {
			$content .= $this->renderLevel($this->items, array(), 0, true);
		}
		return $content;
	}

	function getItemsOnLevel() {
		$this->current = $this->request->getURLLevel($this->level);
		debug($this->level, $this->current);
		if ($this->level) {
			$sub = $this->items[$this->current];
			$sub = $sub instanceof Recursive ? $sub->getChildren() : array();
		} else {
			$sub = $this->items;
		}
		//debug($sub);
		return $sub;
	}

	function renderLevel(array $items, array $root = array(), $level, $isRecursive = true) {
		$content = '';
		//$current = $this->request->getControllerString();
		//debug($this->level, $current, $level);
		foreach ($items as $class => $name) {
			$act = $this->current == $class ? ' class="act"' : '';
			$active = $this->current == $class ? ' class="active"' : '';
			if ($class != $root[0]) {
				$path = array_merge($root, array($class));
			} else {
				$path = array($class);
			}
			$path = implode('/', $path);
			$content .= '<li '.$active.'><a href="'.$path.'"'.$act.'>'.__($name).'</a></li>';

			if ($isRecursive && $class == $this->current && is_object($items[$class]) && $items[$class]->getChildren()) {
				$root_class = array_merge($root, array($class));
				$content .= $this->renderLevel($items[$class]->getChildren(), $root_class, $level+1);
			}
		}
		$content = '<ul class="nav nav-list menu">'.$content.'</ul>';
		return $content;
	}

	function __toString() {
		return $this->render();
	}

	function tryInstance() {
		foreach ($this->items as $class => $_) {
			try {
				$o = new $class;
			} catch (Exception $e) {
				unset($this->items[$class]);
			}
		}
	}

}
