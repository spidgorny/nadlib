<?php

/**
 * Class SQLNotIn *
 */
class SQLNotIn extends SQLWherePart
{

	/**
     * @var mixed[]
     */
    public $list = [];

	/**
     * SQLNotIn constructor.
     */
    public function __construct(array $list)
	{
		parent::__construct();
		$this->list = $list;
	}

	public function __toString(): string
	{
		$field = $this->field;

		// this prevents field names with dot notation being quoted!
		if (in_array(strtoupper($this->field), $this->db->getReserved())) {
			$field = $this->db->quoteKey($this->field);
		}

		if (!$field) {
			//debug_pre_print_backtrace();
		}

//		debug($content); die;
		return $field . " NOT IN (" . implode(", ", $this->db->quoteValues($this->list)) . ")";
	}

}
