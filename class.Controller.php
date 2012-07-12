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
	 * @var dbLayer
	 */
	protected $db;

	public $title = __CLASS__;
	protected $useRouter = false;

	/**
	 * Enter description here...
	 *
	 * @var User/Client/userMan
	 */
	public $user;

	function __construct() {
		$this->index = $GLOBALS['i'];
		$this->request = new Request();
		$this->db = Config::getInstance()->db;
		$this->title = get_class($this);
		$this->title = $this->title ? __($this->title) : $this->title;
		$this->user = Config::getInstance()->user;
	}

	abstract function render();

	function makeURL(array $params, $forceSimple = FALSE) {
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
			$url = '?'.http_build_query($params);
		}
		return $url;
	}

	function makeRelURL(array $params) {
		return $this->makeURL(array(
			'pageType' => get_class($this),
		)+$params);
	}

	function makeLink($text, array $params) {
		$content = '<a href="'.$this->makeURL($params).'">'.$text.'</a>';
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

	function getAssocTable(array $data) {
		$table = array();
		foreach ($data as $key => $val) {
			$table[] = array('key' => $key, 'val' => $val);
		}
		return $table;
	}

	function getInstance() {
		return new self;
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
		$content .= $view->render();
		return $content;
	}

	function __toString() {
		return $this->render().'';
	}

	static function friendlyURL($string){
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

}
