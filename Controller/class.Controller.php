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
	 * @var MySQL/dbLayer
	 */
	protected $db;

	public $title = __CLASS__;
	protected $useRouter = false;

	/**
	 *
	 * @var User/Client/userMan/LoginUser
	 */
	public $user;

	static protected $instance;

	/**
	 * Allows selecting fullScreen layout of the template
	 *
	 * @var string
	 */
	public $layout;

	function __construct() {
		if ($_REQUEST['d'] == 'log') echo __METHOD__."<br />\n";
		$this->index = class_exists('Index') ? Index::getInstance(false) : NULL;
		$this->request = new Request();
		$this->useRouter = $this->request->apacheModuleRewrite();
		$this->db = Config::getInstance()->db;
		$this->title = $this->title ? $this->title : get_class($this);
		$this->title = $this->title ? __($this->title) : $this->title;
		$this->user = Config::getInstance()->user;
		if ($_REQUEST['d'] == 'log') echo __METHOD__." end<br />\n";
	}

	function makeURL(array $params, $forceSimple = FALSE, $prefix = '?') {
		if ($this->useRouter && !$forceSimple && file_exists('class/class.Router.php')) {
			$r = new Router();
			$url = $r->makeURL($params);
		} else {
			foreach ($params as &$val) {
				$val = str_replace('#', '%23', $val);
			} unset($val);
			if (isset($params['c']) && !$params['c']) {
				unset($params['c']); // don't supply empty controller
			}
			$url = $prefix.http_build_query($params, '', '&'); //, PHP_QUERY_RFC3986);
		}
		return $url;
	}

	function makeRelURL(array $params = array()) {
		return $this->makeURL($params+array(
			'c' => get_class($this),
		));
	}

	function getURL(array $params, $prefix = '?') {
		return $this->makeURL($params, $prefix);
	}

	function makeLink($text, array $params, $page = '', array $more = array()) {
		$content = new HTMLTag('a', array(
			'href' => $page.$this->makeURL($params),
		)+$more, $text);
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

	static function friendlyURL($string) {
		$string = preg_replace("`\[.*\]`U","",$string);
		$string = preg_replace('`&(amp;)?#?[a-z0-9]+;`i','-',$string);
		$string = htmlentities($string, ENT_COMPAT, 'utf-8');
		$string = preg_replace( "`&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);`i","\\1", $string );
		$string = preg_replace( array("`[^a-z0-9]`i","`[-]+`") , "-", $string);
		return strtolower(trim($string, '-'));
	}

	function encloseIn($title, $content) {
		return '<fieldset><legend>'.htmlspecialchars($title).'</legend>'.$content.'</fieldset>';
	}

	function encloseInAA($content, $caption = '', $h = 'h4') {
		if ($caption) {
			$content = '<'.$h.'>'.$caption.'</'.$h.'>'.$content;
		}
		$content = '<div class="padding">'.$content.'</div>';
		return $content;
	}

	function encloseInToggle($content, $title, $height = '', $isOpen = NULL) {
		if ($content) {
			$id = uniqid();

			$content = '<div class="encloseIn">
				<h3>
					<a class="show_hide" href="#" rel="#'.$id.'">
						<span>'.($isOpen ? '&#x25BC;' : '&#x25BA;').'</span>
						'.$title.'
					</a>
				</h3>
				<div id="'.$id.'"
					class="toggleDiv"
					style="max-height: '.$height.'; overflow: auto;
					'.($isOpen ? '' : 'display: none;').'">'.$content.'</div>
			</div>';
		}
		return $content;
	}

	function log($text, $class = NULL, $done = NULL, array $extra = array()) {
		//debug_pre_print_backtrace();
		Config::getInstance()->db->runInsertQuery('log', array(
			'pid' => getmypid(),
			'class' => strval($class),
			'done' => floatval($done),
			'line' => $text,
		)+$extra);
		echo '<tr><td>'.implode('</td><td>', array(
			date('Y-m-d H:i:s'),
			getmypid(),
			$class,
			'<img src="bar.php?rating='.round($done*100).'" /> '.number_format($done*100, 3).'%',
			$extra['id_channel'],
			$extra['date'],
			$text,
		)).'</td></tr>'."\n";
		flush();
	}

	function randomBreak() {
/*		$rand = rand(1, 10);
		$this->log('Sleep '.$rand);
		sleep($rand);
		$this->log('.<br>');
*/	}

	function checkStop() {
		if (file_exists('cron.stop')) {
			$this->log('Forced stop.');
			unlink('cron.stop');
			exit();
		}
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

}
