<?php

class PageSize extends Controller {

	/**
	 * Public to allow apps to adjust the amount
	 * @var array
	 */
	public $options = array(
		10, 15, 20, 30, 40, 50, 60, 100, 200, 500, 1000,
	);

	public $selected;

	/**
	 * @var URL
	 */
	protected $url;

	/**
	 * @var int - default for all instances
	 */
	static public $default = 20;

	/**
	 * @param null $selected - default for this instance
	 */
	function __construct($selected = NULL) {
		parent::__construct();
		$this->selected = $this->request->is_set('pageSize') ? $this->request->getInt('pageSize') : NULL;

		$user = null;
		if (class_exists('Config')) {
			$user = Config::getInstance()->getUser();
			if (!$this->selected && $this->userHasPreferences()) {
				$this->selected = $user->getPref('pageSize');
			}
		}

		if (!$this->selected) {
			$this->selected = $selected;
		}
		if (!$this->selected) {
			$this->selected = self::$default;
		}

		if ($user && $this->userHasPreferences()) {
			$user->setPref('pageSize', $this->selected);
		}

		$this->options = array_combine($this->options, $this->options);
		$this->url = new URL(); 	// some default to avoid fatal error
	}

	function userHasPreferences() {
		$user = Config::getInstance()->getUser();
		return $user && ifsetor($user->id) && method_exists($user, 'getPref');
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
			$content .= '<option '.($this->selected == $o ? 'selected' : '').'>'.$o.'</option>'."\n";
		}
		$this->url->unsetParam('pageSize');
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
