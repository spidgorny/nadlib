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

	public $menuTag = 'ul';

	public $ulClass = 'nav nav-list nav-pills nav-stacked menu csc-menu list-group';

	public $itemTag = 'li';

	/**
	 * list-group-item for bootstrap 1/2
	 * @var string
	 */
	public $liClass = '';

	public $normalAClass = '';

	public $activeAClass = 'act';

	/**
	 * @var URL
	 */
	public $basePath;

	public $recursive = true;

	/**
	 * @var bool - will control the URL generation as only last path element or '/'-separated path
	 * This is required for $useControllerSlug to work
	 */
	public $useRecursiveURL = true;

	/**
	 * @var bool
	 * TRUE: explode('/', $path)
	 * FALSE: Request::getControllerSting()
	 * Call setBasePath() after changing this to make an effect
	 */
	public $useControllerSlug = true;

	public $controllerVarName = 'c';

	function __construct(array $items, $level = NULL) {
		//parent::__construct();
		$this->items = new ArrayPlus($items);
		$this->level = $level;
		$this->request = Request::getInstance();
		//$this->tryInstance();
		if (class_exists('Config')) {
			$config = Config::getInstance();
			$this->user = $config->getUser();

			$index = Index::getInstance();
			$controller = ifsetor($index->controller);
			if ($controller && isset($controller->useRouter)) {
				$this->useControllerSlug = $controller->useRouter;
			} else {
				//debug(get_class($controller));
				$this->useControllerSlug = $this->request->apacheModuleRewrite();
			}
		}
		$this->setCurrent($level);
		$this->setBasePath();
	}

	/**
	 * Called by the constructor
	 * @param $level
	 */
	function setCurrent($level) {
		if (class_exists('Config')) {
			$config = Config::getInstance();
			$useRouter = isset($config->config['Controller'])
					? ifsetor($config->config['Controller']['useRouter'])
					: NULL;
		} else {
			$useRouter = NULL;
		}
		$rootpath = $this->request->getURLLevels();

		if ($useRouter) {
			$this->current = $rootpath[$level]
					? $rootpath[$level]
					: $this->request->getControllerString();
		} else if ($this->useControllerSlug) {
			if ($rootpath) {
				$this->current = implode('/', $rootpath);
			} else {
				$this->current = $this->request->getControllerString();
			}
		} else {
			$this->current = $this->request->getControllerString();
		}
		nodebug([
			'cwd' => getcwd(),
			'docRoot' => $this->request->getDocumentRoot(),
			'getPathAfterDocRoot' => $this->request->getPathAfterDocRoot(),
			'useRouter' => $useRouter,
			'useControllerSlug' => $this->useControllerSlug,
			'rootpath' => $rootpath,
			'level' => $level,
			'current' => $this->current
		]);
	}

	function setControllerVarName($c) {
		$this->controllerVarName = $c;
		$this->setBasePath();
	}

	/**
	 * Called by the constructor
	 */
	function setBasePath() {
		if (class_exists('Config')) {
			$config = Config::getInstance();
			$useRouter = (isset($config->config['Controller']))
					? $config->config['Controller']['useRouter']
					: ($this->request->apacheModuleRewrite() && class_exists('Router'));
		} else {
			$config = new stdClass();
			$useRouter = false;
		}
		$autoLoad = AutoLoad::getInstance();
		if ($useRouter) {   // not finished
			$path = new URL();
			$path->clearParams();
		} elseif ($this->useControllerSlug) {
			$path = new URL();
			$appRoot = $autoLoad->getAppRoot();
			if (basename($appRoot) == 'be') {
				$docRoot = $_SERVER['DOCUMENT_ROOT'].$path->documentRoot;
				//$commonRoot = URL::getCommonRoot($docRoot, $appRoot);
				$path->setPath(cap($path->documentRoot . '/' . URL::getRelativePath($docRoot, $appRoot)));
				$path->setParams();
			} else {
				$path->setPath(cap($path->documentRoot));
				$path->setParams();
			}
			// commented when using the slug
			//$path->setParam($this->controllerVarName, '');	// forces a link with "?c="
		} else {
			$path = new URL();
			$path->clearParams();
			if ($this->controllerVarName) {
				$path->setParam($this->controllerVarName, '');    // forces a link with "?c="
			}
		}
		$this->basePath = $path;
		0 && debug(array(
			'class_exists(Config)' => class_exists('Config'),
			'Config::getInstance()->config[Controller]' => (class_exists('Config') && isset($config->config['Controller']))
				? $config->config['Controller']
				: NULL,
			'useRouter' => $useRouter,
			'useControllerSlug' => $this->useControllerSlug,
			'documentRoot' => $path->documentRoot,
			'appRoot' => $appRoot.'',
			'nadlibRoot' => $autoLoad->nadlibRoot,
			'nadlibRootFromDocRoot' => $autoLoad->nadlibFromDocRoot,
			'current' => $this->current,
			'basePath' => $this->basePath.'',
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
		if ($this->useRecursiveURL) {
			$rootPath = $this->request->getURLLevels();
			$rootPath = array_slice($rootPath, 0, $this->level); // avoid searching for sub-menu of Dashboard/About
			if (!$rootPath) { // no rewrite, then find the menu with current as a key
				if (ifsetor($this->items[$this->current])) { // if $current is a top-level menu then add it, otherwise search (see below)

					if ($this->level > 0) {
						$rootPath = array(
							$this->current,   // commented otherwise it will show a corresponding submenu
						);
					}


				}
			}
			//debug($rootpath, sizeof($rootpath), $this->level, $this->current);
			if (sizeof($rootPath) < $this->level) { // URL contains only the sub-page without the path, search for it
				$found = $this->items->find($this->current);
				if ($found) {
					$rootPath = array(
						$found,
						//$this->current,
					);
					$this->current = $found . '/' . $this->current;
				}
				//debug($rootpath);
			}
			if ($this->level == 0) {
				$this->current = $this->current; // no change
			} elseif (ifsetor($this->items[$this->current]) instanceof Recursive) {
				$this->current = $this->current . '/' . $this->current;
			}
		} else {
			$controller = $this->request->getControllerString();
			if (ifsetor($this->items[$controller])) {
				$rootPath = array($controller);
			} else {    // search inside
				$rootPath = $this->items->find($controller);
				$rootPath = array(first($rootPath));    // needed for getItemsOnLevel
			}
		}
		return $rootPath;
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
					if ($methods && in_array('getMenuSuffix', $methods)) {
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
				$renderOnlyCurrentSubMenu = $this->renderOnlyCurrent
					? in_array($class, trimExplode('/', $this->current))
					: true;
				$hasChildren = $renderOnlyCurrentSubMenu
					&& $name instanceof Recursive
					&& $name->getChildren();
				$cur = $this->isCurrent($class, $root, $level);
				$activeLIclass = $this->liClass . ($cur	? ' active' : '');
				$activeAclass  = $cur ? $this->activeAClass : $this->normalAClass;
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
					'$renderOnlyCurrentSubMenu' => $renderOnlyCurrentSubMenu,
					'$this->recursive' => $this->recursive,
					'hasChildren' => $hasChildren));
				if ($this->recursive && $hasChildren) {
					$root_class = array_merge($root, array($class));
					/** @var Recursive $subItem */
					$subItem = $items[$class];
					$contentSubMenu = $this->renderLevel($subItem->getChildren(), $root_class, $level+1, 'dropdown-menu');
				} else {
					$contentSubMenu = '';
				}
				if ($this->itemTag) {
					$content .= new HTMLTag($this->itemTag, array(
						'class' => $activeLIclass,
					), $aTag . $contentSubMenu, true) . "\n";
				} else {
					$content .= $aTag . $contentSubMenu;
				}
			}
		}
		//debug($this->current);
		$content = '<'.$this->menuTag.' class="'.($ulClass ? $ulClass : $this->ulClass).'">'.$content.'</'.$this->menuTag.'>';
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
		$ret = false;
		$combined = NULL;
		if ($class{0} == '?') {	// hack begins
			$parts = trimExplode('/', $_SERVER['REQUEST_URI']);
			//debug($parts, $class);
			if (end($parts) == $class) {
				$ret = true;
			} else {
				$ret = NULL;
			}
		} elseif ($subMenu) {
			$combined = implode('/', $subMenu).'/'.$class;
			$ret = ($this->current == $class)
				|| ($combined == $this->current);
            if ($level > 0 && !$ret) {
                $ret = ($subMenu[($level -1)] == $this->current && $class == $this->current);
            }
		} elseif (contains($class, '/')) {
			$classWithoutSlash = trimExplode('/', $class);
			$classWithoutSlash = $classWithoutSlash[0];
			$ret = $this->current == $classWithoutSlash;
		} else {
			$ret = $this->current == $class;
		}
		//if ($this->level === 0) {
			nodebug(array(
				'class' => $class,
				'class{0}' => $class{0},
				'subMenu' => $subMenu,
				'combined' => $combined,
				'current' => $this->current,
				'contains /' => contains($class, '/'),
				'ret' => $ret,
			));
		//}
		return $ret;
	}

	/**
	 * Finds the path to the menu item inside the menu tree
	 * @param $class
	 * @param array $root
	 * @return string
	 */
	function getClassPath($class, array $root) {
		if (str_startsWith($class, 'http')) {
			return $class;
		} else {
			if ($this->useRecursiveURL) {
				$path = array_merge($root, array($class));
			} else {
				$path = array($class);
			}

			if ($path && $this->useControllerSlug) {
				if ($this->useRecursiveURL) {
					$link = cap($this->basePath) . implode('/', $path);
				} else {
					$link = $this->basePath;
					$link->replaceController($path);
				}
			} else {
				if ($class[0] == '#') {
					$link = $this->basePath->setFragment($class);
				} else {
					$link = $this->basePath->setParam($this->controllerVarName, $class);
				}
			}
		}
		0 && debug(array(
			'class' => $class,
			'root' => $root,
			'path' => $path,
			'useRecursiveURL' => $this->useRecursiveURL,
			'useControllerSlug' => $this->useControllerSlug,
			'basePath' => $this->basePath.'',
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

	function renderBreadcrumbs() {
		$ul = new UL($this->items->getData());
		$ul->links = $this->items->getKeys()->getData();
		$ul->links = array_combine($ul->links, $ul->links);
		$ul->linkWrap = '<a href="###LINK###">|</a>';
		$ul->before = '<ol class="breadcrumb">';
		$ul->after = '</ol>';
		if ($ul->links) {
			return $ul;
		} else {
			return NULL;
		}
	}

}
