<?php

class SQLWhereEqualText extends SQLWhereEqual
{

	public function __toString(): string
	{
		return is_null($this->val) ? $this->field . ' IS NULL' : $this->field . ' = ' . $this->db->quoteSQL($this->val);
	}

}
