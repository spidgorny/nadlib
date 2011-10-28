<?php

abstract class Controller {
	/**
	 *
	 * @var
	 */
	protected $index;

	/**
	 *
	 * @var Request
	 */
	public $request;

	/**
	 *
	 * @var MySQL
	 */
	protected $db;

	public $title = __CLASS__;
	protected $useRouter = false;

	/**
	 * Enter description here...
	 *
	 * @var userMan
	 */
	public $user;

	function __construct() {
		$this->index = $GLOBALS['i'];
		$this->request = new Request();
		$this->db = Config::getInstance()->db;
		$this->title = get_class($this);
		$this->user = $GLOBALS['UM'];
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

	function makeLink($text, $params) {
		$content = '<a href="'.$this->makeURL($params).'">'.$text.'</a>';
		return $content;
	}

	function makeAjaxLink($text, $params, $div, $jsPlus = '', $aMore = '') {
		$content = '<a href="javascript: void(0);" '.$aMore.' onclick="
			$(\'#'.$div.'\').load(\''.$this->makeURL($params).'\');
			'.$jsPlus.'">'.$text.'</a>';
		return $content;
	}

	function __toString() {
		return $this->render().'';
	}

	function encloseIn($title, $content) {
		return '<fieldset><legend>'.htmlspecialchars($title).'</legend>'.$content.'</fieldset>';
	}

}
