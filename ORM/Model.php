<?php

class Model {

	var $table;

	var $idField = 'id';

	var $titleColumn = 'name';

	var $itemClassName = '?';

	/**
	 * @var DBInterface|SQLBuilder
	 */
	var $db;

	function __construct(DBInterface $db) {
		$this->db = $db;
	}

	function getCollection() {
		$col = Collection::createForTable($this->table);
		return $col;
	}

	function getModel($id) {
		$model = call_user_func([$this->itemClassName, 'getInstance'], $id);
		return $model;
	}

	function getFormFromModel()
	{
		$desc = [];
		foreach (get_object_vars($this) as $fieldName => $_) {
			$field = new ReflectionProperty(get_class($this), $fieldName);
			$sComment = $field->getDocComment();
			if ($sComment) {
				$dc = new DocCommentParser($sComment);
				//debug($field->getName(), $sComment, $dc->getAll());
				if ($dc->is_set('column')) {
					$desc[$field->getName()] = [
						'label' => $dc->get('label') ?: $dc->getDescription(),
						'type' => $dc->get('type') ?: 'text',
						'optional' => $dc->is_set('optional') || !$dc->is_set('required'),
					];
				}
			}
		}
		return $desc;
	}

}
