<?php

class PageSize extends AppController {

	/**
	 * Public to allow apps to adjust the amount
	 * @var array
	 */
	public $options = array(
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
		if (!$this->selected && $user) {
			$this->selected = $user->getPref('pageSize');
		}
		if (!$this->selected) {
			$this->selected = $selected;
		}
		if (!$this->selected) {
			$this->selected = self::$default;
		}
		if ($user) {
			$user->setPref('pageSize', $this->selected);
		}
		$this->options = array_combine($this->options, $this->options);
	}

	function setURL(URL $url) {
		$this->url = $url;
	}

	function update() {
		$this->selected = $this->get();
	}

	function get() {
		if (in_array($this->selected, $this->options)) {
			return $this->selected;
		} else {
			return self::$default;
		}
	}

	function render() {
		$content = '';
		foreach ($this->options as $o) {
			$content .= '<option '.($this->selected == $o ? 'selected' : '').'>'.$o.'</option>';
		}
		$this->url->setParam('pageSize', '');	// will end with pageSize=
		$content = '<select
			onchange="location = \''.$this->url.'\'+this.options[this.selectedIndex].value;"
			class="input-small">'.$content.'</select>';
		return $content;
	}

	function __toString() {
		return $this->render().'';
	}

}
