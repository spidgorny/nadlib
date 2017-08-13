<?php

/**
 * Class Controller - a base class for all front-facing pages.
 * Extend and implement your own render() function.
 * It should collect output into a string and return it.
 * Additional actions can be processed by calling
 * $this->performAction() from within render().
 * It checks for the &action= parameter and appends an 'Action' suffix to get the function name.
 *
 * Can be called from CLI with parameters e.g.
 * > php index.php SomeController -action cronjob
 * will call cronjobAction instead of default render()
 */

abstract class Controller {

	//use HTMLHelper;	// bijou is PHP 5.4

	/**
	 * @var Index
	 */
	public $index;

	/**
	 * @var Request
	 */
	public $request;

	/**
	 * @var boolean
	 * @use $this->preventDefault() to set
	 * Check manually in render()
	 */
	public $noRender = false;

	/**
	 * @var MySQL|DBLayer|DBLayerMS|DBLayerPDO|DBLayerSQLite|DBLayerBase
	 */
	protected $db;

	/**
	 * Will be taken as a <title> of the HTML table
	 * @var string
	 */
	public $title;

	/**
	 * Will be set according to mod_rewrite
	 * Override in __construct()
	 * @public to be accessed from Menu
	 * @var bool
	 */
	public $useRouter = false;

	/**
	 * @var User|Client|userMan|LoginUser
	 */
	public $user;

	/**
	 * Instance per class
	 * @var Controller[]
	 */
	static protected $instance = array();

	/**
	 * Allows selecting fullScreen layout of the template
	 *
	 * @var string|Wrap
	 */
	public $layout;

	public $linkVars = array();

	public $encloseTag = 'h2';

	/**
	 * accessible without login
	 * @var bool
	 */
	static public $public = false;

	/**
	 * @var Config
	 */
	public $config;

	/**
	 * @var AutoLoad
	 */
	protected $al;

	var $log = array();

	/**
	 * @var HTML
	 */
	var $html;

	/**
	 * Used by Collection to get the current sorting method.
	 * Ugly, please reprogram.
	 * @var
	 */
	public $sortBy;

	function __construct()
	{
		if (ifsetor($_REQUEST['d']) == 'log') echo get_class($this) . '::' . __METHOD__ . BR;
		$this->index = class_exists('Index', false)
			? Index::getInstance(false) : NULL;
		$this->request = Request::getInstance();
		$this->useRouter = $this->request->apacheModuleRewrite();
		$this->al = AutoLoad::getInstance();
		if (!is_object($this->config) && class_exists('Config')) {
			$this->config = Config::getInstance();
			$this->db = $this->config->getDB();
			$this->user = $this->config->getUser();
			//debug($this->user);
			$this->config->mergeConfig($this);
		} else {
			/** @var Config config */
			// $this->config = NULL;
			//$this->user = new UserBase();
		}
		if (!$this->useRouter) {
			$this->linkVars['c'] = get_class($this);
		}
		$this->title = $this->title ? $this->title : get_class($this);
		//debug_pre_print_backtrace();
		if ($this->config->ll) {
			$this->title = $this->title ? __($this->title) : $this->title;
		}
		$this->html = new HTML();
		self::$instance[get_class($this)] = $this;
	}

	/**
	 * @param array|string $params
	 * @param null $prefix
	 * @return URL
	 * @public for View::link
	 * @use getURL()
	 */
	function makeURL(array $params, $prefix = NULL)
	{
		if (!$prefix && $this->useRouter) { // default value is = mod_rewrite
			$class = ifsetor($params['c']);
			if ($class && !$prefix) {
				unset($params['c']);    // RealURL
				$prefix = $class;
			} else {
				$class = NULL;
			}
		} else {
			$class = NULL;
			// this is the only way to supply controller
			//unset($params['c']);
		}

		$location = $this->request->getLocation();
		$url = new URL($prefix
			? $location.$prefix
			: $location, $params);
		$path = $url->getPath();
		if ($this->useRouter && $class) {
			$path->setFile($class);
			$path->setAsFile();
		}
		//debug($prefix, get_class($path));
		$url->setPath($path);
		nodebug(array(
			'method' => __METHOD__,
			'params' => $params,
			'prefix' => $prefix,
			'useRouter' => $this->useRouter,
			'class' => $class,
			'class($url)' => get_class($url),
			'class($path)' => get_class($path),
			'$this->linkVars' => $this->linkVars,
			'return' => $url . '',
			'location' => $location .'',
		));
		return $url;
	}

