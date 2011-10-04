<?php

class Selectable {
	public $name;
	/**
	 * Assoc
	 * @var type 
	 */
	public $data = array();
	public $selected;

	function __construct($selected) {
		$this->name = get_class($this);
		if (in_array($selected, array_keys($this->data))) {
			$this->selected = $selected;
		} else {
			/*throw new Exception('Invalid selected ('.$selected.') in '.get_class($this).'<br>
				<li>'.implode('<li>', array_keys($this->data)));
			 * 
			 */
			$this->selected = current(array_keys($this->data));
		}
	}

	function __toString() {
		$request = new Request();

		$f = new HTMLForm();
		$f->method('GET');

		if (in_array($request->getController(), array(
			'RoomView',
			'EditRoomDescription',
		))) {
			$f->hidden('c', 'RoomView');
		} else {
			$f->hidden('c', $request->getController());
		}

		$f->selection($this->name, $this->data, $this->selected, TRUE);
		return $f->getContent();
	}

	function getName() {
		return $this->data[$this->selected];
	}

}