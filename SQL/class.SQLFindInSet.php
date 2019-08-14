<?php

class SQLFindInSet extends SQLWherePart
{
	protected $value;

	function __construct($value)
	{
		$this->value = $value;
	}

	function __toString()
	{
		if (is_array($this->value)) {
			return "
				STRING_TO_ARRAY(" . $this->qb->quoteSQL(implode(',', $this->value), $this->field) . ", ',')
				<@
				STRING_TO_ARRAY(" . $this->field . ", ',')
			";
		} else {
			return "COALESCE(" . $this->qb->quoteSQL($this->value, $this->field) . " = ANY(STRING_TO_ARRAY(" . $this->field . ", ',')), FALSE)";
		}
	}

}
