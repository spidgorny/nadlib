<?php

class Selectable {
	public $name;
	/**
	 * Assoc
	 * @var type
	 */
	public $data = array();
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
			// it's called AFTER subclass initialized $this->data
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
	}

	function __toString() {
		$request = new Request();

		$f = new HTMLForm();
		$f->method('GET');

		if (in_array($request->getControllerString(), array(
			'RoomView',
			'EditRoomDescription',
		))) {
			$f->hidden('c', 'RoomView');
		} else {
			$f->hidden('c', $request->getControllerString());
		}

		$f->selection($this->name, $this->data, $this->selected, TRUE);
		return $f->getContent();
	}

	function getName() {
		return $this->data[$this->selected];
	}

}
