<?php

class Model {

	var $table;

	var $idField = 'id';

	var $titleColumn = 'name';

	var $itemClassName = '?';

	function getCollection() {
		$col = Collection::createForTable($this->table);
		return $col;
	}

	function getModel($id) {
		$model = call_user_func([$this->itemClassName, 'getInstance'], $id);
		return $model;
	}

}
