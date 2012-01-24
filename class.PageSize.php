<?php

class PageSize extends Controller {
	protected $options = array(
		10, 15, 20, 30, 40, 50, 60, 100, 200, 500, 1000,
	);
	protected $selected;
	protected $url;

	function __construct($selected) {
		parent::__construct();
		$this->selected = $this->request->is_set('pageSize') ? $this->request->getInt('pageSize') : NULL;
		if (!$this->selected) {
			$this->selected = Config::getInstance()->user->getPref('pageSize');
		}
		if (!$this->selected) {
			$this->selected = $selected;
		}
		Config::getInstance()->user->setPref('pageSize', $this->selected);
	}

	function setURL($url) {
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
		$content = '<select onchange="location = \''.$this->url.'&pageSize=\'+this.options[this.selectedIndex].value;">'.$content.'</select>';
		return $content;
	}

	function __toString() {
		return $this->render().'';
	}

}
