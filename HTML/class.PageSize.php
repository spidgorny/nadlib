<?php

class PageSize extends Controller {
	protected $options = array(
		10, 15, 20, 30, 40, 50, 60, 100, 200, 500, 1000,
	);
	protected $selected;

	/**
	 * @var URL
	 */
	protected $url;
	static public $default = 20;

	function __construct($selected = NULL) {
		parent::__construct();
		$this->selected = $this->request->is_set('pageSize') ? $this->request->getInt('pageSize') : NULL;
		$user = Config::getInstance()->user;
		if (!$this->selected) {
			$this->selected = $user->getPref('pageSize');
		}
		if (!$this->selected) {
			$this->selected = $selected;
		}
		if (!$this->selected) {
			$this->selected = self::$default;
		}
		$user->setPref('pageSize', $this->selected);
	}

	function setURL(URL $url) {
		$this->url = $url;
	}

	function get() {
		return $this->selected;
	}

	function render() {
		$content = '';
		foreach ($this->options as $o) {
			$content .= '<option '.($this->selected == $o ? 'selected' : '').'>'.$o.'</option>';
		}
		$this->url->setParam('pageSize', '');	// will end with pageSize=
		$content = '<select onchange="location = \''.$this->url.'\'+this.options[this.selectedIndex].value;">'.$content.'</select>';
		return $content;
	}

	function __toString() {
		return $this->render().'';
	}

}
