<?php

class SQLIn extends SQLWherePart {

	public $list = array();

	function __construct(array $list) {
		parent::__construct();
		$this->list = $list;
		foreach ($this->list as $el) {
			if (is_array($el)) {
				throw new InvalidArgumentException(__METHOD__.' need to have flat array');
			}
		}
	}

	function __toString() {
		$field = $this->field;

		// this prevents field names with dot notation being quoted!
		if (in_array(strtoupper($this->field), $this->db->getReserved())) {
			$field = $this->db->quoteKey($this->field);
		}

		if (!$field) {
			//debug_pre_print_backtrace();
		}
//		debug(__METHOD__, $this->list);
		$content = $field ." IN (".implode(", ", $this->db->quoteValues($this->list)).")";
		return $content;
	}

}
