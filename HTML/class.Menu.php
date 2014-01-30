<?php

/**
 * Doesn't extend Controller as it makes an infinite loop as a menu is made in Controller::__construct
 */
class Menu /*extends Controller*/ {

	/**
	 * Public for access rights. Will convert to ArrayPlus automatically
	 * @var ArrayPlus
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

	public $current;

	/**
	 * @var User
	 */
	protected $user;

	/**
	 * Will display only the first level of the menu
	 * @var bool
	 */
	public $renderOnlyCurrent = true;

	public $ulClass = 'nav nav-list menu csc-menu list-group';

	/**
	 * @var URL
	 */
	public $basePath;

	public $recursive = true;

	public $useRecursiveURL = true;

	public $useControllerSlug = true;

	function __construct(array $items, $level = NULL) {
		//parent::__construct();
		$this->items = new ArrayPlus($items);
		$this->level = $level;
		$this->request = Request::getInstance();
		//$this->tryInstance();
		if (class_exists('Config')) {
			$this->user = Config::getInstance()->user;
		}
		$this->useControllerSlug = $this->request->apacheModuleRewrite();
		$this->setCurrent($level);
		$this->setBasePath();
	}

	/**
	 * Called by the constructor
	 * @param $level
	 */
	function setCurrent($level) {
		$useRouter = class_exists('Config')
			? Config::getInstance()->config['Controller']['useRouter']
			: '';
		if ($useRouter) {
			$rootpath = $this->request->getURLLevels();
			$this->current = $rootpath[$level] ? $rootpath[$level] : $this->request->getControllerString();
			//debug($rootpath, $level, $this->current);
		} else {
			$this->current = $this->request->getControllerString();
		}
	}

	/**
	 * Called by the constructor
	 */
	function setBasePath() {
		$useRouter = class_exists('Config')
			? Config::getInstance()->config['Controller']['useRouter']
			: ($this->request->apacheModuleRewrite());
		if ($useRouter) {
			$path = new URL();
			$path->clearParams();
		} else {
			$path = new URL();
			$path->clearParams();
			$path->setParam('c', '');	// forces a link with "?c="
		}
		$this->basePath = $path;
		//debug($this->current, $this->basePath);
	}

	/**
	 * Used by AccMailer
	 */
	function filterACL() {
		foreach ($this->items as $class => &$item) {
			if (!$this->user->can($class, '__construct')) {
				unset($this->items[$class]);
			}
		}
	}

	function render() {
		$content = '';
		if (!is_null($this->level)) {
			$rootpath = $this->request->getURLLevels();
			$rootpath = array_slice($rootpath, 0, $this->level);	// avoid searching for submenu of Dashboard/About
			$itemsOnLevel = $this->getItemsOnLevel($rootpath);
			//debug($rootpath, $itemsOnLevel);
			$content .= $this->renderLevel($itemsOnLevel, $rootpath, $this->level);
		} else {
			$content .= $this->renderLevel($this->items->getData(), array(), 0);
		}
		return $content;
	}

	/**
	 * Will retrieve the sub-elements on the specified path
	 * @param array $rootpath
	 * @return array
	 */
	protected function getItemsOnLevel(array $rootpath) {
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
					//$o = new $class();							// BAD instantiating
					//if (method_exists($o, 'getMenuSuffix')) {
					$methods = get_class_methods($class);
					//if ($class == 'AssignHardware') debug($class, $methods, in_array('getMenuSuffix', $methods));
					if (in_array('getMenuSuffix', $methods)) {
						$o = new $class();
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

	function renderLevel(array $items, array $root = array(), $level, $ulClass = NULL) {
		$content = '';
		foreach ($items as $class => $name) {
			if ($name) {	// empty menu items indicate menu location for a controller
				$path = $this->getClassPath($class, $root);
				//$renderOnlyCurrentSubmenu = $this->renderOnlyCurrent ? $class == $this->current : true;
				$renderOnlyCurrentSubmenu = $this->renderOnlyCurrent
					? in_array($class, $this->request->getURLLevels())
					: true;
				$hasChildren = $renderOnlyCurrentSubmenu
					&& $name instanceof Recursive
					&& $name->getChildren();
				$cur = $this->isCurrent($class, $root);
				$activeLIclass = $cur	? 'list-group-item active' : 'list-group-item';
				$activeAclass  = $cur 	? 'act' : '';
				if ($name instanceof HTMLTag) {
					$aTag = $name.'';
				} else {
					if ($hasChildren) {
						$activeLIclass .= ' dropdown';
						$activeAclass .= ' dropdown-toggle';
						$aTag = '<a href="'.$path.'" class="'.$activeAclass.'" data-toggle="dropdown">'.__($name.'').' <b class="caret"></b></a>'."\n";
					} else {
						$aTag = '<a href="'.$path.'" class="'.$activeAclass.'">'.__($name.'').'</a>'."\n";
					}
				}
				if ($this->recursive && $hasChildren) {
					$root_class = array_merge($root, array($class));
					$contentSubmenu = $this->renderLevel($items[$class]->getChildren(), $root_class, $level+1, 'dropdown-menu');
				} else {
					$contentSubmenu = '';
				}
				$content .= new HTMLTag('li', array(
					'class' => $activeLIclass,
				), $aTag.$contentSubmenu, true)."\n";
			}
		}
		//debug($this->current);
		$content = '<ul class="'.($ulClass ? $ulClass : $this->ulClass).'">'.$content.'</ul>';
		return $content;
	}

	/**
	 * For http://appdev.nintendo.de/~depidsvy/posaCards/ListSales/ChartSales/BreakdownTotal/?filter[id_country]=2
	 * to work we need to split by '/' not only the path but also parameters
	 * @param string $class
	 * @param $subMenu
	 * @return bool
	 */
	function isCurrent($class, $subMenu) {
		if ($class{0} == '?') {	// hack begins
			$parts = trimExplode('/', $_SERVER['REQUEST_URI']);
			//debug($parts, $class);
			if (end($parts) == $class) {
				$ret = true;
			}
		} elseif ($subMenu) {
			$ret = (implode('/', $subMenu).'/'.$class) == $this->current;
		} else {
			$ret = $this->current == $class;
		}
		//debug($class, $subMenu, $this->current, $ret);
		return $ret;
	}

	/**
	 * Finds the path to the menu item inside the menu tree
	 * @param $class
	 * @param array $root
	 * @return string
	 */
	function getClassPath($class, array $root) {
		if (startsWith($class, 'http')) {
			return $class;
		} else {
			if ($this->useRecursiveURL) {
				//$path = $this->items->find($class);
				//debug($class, $path);
				$path = array_merge($root, array($class));
			//if ($path) {
			if ($path && $this->useControllerSlug) {
				$link = $this->basePath . implode('/', $path);
				} else {
				$link = $this->basePath . $class;
				}
			} else {
			$link = $this->basePath . $class;
			}
		}
		//debug($class, $root, $path, $this->useControllerSlug, $this->basePath, $link);
		return $link;
	}

	function __toString() {
		return $this->render().'';
	}

	/**
	 * ACL. Constructs each menu object and reacts on access denied exception
	 */
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
