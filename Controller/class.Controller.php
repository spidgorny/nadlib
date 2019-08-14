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

abstract class Controller
{

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
	 * chack manually in render()
	 */
	public $noRender = false;

	/**
	 * @var MySQL|dbLayer|dbLayerMS|dbLayerPDO
	 */
	protected $db;

	/**
	 * Will be taken as a <title> of the HTML table
	 * @var string
	 */
	public $title;

	protected $useRouter = false;

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
	 * @var string
	 */
	public $layout;

	public $linkVars = array();

	public $encloseTag = 'h4';

	/**
	 * accessible without login
	 * @var bool
	 */
	static public $public = false;

	function __construct()
	{
		if ($_REQUEST['d'] == 'log') echo get_class($this) . ' ' . __METHOD__ . "<br />\n";
		$this->index = class_exists('Index') ? Index::getInstance(false) : NULL;
		$this->request = Request::getInstance();
		//$this->useRouter = $this->request->apacheModuleRewrite(); // set only when needed
		if (class_exists('Config')) {
			$this->db = Config::getInstance()->db;
			$this->user = Config::getInstance()->user;
			Config::getInstance()->mergeConfig($this);
		}
		$this->linkVars['c'] = get_class($this);
		$this->title = $this->title ? $this->title : get_class($this);
		$this->title = $this->title ? __($this->title) : $this->title;
		self::$instance[get_class($this)] = $this;
	}

	protected function makeURL(array $params, $forceSimple = FALSE, $prefix = '?')
	{
		if ($this->useRouter && !$forceSimple) {
			if (file_exists('class/class.Router.php')) {
				$r = new Router();
				$url = $r->makeURL($params, $prefix);
			} else {
				$class = $params['c'];
				unset($params['c']);
				$url = new URL($prefix != '?'
					? $prefix
					: $this->request->getLocation(), $params);
				$url->components['path'] .= $class;
			}
		} else {
			if (isset($params['c']) && !$params['c']) {
				unset($params['c']); // don't supply empty controller
			}
			$url = new URL($prefix != '?'
				? $prefix
				: $this->request->getLocation(), $params);
			//debug($prefix, $url);
			//$url->setPath($url->documentRoot.'/'.($prefix != '?' ? $prefix : ''));

			//debug($url->documentRoot, $prefix, $url.'');
			/*foreach ($params as &$val) {
				$val = str_replace('#', '%23', $val);
			} unset($val);
			if ($params || $prefix != '?') {
				$url = $prefix.http_build_query($params, '', '&'); //, PHP_QUERY_RFC3986);
			}*/
		}
		return $url;
	}

	/**
	 * Only appends $this->linkVars to the URL.
	 * Use this one if your linkVars is defined.
	 * @param array $params
	 * @param string $page
	 * @return URL
	 */
	function makeRelURL(array $params = array(), $page = '?')
	{
		return $this->makeURL($params + $this->linkVars, $page);
	}

