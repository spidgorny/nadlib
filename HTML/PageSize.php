<?php

use spidgorny\nadlib\HTTP\URL;

class PageSize extends Controller
{

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

	/**
	 * @var int - default for all instances
	 */
	static public $default = 20;

	/**
	 * @param null $selected - default for this instance
	 */
	public function __construct($selected = null)
	{
		parent::__construct();
		$this->selected = $this->request->is_set('pageSize')
			? $this->request->getInt('pageSize') : null;

		if (!$this->selected) {
			$this->selected = $selected;
			$this->log[] = 'Constructor: '.$this->selected;
		}
		if (!$this->selected) {
			$this->selected = self::$default;
			$this->log[] = 'Default: '.$this->selected;
		}

		$this->options = array_combine($this->options, $this->options);
		$this->url = new URL();    // some default to avoid fatal error
	}

	public function setURL(URL $url)
	{
		$this->url = $url;
	}

	public function update()
	{
		$this->selected = $this->get();
	}

	public function set($value)
	{
		$this->selected = $value;
	}

	/**
	 * Returns the $this->selected value making sure it's not too big
	 * @return integer
	 */
	public function get()
	{
		return min($this->selected, max($this->options));
	}

	public function getAllowed()
	{
		if (in_array($this->selected, $this->options)) {
			return $this->selected;
		} else {
			return self::$default;
		}
	}

	public function render()
	{
		$content = '';
		foreach ($this->options as $o) {
			$content .= '<option ' . ($this->selected == $o ? 'selected' : '') . '>' . $o . '</option>' . "\n";
		}
		$this->url->unsetParam('pageSize');
		$this->url->setParam('pageSize', '');    // will end with pageSize=
		$content = '<select
			onchange="location = \'' . $this->url . '\'+this.options[this.selectedIndex].value;"
			class="input-small">' . $content . '</select>';
		return $content;
	}

	public function __toString()
	{
		return $this->render() . '';
	}

}
