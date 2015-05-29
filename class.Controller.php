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

	public $title = __CLASS__;
	protected $useRouter = false;

	/**
	 *
	 * @var User/Client/userMan/LoginUser
	 */
	public $user;

	/**
	 * Enter description here...
	 *
	 * @var Client
	 */
	public $client;

	static protected $instance;

	function __construct() {
		if ($_REQUEST['d'] == 'log') echo __METHOD__."<br />\n";
		$this->index = class_exists('Index') ? Index::getInstance() : NULL;
		$this->request = new Request();
		$this->db = Config::getInstance()->db;
		$this->client = $this->index->client;
		$this->title = get_class($this);
		$this->user = Config::getInstance()->user;
		$this->title = $this->title ? __($this->title) : $this->title;
	}

	function makeURL(array $params, $forceSimple = FALSE, $prefix = '?') {
		if ($this->useRouter && !$forceSimple) {
			$r = new Router();
			$url = $r->makeURL($params);
		} else {
			foreach ($params as &$val) {
				$val = str_replace('#', '%23', $val);
			} unset($val);
			if (isset($params['c']) && !$params['c']) {
				unset($params['c']); // don't supply empty controller
			}
			$url = $prefix.http_build_query($params);
		}
		return $url;
	}

	function makeRelURL(array $params) {
		return $this->makeURL(array(
			'pageType' => get_class($this),
		)+$params);
	}

	function getURL(array $params, $prefix = '?') {
		return $this->makeURL($params, $prefix);
	}

	function makeLink($text, array $params, $page = '') {
		$content = '<a href="'.$page.$this->makeURL($params).'">'.$text.'</a>';
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
			$(\'#'.$div.'\').slideLoad(\''.$this->makeURL($params, '').'\');
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

	static function getInstance() {
		return self::$instance ?: new static;
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
			}
		}
		return $content;
	}

}
