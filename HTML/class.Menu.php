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

	/**
	 * @var User
	 */
	protected $user;

	public $renderOnlyCurrent = true;

	public $ulClass = 'nav nav-list menu csc-menu';

	function __construct(array $items, $level = NULL) {
		//parent::__construct();
		$this->items = new ArrayPlus($items);
		$this->level = $level;
		$this->request = Request::getInstance();
		//$this->tryInstance();
		$this->user = Config::getInstance()->user;
	}

	function filterACL() {
		foreach ($this->items as $class => &$item) {
			if (!$this->user->can($class, '__construct')) {
				unset($this->items[$class]);
			}
		}
	}

	function render() {
		$content = '';
		//if ($this->user && $this->user->id) {
			if (!is_null($this->level)) {
				$rootpath = $this->request->getURLLevels();
				$rootpath = array_slice($rootpath, 0, $this->level);	// avoid searching for submenu of Dashboard/About
				$itemsOnLevel = $this->getItemsOnLevel($rootpath);
				$content .= $this->renderLevel($itemsOnLevel, $rootpath, $this->level, false);
			} else {
				$content .= $this->renderLevel($this->items->getData(), array(), 0, true);
			}
		//}
		return $content;
	}

	function getItemsOnLevel(array $rootpath) {
		$fullRecursive = new Recursive(NULL, $this->items->getData());
		$sub = $fullRecursive->findPath($rootpath);
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

	function renderLevel(array $items, array $root = array(), $level, $isRecursive = true, $ulClass = NULL) {
		$content = '';
		//$this->current = $this->request->getControllerString();
		$rootpath = $this->request->getURLLevels();
		$this->current = $rootpath[$level] ? $rootpath[$level] : $this->request->getControllerString();
		//debug($rootpath, $level, $this->current);
		foreach ($items as $class => $name) {
			if ($name) {	// empty menu items indicate menu location for a controller
				if (isset($root[0]) && ($class != $root[0])) {
					$path = array_merge($root, array($class));
				} else {
					$path = array($class);
				}
				if (Config::getInstance()->config['Controller']['useRouter']) {
					$path = implode('/', $path);
				} else {
					$path = new URL();
					$path->clearParams();
					$path->setParam('c', $class);
				}

				$renderOnlyCurrentSubmenu = $this->renderOnlyCurrent ? $class == $this->current : true;
				$hasChildren = $renderOnlyCurrentSubmenu && is_object($name) && $name->getChildren();
				$actInA = $this->current == $class ? 'act' : '';
				$active = $this->current == $class ? 'active' : '';
				if ($hasChildren) {
					$active .= ' dropdown';
					$actInA .= ' dropdown-toggle';
					$aTag = '<a href="'.$path.'" class="'.$actInA.'" data-toggle="dropdown">'.__($name.'').' <b class="caret"></b></a>';
				} else {
					$aTag = '<a href="'.$path.'" class="'.$actInA.'">'.__($name.'').'</a>';
				}
				if ($isRecursive && $hasChildren) {
					$root_class = array_merge($root, array($class));
					$contentSubmenu = $this->renderLevel($items[$class]->getChildren(), $root_class, $level+1, $isRecursive, 'dropdown-menu');
				} else {
					$contentSubmenu = '';
				}
				$content .= new HTMLTag('li', array(
					'class' => $active,
				), $aTag.$contentSubmenu, true);
			}
		}
		$content = '<ul class="'.($ulClass ? $ulClass : $this->ulClass).'">'.$content.'</ul>';
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
