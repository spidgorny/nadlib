<?php

/**
 * Doesn't extend Controller as it makes an infinite loop as a menu is made in Controller::__construct
 */
class Menu /*extends Controller*/ {

	/**
	 * Public for access rights
	 * @var array of Recursive
	 */
	public $items = array(
		'default' => 'Default Menu Item',
	);
	/**
	 * Set to not NULL to see only specific level
	 * @var int|null
	 */
	public $level = NULL;

	/**
	 * Set to call getMenuSuffix() on each object in menu
	 * @var bool
	 */
	public $tryMenuSuffix = false;

	protected $current;
	
	function __construct(array $items, $level = NULL) {
		//parent::__construct();
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
		if (Config::getInstance()->user->id) {
			if (!is_null($this->level)) {
				$rootpath = $this->request->getURLLevels();
				$rootpath = array_slice($rootpath, 0, $this->level);	// avoid searching for submenu of Dashboard/About
				$itemsOnLevel = $this->getItemsOnLevel($rootpath);
				$content .= $this->renderLevel($itemsOnLevel, $rootpath, $this->level, false);
			} else {
				$content .= $this->renderLevel($this->items, array(), 0, true);
			}
		}
		return $content;
	}

	function getItemsOnLevel(array $rootpath) {
		$fullRecurive = new Recursive(NULL, $this->items);
		$sub = $fullRecurive->findPath($rootpath);
		if ($sub instanceof Recursive) {
			$items = $sub->getChildren();
		} else {
			$items = array();
		}

		if ($this->tryMenuSuffix) {
			foreach ($items as $class => &$name) {
				try {
					$o = new $class();
					if (method_exists($o, 'getMenuSuffix')) {
						$name .= call_user_func(array($o, 'getMenuSuffix'));
					}
				} catch (AccessDeniedException $e) {
					unset($items[$class]);
				}
			}
		}

		if (-1 == $this->level) debug(array(
			'level' => $this->level,
			'rootpath' => $rootpath,
			'sub' => $sub,
			'items' => $items
		));
		return $items;
	}

	function renderLevel(array $items, array $root = array(), $level, $isRecursive = true) {
		$content = '';
		//$this->current = $this->request->getControllerString();
		$rootpath = $this->request->getURLLevels();
		$this->current = $rootpath[$level] ?: $this->request->getControllerString();
		//debug($rootpath, $level, $this->current);
		foreach ($items as $class => $name) {
			$actInA = in_array($class, $current) ? ' class="act"' : '';
			$active = $this->current == $class ? ' class="active"' : '';
			if ($class != $root[0]) {
				$path = array_merge($root, array($class));
			} else {
				$path = array($class);
			}
			$path = implode('/', $path);
			$content .= '<li '.$active.'><a href="'.$path.'"'.$actInA.'>'.__($name).'</a></li>';

			if ($isRecursive && $class == $this->current && is_object($items[$class]) && $items[$class]->getChildren()) {
				$root_class = array_merge($root, array($class));
				$content .= $this->renderLevel($items[$class]->getChildren(), $root_class, $level+1, $isRecursive);
			}
		}
		$content = '<ul class="nav nav-list menu">'.$content.'</ul>';
		return $content;
	}

	function __toString() {
		return $this->render().'';
	}

	function tryInstance() {
		foreach ($this->items as $class => $_) {
			try {
				new $class;
			} catch (Exception $e) {
				unset($this->items[$class]);
			}
		}
	}

}