	/**
	 * Combines params with $this->linkVars
	 * @param array $params
	 * @param string $prefix
	 * @return URL
	 */
	public function getURL(array $params, $prefix = '?')
	{
		$params = $params + $this->linkVars;
		//debug($params);
		return $this->makeURL($params, false, $prefix);
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
				'href' => $this->makeURL($params, false, $page),
			) + $more, $text, $isHTML);
		return $content;
	}

	function makeAjaxLink($text, array $params, $div, $jsPlus = '', $aMore = '', $ahrefPlus = '')
	{
		$content = '<a href="javascript: void(0);" ' . $aMore . ' onclick="
			$(\'#' . $div . '\').load(\'' . $this->makeURL($params) . '\');
			' . $jsPlus . '" ' . $ahrefPlus . '>' . $text . '</a>';
		return $content;
	}

	function slideLoad($text, array $params, $div)
	{
		$content = '<a href="javascript: void(0);" onclick="
			$(\'#' . $div . '\').slideLoad(\'' . $this->makeURL($params, false, '') . '\');
		">' . $text . '</a>';
		return $content;
	}

	function begins($line, $with)
	{
		return (substr($line, 0, strlen($with)) == $with);
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
		if ($static == 'Controller') throw new Exception('Unable to create Controller instance');
		return self::$instance[$static]
			? self::$instance[$static]
			: (self::$instance[$static] = new $static());
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
		$view = new View(get_class($this) . '.phtml', $this);
		$content = $view->render();
		return $content;
	}

	function __toString()
	{
		return $this->render() . '';
	}

	/**
	 * @param string $string - source page name
	 * @param bool $preserveSpaces - leaves spaces
	 * @return string                - converted to URL friendly name
	 */
	static function friendlyURL($string, $preserveSpaces = false)
	{
		$string = preg_replace("`\[.*\]`U", "", $string);
		$string = preg_replace('`&(amp;)?#?[a-z0-9]+;`i', '-', $string);
		$string = htmlentities($string, ENT_COMPAT, 'utf-8');
		$string = preg_replace("`&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);`i", "\\1", $string);
		if (!$preserveSpaces) {
			$string = preg_replace(array("`[^a-z0-9]`i", "`[-]+`"), "-", $string);
		}
		return strtolower(trim($string, '-'));
	}

	function encloseIn($title, $content)
	{
		$title = $title instanceof htmlString ? $title : htmlspecialchars($title);
		return '<fieldset><legend>' . $title . '</legend>' . $content . '</fieldset>';
	}

	function encloseInAA($content, $caption = '', $h = NULL)
	{
		$h = $h ? $h : $this->encloseTag;
		$content = IndexBase::mergeStringArrayRecursive($content);
		if ($caption) {
			$content = '<' . $h . '>' . $caption . '</' . $h . '>' . $content;
		}
		//debug_pre_print_backtrace();
		$content = '<div class="padding clearfix">' . $content . '</div>';
		return $content;
	}

	function encloseInToggle($content, $title, $height = '', $isOpen = NULL, $tag = 'h3')
	{
		if ($content) {
			// buggy: prevents all clicks on the page in KA.de
			$this->index->addJQuery();
			$this->index->addJS('nadlib/js/showHide.js');
			$this->index->addJS('nadlib/js/encloseInToggle.js');
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
		$method = $action ? $action : $this->request->getTrim('action');
		if ($method) {
			$method .= 'Action';        // ZendFramework style
			//debug($method, method_exists($this, $method));
			if (method_exists($this, $method)) {
				$content = $this->$method();
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
		$content = '';
		foreach ($elements as $html) {
			$content .= '<div style="float: left;">' . $html . '</div>';
		}
		$content = $content . '<div style="clear: both"></div>';
		return $content;
	}

	function inColumnsHTML5()
	{
		$this->index->addCSS('vendor/spidgorny/nadlib/CSS/display-box.css');
		$elements = func_get_args();
		$content = '';
		foreach ($elements as $html) {
			$content .= '<div class="flex-box">' . $html . '</div>';
		}
		$content = '<div class="display-box">' . $content . '</div>';
		return $content;
	}

	function inEqualColumnsHTML5()
	{
		$this->index->addCSS('vendor/spidgorny/nadlib/CSS/display-box.css');
		$elements = func_get_args();
		$content = '';
		foreach ($elements as $html) {
			$content .= '<div class="flex-box flex-equal">' . $html . '</div>';
		}
		$content = '<div class="display-box equal">' . $content . '</div>';
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
	 * @param array $params
	 * @return URL
	 * @see makeRelURL
	 */
	function adjustURL(array $params)
	{
		return URL::getCurrent()->setParams(array(
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
	 * @param $name string|htmlString - if object then will be used as is
	 * @param string|null $action
	 * @param $formAction
	 * @param array $hidden
	 * @param string $submitClass
	 * @param array $submitParams
	 * @return HTMLForm
	 * @internal param null $class
	 */
	function getActionButton($name, $action, $formAction = NULL, array $hidden = array(), $submitClass = 'likeText', array $submitParams = array())
	{
		$f = new HTMLForm();
		if ($formAction) {
			$f->action($formAction);
		} else {
			$f->hidden('c', get_class($this));
		}
		$f->formHideArray($hidden);
		if ($id = $this->request->getInt('id')) {
			$f->hidden('id', $id);
		}
		$f->hidden('action', $action);
		if ($name instanceof htmlString) {
			$f->button($name, 'type="submit" class="' . $submitClass . '"');
		} else {
			$f->submit($name, array(
					'class' => $submitClass,
				) + $submitParams);
		}
		return $f;
	}

	function inTable(array $parts)
	{
		$size = sizeof($parts);
		$x = round(12 / $size);
		$content = '<div class="row">';
		foreach ($parts as $c) {
			$c = IndexBase::mergeStringArrayRecursive($c);
			$content .= '<div class="col-md-' . $x . '">' . $c . '</div>';
		}
		$content .= '</div>';
		return $content;
	}

}
