<?php

abstract class Controller {
	/**
	 * Enter description here...
	 *
	 * @var Index
	 */
	public $index;

	/**
	 *
	 * @var Request
	 */
	public $request;

	/**
	 *
	 * @var MySQL|dbLayer
	 */
	protected $db;

	/**
	 * Will be taken as a <title> of the HTML table
	 * @var string
	 */
	public $title;

	protected $useRouter = false;

	/**
	 *
	 * @var User/Client/userMan/LoginUser
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

	function __construct() {
		if ($_REQUEST['d'] == 'log') echo __METHOD__."<br />\n";
		$this->index = class_exists('Index') ? Index::getInstance(false) : NULL;
		$this->request = Request::getInstance();
		$this->useRouter = $this->request->apacheModuleRewrite();
		$this->db = Config::getInstance()->db;
		$this->title = $this->title ? $this->title : get_class($this);
		$this->title = $this->title ? __($this->title) : $this->title;
		$this->user = Config::getInstance()->user;
		$this->linkVars['c'] = get_class($this);
		Config::getInstance()->mergeConfig($this);
		self::$instance[get_class($this)] = $this;
		if ($_REQUEST['d'] == 'log') echo __METHOD__." end<br />\n";
	}

	protected function makeURL(array $params, $forceSimple = FALSE, $prefix = '?') {
		if ($this->useRouter && !$forceSimple && file_exists('class/class.Router.php')) {
			$r = new Router();
			$url = $r->makeURL($params);
		} else {
			if (isset($params['c']) && !$params['c']) {
				unset($params['c']); // don't supply empty controller
			}
			$url = new URL($prefix != '?' ? $prefix : $this->request->getLocation(), $params);
			//echo $url, '<br />';
			$url->setPath($url->documentRoot.'/'.($prefix != '?' ? $prefix : ''));
			/*foreach ($params as &$val) {
				$val = str_replace('#', '%23', $val);
			} unset($val);
			if ($params || $prefix != '?') {
				$url = $prefix.http_build_query($params, '', '&'); //, PHP_QUERY_RFC3986);
			}*/
		}
		return $url;
	}

	function makeRelURL(array $params = array()) {
		return $this->makeURL($params + $this->linkVars);
	}

	function getURL(array $params, $prefix = '?') {
		$params = $params + $this->linkVars;
		//debug($params);
		return $this->makeURL($params, false, $prefix);
	}

	function makeLink($text, array $params, $page = '', array $more = array(), $isHTML = false) {
		$content = new HTMLTag('a', array(
			'href' => $this->makeURL($params, false, $page),
		)+$more, $text, $isHTML);
		return $content;
	}

	function makeAjaxLink($text, array $params, $div, $jsPlus = '', $aMore = '', $ahrefPlus = '') {
		$content = '<a href="javascript: void(0);" '.$aMore.' onclick="
			$(\'#'.$div.'\').load(\''.$this->makeURL($params).'\');
			'.$jsPlus.'" '.$ahrefPlus.'>'.$text.'</a>';
		return $content;
	}

	function slideLoad($text, array $params, $div) {
		$content = '<a href="javascript: void(0);" onclick="
			$(\'#'.$div.'\').slideLoad(\''.$this->makeURL($params, false, '').'\');
		">'.$text.'</a>';
		return $content;
	}

	function begins($line, $with) {
		return (substr($line, 0, strlen($with)) == $with);
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

/*	static function getInstance() {
		$static = get_called_class();
		if ($static == 'Controller') throw new Exception('Unable to create Controller instance');
		return self::$instance ? self::$instance : new $static();
	}
*/

	static function getInstance() {
		return self::$instance;
	}

	function redirect($url) {
		if (DEVELOPMENT) {
			return '<script>
				setTimeout(function() {
					document.location.replace("'.str_replace('"', '&quot;', $url).'");
				}, 5000);
			</script>';
		} else {
			return '<script> document.location.replace("'.str_replace('"', '&quot;', $url).'"); </script>';
		}
	}

	function render() {
		$view = new View(get_class($this).'.phtml', $this);
		$content = $view->render();
		return $content;
	}

	function __toString() {
		return $this->render().'';
	}

	/**
	 * @param string $string		- source page name
	 * @param bool $preserveSpaces	- leaves spaces
	 * @return string				- converted to URL friendly name
	 */
	static function friendlyURL($string, $preserveSpaces = false) {
		$string = preg_replace("`\[.*\]`U","",$string);
		$string = preg_replace('`&(amp;)?#?[a-z0-9]+;`i','-',$string);
		$string = htmlentities($string, ENT_COMPAT, 'utf-8');
		$string = preg_replace( "`&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);`i","\\1", $string );
		if (!$preserveSpaces) {
			$string = preg_replace( array("`[^a-z0-9]`i","`[-]+`") , "-", $string);
		}
		return strtolower(trim($string, '-'));
	}

	function encloseIn($title, $content) {
		return '<fieldset><legend>'.htmlspecialchars($title).'</legend>'.$content.'</fieldset>';
	}

	function encloseInAA($content, $caption = '', $h = NULL) {
		$h = $h ?: $this->encloseTag;
		if ($caption) {
			$content = '<'.$h.'>'.$caption.'</'.$h.'>'.$content;
		}
		$content = '<div class="padding">'.$content.'</div>';
		return $content;
	}

	function encloseInToggle($content, $title, $height = '', $isOpen = NULL, $tag = 'h3') {
		if ($content) {
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

	function performAction() {
		$method = $this->request->getTrim('action');
		if ($method) {
			$method .= 'Action';		// ZendFramework style
			if (method_exists($this, $method)) {
				$content = $this->$method();
			} else {
				// other classes except main controller may result in multiple messages
				//Index::getInstance()->message('Action "'.$method.'" does not exist in class "'.get_class($this).'".');
			}
		}
		return $content;
	}

	function inColumns() {
		$elements = func_get_args();
		return call_user_func_array(array(__CLASS__, 'inColumnsHTML5'), $elements);
		$content = '';
		foreach ($elements as $html) {
			$content .= '<div style="float: left;">'.$html.'</div>';
		}
		$content = $content.'<div style="clear: both"></div>';
		return $content;
	}

	function inColumnsHTML5() {
		$GLOBALS['HTMLFOOTER']['display-box.css'] = '<link rel="stylesheet" type="text/css" href="/nadlib/CSS/display-box.css" />';
		$elements = func_get_args();
		$content = '';
		foreach ($elements as $html) {
			$content .= '<div class="flex-box">'.$html.'</div>';
		}
		$content = '<div class="display-box">'.$content.'</div>';
		return $content;
	}

	function getMenuSuffix() {
		return '';
	}

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

	function makeRelLink($text, array $params) {
		return new HTMLTag('a', array(
			'href' => $this->makeRelURL($params)
		), $text);
	}

	/**
	 * @param $name string|htmlString - if object then will be used as is
	 * @param $action
	 * @return HTMLForm
	 */
	function getActionButton($name, $action) {
		$f = new HTMLForm();
		$f->hidden('c', get_class($this));
		if ($id = $this->request->getInt('id')) {
			$f->hidden('id', $id);
		}
		$f->hidden('action', $action);
		if ($name instanceof htmlString) {
			$f->button($name, 'type="submit" class="likeText"');
		} else {
			$f->submit($name);
		}
		return $f;
	}

}