	/**
	 * Only appends $this->linkVars to the URL.
	 * Use this one if your linkVars is defined.
	 * @param array $params
	 * @param string $page
	 * @return URL
	 */
	function makeRelURL(array $params = array(), $page = NULL)
	{
		return $this->makeURL(
			$params                            // 1st priority
			+ $this->getURL()->getParams()            // 2nd priority
			+ $this->linkVars, $page);                // 3rd priority
	}

	/**
	 * Combines params with $this->linkVars
	 * Use makeURL() for old functionality
	 * @return URL
	 */
	public function getURL(array $params = [], $prefix = NULL) {
		if ($params || $prefix) {
			throw new InvalidArgumentException('User makeURL() instead of '.__METHOD__);
		}
//		$params = $params + $this->linkVars;
//		debug($params);
//		return $this->makeURL($params, $prefix);
		return ClosureCache::getInstance(spl_object_hash($this), function () {
			return new URL();
		})->get();
	}

	/**
	 * Returns '<a href="$page?$params" $more">$text</a>
	 * @param $text
	 * @param array $params
	 * @param string $page
	 * @param array $more
	 * @param bool $isHTML
	 * @return HTMLTag
	 */
	function makeLink($text, array $params, $page = '', array $more = array(), $isHTML = false)
	{
		//debug($text, $params, $page, $more, $isHTML);
		$content = new HTMLTag('a', array(
				'href' => $this->makeURL($params, $page),
			) + $more, $text, $isHTML);
		return $content;
	}

