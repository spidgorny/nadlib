<?php

class TableField {

	var $field;

	var $type;

	var $isNull;

	var $key;

	var $default;

	var $extra = array();

	static function init(array $row) {
		//debug($row); exit();
		if (isset($row['Field'])) {
			$self = self::initMySQL($row);
		} else {
			throw new Exception(__METHOD__.' Unable to identify DB type');
		}
		return $self;
	}

	/**
	 * Field    string[2]    id
	 * Type    string[7]    int(11)
	 * Null    string[2]    NO
	 * Key    string[3]    PRI
	 * Default    NULL
	 * Extra    string[14]    auto_increment
	 * @param array $row
	 * @return TableField
	 */
	static function initMySQL(array $row) {
		$self = new self();
		$self->field = $row['Field'];
		$self->type = $row['Type'];
		$self->isNull = $row['Null'] == 'YES';
		$self->key = $row['Key'];
		$self->default = $row['Default'];
		$self->extra = trimExplode(' ', $row['Extra']);
		return $self;
	}

	function convertFromOtherDB(array $desc) {
		if (isset($desc['cid']) || isset($desc['pk'])) {    // MySQL???
			//$original = $desc;
			unset($desc['cid']);
			$desc['Field'] = $desc['name'];
			unset($desc['name']);
			$desc['Type'] = $desc['type'];
			unset($desc['type']);
			$desc['Null'] = $desc['notnull'] ? 'NO' : 'YES';
			unset($desc['notnull']);
			$desc['Default'] = $desc['dflt_value'];
			$desc['Default'] = $this->unQuote($desc['Default']);
			unset($desc['dflt_value']);
			$desc['Extra'] = $desc['pk'] ? 'PRIMARY_KEY' : '';
			unset($desc['pk']);
			//debug($original, $desc); exit();
		}
		return $desc;
	}

	function __toString() {
		$copy = get_object_vars($this);
		$copy['isNull'] = $copy['isNull'] ? 'is NULL' : 'NOT NULL';
		$copy['default'] = $copy['default'] ? 'DEFAULT ['.$copy['default'].']' : '';
		$copy['extra'] = implode(' ', $copy['extra']);
		return implode(' ', $copy);
	}

}
