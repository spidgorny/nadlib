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

	/**
	 * It used to be just the "Controller" but menu needs the full path
	 * @var string in a format "TopPage/SubPage/Controller"
	 */
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

	public $liClass = 'list-group-item';

	/**
	 * @var URL
	 */
	public $basePath;

	public $recursive = true;

	/**
	 * @var bool - will control the URL generation as only last path element or '/'-separated path
	 */
	public $useRecursiveURL = true;

	public $useControllerSlug = true;

	public $controllerVarName = 'c';

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
		$rootpath = $this->request->getURLLevels();

		if ($useRouter) {
			$this->current = $rootpath[$level] ? $rootpath[$level] : $this->request->getControllerString();
		} else if ($this->useControllerSlug) {
			if ($rootpath) {
				$this->current = implode('/', $rootpath);
			} else {
				$this->current = $this->request->getControllerString();
			}
		} else {
			$this->current = $this->request->getControllerString();
		}
		//debug($useRouter, $this->useControllerSlug, $rootpath, $level, $this->current);
	}

	/**
	 * Called by the constructor
	 */
	function setBasePath() {
		$useRouter = class_exists('Config')
			? Config::getInstance()->config['Controller']['useRouter']
			: ($this->request->apacheModuleRewrite());
		if ($useRouter) {   // not finished
			$path = new URL();
			$path->clearParams();
		} elseif ($this->useControllerSlug) {
			$path = new URL();
			if (basename(AutoLoad::getInstance()->appRoot) == 'be') {
				$docRoot = $_SERVER['DOCUMENT_ROOT'].$path->documentRoot;
				$appRoot = AutoLoad::getInstance()->appRoot;
				//$commonRoot = URL::getCommonRoot($docRoot, $appRoot);
				$path->setPath($path->documentRoot . '/' . URL::getRelativePath($docRoot, $appRoot) . '/');
			} else {
				$path->setPath($path->documentRoot.'/');
			}
			// commented when using the slug
			//$path->setParam($this->controllerVarName, '');	// forces a link with "?c="
		} else {
			$path = new URL();
			$path->clearParams();
			$path->setParam($this->controllerVarName, '');	// forces a link with "?c="
		}
		$this->basePath = $path;
		nodebug(array(
			'class_exists(Config)' => class_exists('Config'),
			'Config::getInstance()->config[Controller]' => Config::getInstance()->config['Controller'],
			'useRouter' => $useRouter,
			'useControllerSlug' => $this->useControllerSlug,
			'documentRoot' => $path->documentRoot,
			'appRoot' => AutoLoad::getInstance()->appRoot,
			'nadlibRoot' => AutoLoad::getInstance()->nadlibRoot,
			'nadlibRootFromDocRoot' => AutoLoad::getInstance()->nadlibFromDocRoot,
			'current' => $this->current,
			'basePath' => $this->basePath,
		));
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

	function getRootpath() {
		$rootpath = $this->request->getURLLevels();
		$rootpath = array_slice($rootpath, 0, $this->level);	// avoid searching for sub-menu of Dashboard/About
		if (!$rootpath) {                                       // no rewrite, then find the menu with current as a key
			if ($this->items[$this->current]) {                 // if $current is a top-level menu then add it, otherwise search (see below)
				$rootpath = array(
					//$this->current,                           // commented otherwise it will show a corresponding submenu
				);
			}
		}
		//debug($rootpath, sizeof($rootpath), $this->level, $this->current);
		if (sizeof($rootpath) < $this->level) {                 // URL contains only the sub-page without the path, search for it
			foreach ($this->items as $key => $rec) {
				/** @var $rec Recursive */
				//$found = $rec->findPath($this->current);
				if ($rec instanceof Recursive) {
					$children = $rec->getChildren();
					$found = $children[$this->current];
					//debug($children, $found, $key, $this->current);
					if ($found) {
						$rootpath = array(
							$key,
							//$this->current,
						);
						$this->current = $key.'/'.$this->current;
						break;
					}
				}
			}
			//debug($rootpath);
		}
		if ($this->level == 0) {
			$this->current = $this->current;                    // no change
		} elseif ($this->items[$this->current] instanceof Recursive) {
			$this->current = $this->current.'/'.$this->current;
		}
		return $rootpath;
	}

	function render() {
		$content = '';
		if (!is_null($this->level)) {
			$rootpath = $this->getRootpath();
			$itemsOnLevel = $this->getItemsOnLevel($rootpath);
			if ($this->level === 1) {
				nodebug(array(
					'current' => $this->current,
					'sizeof($rootpath)' => sizeof($rootpath),
					'level' => $this->level,
					'rootpath' => $rootpath,
					'itemsOnLevel' => $itemsOnLevel,
				));
			}
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

    /**
     * @param array $items
     * @param array $root
     * @param $level
     * @param null $ulClass
     * @return string
     */
    function renderLevel(array $items, array $root = array(), $level, $ulClass = NULL) {
		$content = '';
		foreach ($items as $class => $name) {
			if ($name) {	// empty menu items indicate menu location for a controller
				$path = $this->getClassPath($class, $root);
				//$renderOnlyCurrentSubmenu = $this->renderOnlyCurrent ? $class == $this->current : true;
				$renderOnlyCurrentSubmenu = $this->renderOnlyCurrent
					? in_array($class, trimExplode('/', $this->current))
					: true;
				$hasChildren = $renderOnlyCurrentSubmenu
					&& $name instanceof Recursive
					&& $name->getChildren();
				$cur = $this->isCurrent($class, $root, $level);
				$activeLIclass = $this->liClass . ($cur	? ' active' : '');
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
				nodebug(array(
					'class' => $class,
					'$this->renderOnlyCurrent' => $this->renderOnlyCurrent,
					'getURLLevels()' => $this->request->getURLLevels(),
					'$this->current' => $this->current,
					'$renderOnlyCurrentSubmenu' => $renderOnlyCurrentSubmenu,
					'$this->recursive' => $this->recursive,
					'hasChildren' => $hasChildren));
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
	 * @param array $subMenu
     * @param $level
     * @return bool
     */
	function isCurrent($class, array $subMenu = array(), $level) {
		if ($class{0} == '?') {	// hack begins
			$parts = trimExplode('/', $_SERVER['REQUEST_URI']);
			//debug($parts, $class);
			if (end($parts) == $class) {
				$ret = true;
			}
		} elseif ($subMenu) {
			$combined = implode('/', $subMenu).'/'.$class;
			$ret = ($this->current == $class)
				|| ($combined == $this->current);
            if($level > 0 && !$ret) {
                $ret = ($subMenu[($level -1)] == $this->current && $class == $this->current);
            }

		} else {
			$ret = $this->current == $class;
		}
		if ($this->level === 1) {
			nodebug(array(
				'class' => $class,
				'subMenu' => $subMenu,
				'combined' => $combined,
				'current' => $this->current,
				'ret' => $ret,
			));
		}
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
				$path = array_merge($root, array($class));
				if ($path && $this->useControllerSlug) {
					$link = $this->basePath . implode('/', $path);
				} else {
					$link = $this->basePath->setParam($this->controllerVarName, $class);
				}
			} else {
				$link = $this->basePath->setParam($this->controllerVarName, $class);
			}
		}
		nodebug(array(
			'class' => $class,
			'root' => $root,
			'path' => $path,
			'useRecursiveURL' => $this->useRecursiveURL,
			'useControllerSlug' => $this->useControllerSlug,
			'basePath' => $this->basePath,
			'link' => $link
		));
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
