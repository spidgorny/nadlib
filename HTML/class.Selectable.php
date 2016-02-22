<?php

class Selectable {

	public $name;

	/**
	 * Assoc [14 => 'Koopa']
	 * @var array
	 */
	public $data = array();

	/**
	 * @var int
	 */
	public $selected;

	/**
	 * @param $selected - id / array row
	 */
	function __construct($selected) {
		$this->name = get_class($this);
		if (is_array($selected)) {
			$this->rows[$selected['id']] = $selected;
			$this->data[$selected['id']] = $selected['title'];
			$this->selected = $selected['id'];
		} else {
			$this->selected = $selected;
		}
	}

	function validateSelected() {
		// it's called AFTER subclass initialized $this->data
		//debug($selected, array_keys($this->data));
		if (!in_array($this->selected, array_keys($this->data))) {
			/*throw new Exception('Invalid selected ('.$selected.') in '.get_class($this).'<br>
				<li>'.implode('<li>', array_keys($this->data)));
			 *
			 */
			$this->selected = current(array_keys($this->data));
		}
	}

	/**
	 * It's not more convenient to have it in a toString()
	 * @return string
	 */
	function __toString() {
		return $this->getDropdown();
	}

	function getDropdown() {
		$request = Request::getInstance();

		$f = new HTMLForm();
		$f->method('GET');

		$nameless = $request->getURLLevel(0);
		//debug($nameless);
		if (!$nameless) {
			if (in_array($request->getControllerString(), array(
				'RoomView',
				'EditRoomDescription',
			))) {
				$f->hidden('c', 'RoomView');
			} else {
				$f->hidden('c', $request->getControllerString());
			}
		}

		$f->selection($this->name, $this->data, $this->selected, TRUE);
		return $f->getContent();
	}

	function getOptions($selected = NULL) {
		return $this->data;
	}

	function getName() {
		if (!isset($this->data[$this->selected])) {
			//debug($this->data);
			//debug_pre_print_backtrace();
			return 'Unknown room/location #'.$this->selected;
		}
		return $this->data[$this->selected];
	}

}
