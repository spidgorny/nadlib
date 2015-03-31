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
	 * @var MySQL|dbLayer|dbLayerMS|dbLayerPDO|dbLayerSQLite|dbLayerBase
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

	/**
	 * @var Config
	 */
	public $config;

	/**
	 * Used by Collection to get the current sorting method.
	 * Ugly, please reprogram.
	 * @var
	 */
	public $sortBy;

	protected $al;

	function __construct() {
		if (isset($_REQUEST['d']) && $_REQUEST['d'] == 'log') echo get_class($this).' '.__METHOD__."<br />\n";
		$this->index = class_exists('Index') ? Index::getInstance(false) : NULL;
		$this->request = Request::getInstance();
		$this->useRouter = $this->request->apacheModuleRewrite();
		$this->config = Config::getInstance();
		$this->al = AutoLoad::getInstance();
		if (class_exists('Config')) {
			$this->db = $this->config->getDB();
			$this->user = $this->config->getUser();
			//debug($this->user);
			$this->config->mergeConfig($this);
		} else {
			//$this->user = new UserBase();
		}
		$this->linkVars['c'] = get_class($this);
		$this->title = $this->title ? $this->title : get_class($this);
		$this->title = $this->title ? __($this->title) : $this->title;
		self::$instance[get_class($this)] = $this;
	}

	/**
	 * Why protected?
	 * @param array $params
	 * @param null $prefix
	 * @return URL
	 * @protected
	 * @use getURL()
	 */
	protected function makeURL(array $params, $prefix = NULL) {
		$class = ifsetor($params['c']);
		unset($params['c']);    // RealURL
		if ($class && !$prefix) {
			$prefix = $class;
		}
		$url = new URL($prefix
			? $prefix
			: $this->request->getLocation(), $params);
		$path = $url->getPath();
		if ($class) {
			$path->setFile($class);
		}
		$path->setAsFile();
		$url->setPath($path);
		return $url;
	}

	/**
	 * Only appends $this->linkVars to the URL.
	 * Use this one if your linkVars is defined.
	 * @param array $params
	 * @param string $page
	 * @return URL
	 */
	function makeRelURL(array $params = array(), $page = NULL) {
		return $this->makeURL($params + $this->linkVars, $page);
	}

	/**
	 * Combines params with $this->linkVars
	 * @param array $params
	 * @param string $prefix
	 * @return URL
	 */
	public function getURL(array $params, $prefix = NULL) {
		$params = $params + $this->linkVars;
		//debug($params);
		return $this->makeURL($params, $prefix);
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
	function makeLink($text, array $params, $page = '', array $more = array(), $isHTML = false) {
		//debug($text, $params, $page, $more, $isHTML);
		$content = new HTMLTag('a', array(
			'href' => $this->makeURL($params, $page),
		)+$more, $text, $isHTML);
		return $content;
	}

	function makeAjaxLink($text, array $params, $div, $jsPlus = '', $aMore = array()) {
		$url = $this->makeURL($params);
		$link = new HTMLTag('a', $aMore + array(
			'href' => $url,
			'onclick' => '
			$(\'#'.$div.'\').load(\''.$url.'\');
			return false;
			'.$jsPlus,
			), $text, true);
		return $link;
	}

	/**
	 * @param array $data
	 * @return array
	 * @deprecated
	 * @see slTable::showAssoc()
	 */
	function getAssocTable(array $data) {
		$table = array();
		foreach ($data as $key => $val) {
			$table[] = array('key' => $key, 'val' => $val);
		}
		return $table;
	}

	static function getInstance() {
		$static = get_called_class();
		//if ($static == 'Controller') throw new Exception('Unable to create Controller instance');
		$isset = isset(self::$instance[$static]);
		if ($isset) {
			$result = self::$instance[$static];
		} else {
			$index = Index::getInstance();
			$result = $index->getController();
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

	function render() {
		$view = new View(get_class($this).'.phtml', $this);
		$content = $view->render();
		return $content;
	}

	function __toString() {
		return $this->render().'';
	}

	function encloseIn($title, $content) {
		$title = $title instanceof htmlString ? $title : htmlspecialchars($title);
		$content = IndexBase::mergeStringArrayRecursive($content);
		return '<fieldset><legend>'.$title.'</legend>'.$content.'</fieldset>';
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
	function encloseInAA($content, $caption = '', $h = NULL, $more = array()) {
		$h = $h ? $h : $this->encloseTag;
		$content = $this->s($content);
		if ($caption) {
			Index::getInstance()->addCSS(AutoLoad::getInstance()->nadlibFromDocRoot.'CSS/header-link.less');
			$slug = URL::friendlyURL($caption);
			$link = '<a class="header-link" href="#'.$slug.'">
				<i class="fa fa-link"></i>
			</a>';
			$content = '<'.$h.' id="'.$slug.'">'.$link.$caption.'</'.$h.'>'.$content;
		}
		$more['class'] .= (ifsetor($more['class']) ? ' ' : '').get_class($this);
		//debug_pre_print_backtrace();
		$content = '<section class="padding clearfix '.ifsetor($more['class']).'"
			style="position: relative;">'.$content.'</section>';
		return $content;
	}

	function encloseInToggle($content, $title, $height = '', $isOpen = NULL, $tag = 'h3') {
		if ($content) {
			// buggy: prevents all clicks on the page in KA.de
			$this->index->addJQuery();
			$this->index->addJS('nadlib/js/showHide.js');
			$this->index->addJS('nadlib/js/encloseInToggle.js');
			$id = uniqid();

			$content = '<div class="encloseIn">
				<'.$tag.'>
					<a class="show_hide" href="#" rel="#'.$id.'">
						<span>'.($isOpen ? '&#x25BC;' : '&#x25BA;').'</span>
						'.$title.'
					</a>
				</'.$tag.'>
				<div id="'.$id.'"
					class="toggleDiv"
					style="max-height: '.$height.'; overflow: auto;
					'.($isOpen ? '' : 'display: none;').'">'.$content.'</div>
			</div>';
		}
		return $content;
	}

	function performAction($action = NULL) {
		$content = '';
		$reqAction = $this->request->getTrim('action');
		$method = $action ? $action : (!empty($reqAction) ? $reqAction : 'index');
		if ($method) {
			$method .= 'Action';		// ZendFramework style
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

	function preventDefault() {
		$this->noRender = true;
	}

	/**
	 * Uses float: left;
	 * @params array[string]
	 * @return mixed|string
	 */
	function inColumns() {
		$elements = func_get_args();
		return call_user_func_array(array(__CLASS__, 'inColumnsHTML5'), $elements);
		$content = '';
		foreach ($elements as $html) {
			$html = IndexBase::mergeStringArrayRecursive($html);
			$content .= '<div style="float: left;">'.$html.'</div>';
		}
		$content = $content.'<div style="clear: both"></div>';
		return $content;
	}

	function inColumnsHTML5() {
		$this->index->addCSS($this->al->nadlibFromDocRoot.'CSS/display-box.css');
		$elements = func_get_args();
		$content = '';
		foreach ($elements as $html) {
			$html = IndexBase::mergeStringArrayRecursive($html);
			$content .= '<div class="flex-box">'.$html.'</div>';
		}
		$content = '<div class="display-box">'.$content.'</div>';
		return $content;
	}

	function inEqualColumnsHTML5() {
		$this->index->addCSS($this->al->nadlibFromDocRoot.'CSS/display-box.css');
		$elements = func_get_args();
		$content = '';
		foreach ($elements as $html) {
			$content .= '<div class="flex-box flex-equal">'.$html.'</div>';
		}
		$content = '<div class="display-box equal">'.$content.'</div>';
		return $content;
	}

	function encloseInTableHTML3(array $cells) {
		$content[] = '<table class="encloseInTable">';
		$content[] = '<tr>';
		foreach ($cells as $info) {
			$content[] = '<td valign="top">';
			$content[] = IndexBase::mergeStringArrayRecursive($info);
			$content[] = '</td>';
		}
		$content[] = '</tr>';
		$content[] = '</table>';
		return $content;
	}

	function encloseInTable() {
		$this->index->addCSS($this->al->nadlibFromDocRoot.'CSS/columnContainer.less');
		$elements = func_get_args();
		$content = '<div class="columnContainer">';
		foreach ($elements as &$el) {
			if (!$el instanceof HTMLTag) {
				$el = IndexBase::mergeStringArrayRecursive($el);
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

	function sidebar() {
		return '';
	}

	/**
	 * @see makeRelURL
	 * @param array $params
	 * @return URL
	 */
	function adjustURL(array $params) {
		return URL::getCurrent()->setParams(array(
			'c' => get_class(Index::getInstance()->controller),
		)+$params);
	}

	/**
	 * Just appends $this->linkVars
	 * @param $text
	 * @param array $params
	 * @param string $page
	 * @return HTMLTag
	 */
	function makeRelLink($text, array $params, $page = '?') {
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
	 */
	function getActionButton($name, $action, $formAction = NULL, array $hidden = array(), $submitClass = '', array $submitParams = array()) {
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
			$f->button($name, array(
				'type' => "submit",
				'class' => $submitClass,
				) + $submitParams);
		} else {
			$f->submit($name, array(
				'class' => $submitClass,
			) + $submitParams);
		}
		return $f;
	}

	function inTable(array $parts) {
		$size = sizeof($parts);
		$x = round(12 / $size);
		$content = '<div class="row">';
		foreach ($parts as $c) {
			$c = IndexBase::mergeStringArrayRecursive($c);
			$content .= '<div class="col-md-'.$x.'">'.$c.'</div>';
		}
		$content .= '</div>';
		return $content;
	}

	function attr($s) {
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

	function s($something) {
		return IndexBase::mergeStringArrayRecursive($something);
	}

	/**
	 * @param string|URL $href
	 * @param string|htmlString $text
	 * @param bool $isHTML
	 * @return HTMLTag
	 */
	function a($href, $text = '', $isHTML = false) {
		return new HTMLTag('a', array(
			'href' => $href,
		), $text ?: $href, $isHTML);
	}

}
