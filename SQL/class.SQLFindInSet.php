<?php

class SQLFindInSet extends SQLWherePart {

	protected $value;

	function __construct($value) {
		parent::__construct();
		$this->value = $value;
	}

	function __toString() {
		if (is_array($this->value)) {
			return "
				STRING_TO_ARRAY(".$this->db->quoteSQL(implode(',', $this->value), $this->field).", ',')
				<@
				STRING_TO_ARRAY(".$this->field.", ',')
			";
		} else {
			return "COALESCE(".$this->db->quoteSQL($this->value, $this->field)." = ANY(STRING_TO_ARRAY(".$this->field.", ',')), FALSE)";
		}
	}

}