	function makeAjaxLink($text, array $params, $div, $jsPlus = '', $aMore = array(), $prefix = '')
	{
		$url = $this->makeURL($params, $prefix);
		$link = new HTMLTag('a', $aMore + array(
				'href' => $url,
				'onclick' => '
			jQuery(\'#' . $div . '\').load(\'' . $url . '\');
			return false;
			' . $jsPlus,
			), $text, true);
		return $link;
	}

	/**
	 * @param array $data
	 * @return array
	 * @deprecated
	 * @see slTable::showAssoc()
	 */
	function getAssocTable(array $data)
	{
		$table = array();
		foreach ($data as $key => $val) {
			$table[] = array('key' => $key, 'val' => $val);
		}
		return $table;
	}

	static function getInstance()
	{
		$static = get_called_class();
		//if ($static == 'Controller') throw new Exception('Unable to create Controller instance');
		$isset = isset(self::$instance[$static]);
		//debug(array_keys(self::$instance), $static, $isset);
		if ($isset) {
			$result = self::$instance[$static];
		} else {
			$index = Index::getInstance();
			if ($index->controller instanceof $static) {
				$result = $index->getController();
			} else {
				$result = new $static;
			}
		}
		//debug($isset, get_class($index), get_class($result));
		return $result;
	}

	/*function redirect($url) {
		if (DEVELOPMENT) {
			return '<script>
				setTimeout(function() {
					document.location.replace("'.str_replace('"', '&quot;', $url).'");
				}, 5000);
			</script>';
		} else {
			return '<script> document.location.replace("'.str_replace('"', '&quot;', $url).'"); </script>';
		}
	}*/

	function render()
	{
		$content[] = $this->performAction();
		return $content;
	}

	/**
	 * This function prevents performAction() from doing nothing
	 * if there is a __CLASS__.phtml file in the same folder
	 * @return MarkdownView|string|View
	 */
	function indexAction()
	{
		$content = $this->renderTemplate();
		$content = $this->div($content, str_replace('\\', '-', get_class($this)));
		return $content;
	}

	function renderTemplate()
	{
		$filePHTML = get_class($this) . '.phtml';
		$fileMD = get_class($this) . '.md';

		$reflector = new ReflectionClass(get_class($this));
		$classDir = dirname($reflector->getFileName());
		if (file_exists('template/' . $filePHTML)) {
			$content = new View($filePHTML, $this);
		} elseif (file_exists('template/' . $fileMD)) {
			$content = new MarkdownView($fileMD, $this);
		} elseif (file_exists($classDir . '/' . $filePHTML)) {
			$content = new View($classDir . '/' . $filePHTML, $this);
		} elseif (file_exists($classDir . '/' . $fileMD)) {
			$content = new MarkdownView($classDir . '/' . $fileMD, $this);
		} else {
			$content = '';
		}

//		debug($filePHTML, $fileMD);

		return is_object($content)
			? $content->render()
			: $content;
	}

	function __toString()
	{
		return $this->s($this->render());
	}

	function encloseIn($title, $content)
	{
		$title = $title instanceof htmlString ? $title : htmlspecialchars($title);
		$content = $this->s($content);
		return '<fieldset><legend>' . $title . '</legend>' . $content . '</fieldset>';
	}

	/**
	 * Wraps the content in a div/section with a header.
	 * The header is linkable.
	 * @param $content
	 * @param string $caption
	 * @param null $h
	 * @param array $more
	 * @return array|string
	 */
	function encloseInAA($content, $caption = '', $h = NULL, array $more = array())
	{
		$h = $h ? $h : $this->encloseTag;
		$content = $this->s($content);
		if ($caption) {
			$content = array(
				'caption' => $this->getCaption($caption, $h),
				$content
			);
		}
		$more['class'] = ifsetor($more['class'], 'padding clearfix');
		$more['class'] .= ' ' . get_class($this);
		//debug_pre_print_backtrace();
		//$more['style'] = "position: relative;";	// project specific
		$content = new HTMLTag('section', $more, $content, true);
		return $content;
	}

	function encloseInToggle($content, $title, $height = 'auto', $isOpen = NULL, $tag = 'h3')
	{
		if ($content) {
			// buggy: prevents all clicks on the page in KA.de
			$nadlibPath = AutoLoad::getInstance()->nadlibFromDocRoot;
			$this->index->addJQuery();
			$this->index->addJS($nadlibPath . 'js/showHide.js');
			$this->index->addJS($nadlibPath . 'js/encloseInToggle.js');
			$id = uniqid();

			$content = '<div class="encloseIn">
				<' . $tag . '>
					<a class="show_hide" href="#" rel="#' . $id . '">
						<span>' . ($isOpen ? '&#x25BC;' : '&#x25BA;') . '</span>
						' . $title . '
					</a>
				</' . $tag . '>
				<div id="' . $id . '"
					class="toggleDiv"
					style="max-height: ' . $height . '; overflow: auto;
					' . ($isOpen ? '' : 'display: none;') . '">' . $content . '</div>
			</div>';
		}
		return $content;
	}

	function performAction($action = NULL)
	{
		$content = '';
		if ($this->request->isCLI()) {
			//debug($_SERVER['argv']);
			$reqAction = ifsetor($_SERVER['argv'][2]);    // it was 1
		} else {
			$reqAction = $this->request->getTrim('action');
		}
//		debug($reqAction);
		$method = $action
			?: (!empty($reqAction) ? $reqAction : 'index');
		if ($method) {
			$method .= 'Action';        // ZendFramework style
//			debug($method, method_exists($this, $method));

			if ($proxy = $this->request->getTrim('proxy')) {
				$proxy = new $proxy($this);
			} else {
				$proxy = $this;
			}

			if (method_exists($proxy, $method)) {
				if ($this->request->isCLI()) {
					$assoc = array_slice(ifsetor($_SERVER['argv'], []), 3);
					$content = call_user_func_array(array($proxy, $method), $assoc);
				} else {
					$caller = new MarshalParams($proxy);
					$content = $caller->call($method);
				}
			} else {
				// other classes except main controller may result in multiple messages
				//Index::getInstance()->message('Action "'.$method.'" does not exist in class "'.get_class($this).'".');
			}
		}
		return $content;
	}

	function preventDefault()
	{
		$this->noRender = true;
	}

	/**
	 * Uses float: left;
	 * @params array[string]
	 * @return mixed|string
	 */
	function inColumns()
	{
		$elements = func_get_args();
		return call_user_func_array(array(__CLASS__, 'inColumnsHTML5'), $elements);
		/*		$content = '';
				foreach ($elements as $html) {
					$html = $this->s($html);
					$content .= '<div style="float: left;">'.$html.'</div>';
				}
				$content = $content.'<div style="clear: both"></div>';
				return $content;*/
	}

	function inColumnsHTML5()
	{
		$this->index->addCSS($this->al->nadlibFromDocRoot . 'CSS/display-box.css');
		$elements = func_get_args();
		$content = '';
		foreach ($elements as $html) {
			$html = $this->s($html);
			$content .= '<div class="flex-box">' . $html . '</div>';
		}
		$content = '<div class="display-box">' . $content . '</div>';
		return $content;
	}

	function inEqualColumnsHTML5()
	{
		$this->index->addCSS($this->al->nadlibFromDocRoot . 'CSS/display-box.css');
		$elements = func_get_args();
		$content = '';
		foreach ($elements as $html) {
			$content .= '<div class="flex-box flex-equal">' . $html . '</div>';
		}
		$content = '<div class="display-box equal">' . $content . '</div>';
		return $content;
	}

	function encloseInTableHTML3(array $cells, array $more = array())
	{
		if (!$more) {
			$more['class'] = "encloseInTable";
		}
		$content[] = '<table ' . HTMLTag::renderAttr($more) . '>';
		$content[] = '<tr>';
		foreach ($cells as $info) {
			$content[] = '<td valign="top">';
			$content[] = $this->s($info);
			$content[] = '</td>';
		}
		$content[] = '</tr>';
		$content[] = '</table>';
		return $content;
	}

	/**
	 * Wraps all elements in <div class="column">|</div>
	 * Use HTMLTag to do manual wrapping
	 * @return string
	 */
	function encloseInTable()
	{
		$this->index->addCSS($this->al->nadlibFromDocRoot . 'CSS/columnContainer.less');
		$elements = func_get_args();
		$content = '<div class="columnContainer">';
		foreach ($elements as &$el) {
			if (!$el instanceof HTMLTag) {
				$el = $this->s($el);
				$el = new HTMLTag('div', array(
					'class' => 'column',
				), $el, true);
			}
		}
		$content .= implode("\n", $elements);
		$content .= '</div>';
		return $content;
	}

	/**
	 * Commented to allow get_class_methods() to return false
	 * @return string
	 */
	//function getMenuSuffix() {
	//	return '';
	//}

	function sidebar()
	{
		return '';
	}

	/**
	 * @see makeRelURL
	 * @param array $params
	 * @return URL
	 */
	function adjustURL(array $params)
	{
		return URL::getCurrent()->addParams(array(
				'c' => get_class(Index::getInstance()->controller),
			) + $params);
	}

	/**
	 * Just appends $this->linkVars
	 * @param $text
	 * @param array $params
	 * @param string $page
	 * @return HTMLTag
	 */
	function makeRelLink($text, array $params, $page = '?')
	{
		return new HTMLTag('a', array(
			'href' => $this->makeRelURL($params, $page)
		), $text);
	}

	/**
	 * There is no $formMore parameter because you get the whole form returned.
	 * You can modify it after returning as you like.
	 * @param $name string|htmlString - if object then will be used as is
	 * @param string|null $action
	 * @param $formAction
	 * @param array $hidden
	 * @param string $submitClass
	 * @param array $submitParams
	 * @return HTMLForm
	 */
	function getActionButton($name, $action, $formAction = NULL, array $hidden = array(), $submitClass = '', array $submitParams = array())
	{
		$f = new HTMLForm();
		if ($formAction) {
			$f->action($formAction);
		} else {
			$f->hidden('c', get_class($this));
		}
		$f->formHideArray($hidden);
		if (false) {    // this is too specific, not and API
//			if ($id = $this->request->getInt('id')) {
//				$f->hidden('id', $id);
//			}
		}
		if (!is_null($action)) {
			$f->hidden('action', $action);
		}
		if ($name instanceof htmlString) {
			$f->button($name, array(
					'type' => "submit",
					'id' => 'button-action-' . $action,
					'class' => $submitClass,
				) + $submitParams);
		} else {
			$f->submit($name, array(
					'id' => 'button-action-' . $action,
					'class' => $submitClass,
				) + $submitParams);
		}
		return $f;
	}

	/**
	 * Returns content wrapped in bootstrap .row .col-md-3/4/5 columns
	 * @param array $parts
	 * @param array $widths
	 * @return string
	 */
	function inTable(array $parts, array $widths = array())
	{
		$size = sizeof($parts);
		$equal = round(12 / $size);
		$content = '<div class="row">';
		foreach ($parts as $i => $c) {
			$c = $this->s($c);
			$x = ifsetor($widths[$i], $equal);
			$content .= '<div class="col-md-' . $x . '">' . $c . '</div>';
		}
		$content .= '</div>';
		return $content;
	}

	function attr($s)
	{
		if (is_array($s)) {
			$content = array();
			foreach ($s as $k => $v) {
				$content[] = $k . '="' . $this->attr($v) . '"';
			}
			$content = implode(' ', $content);
		} else {
			$content = htmlspecialchars($s, ENT_QUOTES);
		}
		return $content;
	}

	function s($something)
	{
		return MergedContent::mergeStringArrayRecursive($something);
	}

	/**
	 * @param string|URL $href
	 * @param string|htmlString $text
	 * @param bool $isHTML
	 * @param array $more
	 * @return HTMLTag
	 */
	function a($href, $text = '', $isHTML = false, array $more = array())
	{
		return new HTMLTag('a', array(
				'href' => $href,
			) + $more, $text ?: $href, $isHTML);
	}

	function div($content, $class = '', array $more = array())
	{
		$more['class'] = ifsetor($more['class']) . ' ' . $class;
		$more = HTMLTag::renderAttr($more);
		return '<div ' . $more . '>' . $this->s($content) . '</div>';
	}

	function span($content, $class = '', array $more = array())
	{
		$more['class'] = ifsetor($more['class']) . ' ' . $class;
		$more = HTMLTag::renderAttr($more);
		return new htmlString('<span ' . $more . '>' . $this->s($content) . '</span>');
	}

	function info($content)
	{
		return '<div class="alert alert-info">' . $this->s($content) . '</div>';
	}

	function error($content)
	{
		return '<div class="alert alert-danger">' . $this->s($content) . '</div>';
	}

	function success($content)
	{
		return '<div class="alert alert-success">' . $this->s($content) . '</div>';
	}

	function message($content)
	{
		return '<div class="alert alert-warning">' . $this->s($content) . '</div>';
	}

	function h1($content)
	{
		return '<h1>' . $this->s($content) . '</h1>';
	}

	function h2($content)
	{
		return '<h2>' . $this->s($content) . '</h2>';
	}

	function h3($content)
	{
		return '<h3>' . $this->s($content) . '</h3>';
	}

	function h4($content)
	{
		return '<h4>' . $this->s($content) . '</h4>';
	}

	function h5($content)
	{
		return '<h5>' . $this->s($content) . '</h5>';
	}

	function h6($content)
	{
		return '<h6>' . $this->s($content) . '</h6>';
	}

	function progress($percent)
	{
		$percent = intval($percent);
		return '<div class="progress">
		  <div class="progress-bar" role="progressbar"
		  	aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100"
		  	style="width: ' . $percent . '%;">
			' . $percent . '%
		  </div>
		</div>';
	}

	function linkToAction($action = '', array $params = array(), $controller = NULL)
	{
		if (!$controller) {
			$controller = get_class($this);
		}
		$params = [
				'c' => $controller,
			] + $params;
		if ($action) {
			$params += [
				'action' => $action,
			];
		}
		return $this->makeURL($params);
	}

	function p($content, array $attr = array())
	{
		$more = HTMLTag::renderAttr($attr);
		return '<p ' . $more . '>' . $this->s($content) . '</p>';
	}

	function img($src, array $attr = array())
	{
		$html = new HTMLTag('img', array(
				'src' => /*$this->e*/
					($src),    // encoding is not necessary for &amp; in URL
			) + $attr);
		$html->closingTag = false;
		return $html;
	}

	function e($content)
	{
		if (is_array($content)) {
			$content = MergedContent::mergeStringArrayRecursive($content);
		}
		return htmlspecialchars($content, ENT_QUOTES);
	}

	public function noRender()
	{
		$this->noRender = true;
		$this->request->set('ajax', 1);
	}

	function script($file)
	{
		$mtime = filemtime($file);
		$file .= '?' . $mtime;
		return '<script src="' . $file . '" type="text/javascript"></script>';
	}

	function log($action, $data = NULL)
	{
		$this->log[] = new LogEntry($action, $data);
	}

	static function link($text = NULL, array $params = [])
	{
		/** @var Controller $self */
		$self = get_called_class();
		return new HTMLTag('a', array(
			'href' => $self::href($params)
		), $text ?: $self);
	}

	static function href(array $params = array())
	{
		$self = get_called_class();
		$url = $self;
		if ($params) {
			$url .= '?' . http_build_query($params);
		}
		return $url;
	}

	/**
	 * @param $caption
	 * @param $h
	 * @return string
	 */
	public function getCaption($caption, $h)
	{
		$al = AutoLoad::getInstance();
		Index::getInstance()->addCSS($al->nadlibFromDocRoot . 'CSS/header-link.less');
		$slug = $this->request->getURL() . URL::friendlyURL($caption);
		$link = '<a class="header-link" href="#' . $slug . '">
				<i class="fa fa-link"></i>
			</a>';
		$content = '<a name="' . URL::friendlyURL($caption) . '"></a>
			<' . $h . ' id="' . $slug . '">' .
			$link . $caption .
			'</' . $h . '>';
		return $content;
	}

	function linkPage($className)
	{
		$obj = new $className();
		$title = $obj->title;
		return $this->a($className, $title);
	}

	function makeNewOf($className, $id)
	{
		return new $className($id);
	}

	function getInstanceOf($className, $id)
	{
		return $className::getInstance($id);
	}

	function setDB(DBInterface $db)
	{
		$this->db = $db;
	}

	/**
	 * http://stackoverflow.com/questions/19901850/how-do-i-get-an-objects-unqualified-short-class-name
	 * @return string
	 */
	function self()
	{
		return substr(strrchr(get_class($this), '\\'), 1);
	}

	function st($a)
	{
		return strip_tags($a);
	}

	function makeActionURL($action = '', array $params = [])
	{
		$urlParams = [
				'c' => get_class($this),
				'action' => $action,
			] + $params;
		$urlParams = array_filter($urlParams);
		return $this->makeURL($urlParams);
	}

}
