<?php

class ShowAssoc {

	var $data = [];

	var $thes = [];

	var $title;

	function __construct(array $assoc) {
		$this->data = $assoc;
	}

	function setThes(array $thes) {
		$this->thes = $thes;
	}

	function setTitle($title) {
		$this->title = $title;
	}

	function render() {
		TaylorProfiler::start(__METHOD__);
		$content[] = '<div class="showAssoc">';
		if ($this->title !== '') {
			$content[] = '<h3>' . ($this->title) . ':</h3>';
		}
		$assoc = array();
		foreach ($this->thes as $key => $name) {
			$val = $this->data[$key];
			if (is_array($name)) {
				$val = $this->getValue($name, $val);
			}
			$niceName = is_array($name) ? $name['name'] : $name;
			$assoc[$niceName] = $val;
		}
		$content[] = UL::DL($assoc)->render();
		$content[] = '</div>';
		TaylorProfiler::stop(__METHOD__);
		return $content;
	}

	function getValue(array $desc, $val) {
		if (ifsetor($desc['reference'])) {
			// class name
			$class = $desc['reference'];
			$obj = $class::getInstance($val);
			if (method_exists($obj, 'getNameLink')) {
				$val = new htmlString($obj->getNameLink());
			} elseif (method_exists($obj, 'getName')) {
				$val = $obj->getName();
			} else {
				$val = $obj->__toString();
			}
		} elseif (ifsetor($desc['bool'])) {
			$val = $desc['bool'][$val];	// yes/no
		} elseif (is_callable(ifsetor($desc['render']))) {
			$val = call_user_func($desc['render'], $this->data);
		}
		return $val;
	}

	function __toString() {
		return MergedContent::mergeStringArrayRecursive($this->render());
	}

}