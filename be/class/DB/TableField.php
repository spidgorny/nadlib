<?php

class TableField
{

	public $field;

	public $type;

	public $collation;

	public $isNull;

	public $key;

	public $default;

	public $comment;

	public $extra = array();

	static function init(array $row)
	{
		//debug($row); exit();
		if (isset($row['cid']) || isset($row['pk'])) {
			$self = self::initSQLite($row);
		} elseif (isset($row['Field'])) {
			$self = self::initMySQL($row);
		} else {
			throw new Exception(__METHOD__ . ' Unable to identify DB type');
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
	static function initMySQL(array $row)
	{
		$self = new self();
		$self->field = $row['Field'];
		$self->type = $row['Type'];
		$self->isNull = $row['Null'] == 'YES';
		$self->key = $row['Key'];
		$self->default = $row['Default'];
		$self->extra = trimExplode(' ', $row['Extra']);
		return $self;
	}

	/**
	 * cid    string[1]    0
	 * name    string[2]    id
	 * type    string[7]    integer
	 * notnull    string[1]    1
	 * dflt_value    NULL
	 * pk    string[1]    1
	 * Field    string[2]    id
	 * Type    string[7]    integer
	 * Null    string[2]    NO
	 * @param array $desc
	 * @return TableField
	 */
	static function initSQLite(array $desc)
	{
		$self = new self();
		$self->field = $desc['name'];
		$self->type = $desc['type'];
		$self->isNull = $desc['notnull'] ? false : true;
		$self->default = self::unQuote($desc['dflt_value']);
		$self->key = $desc['pk'] ? 'PRIMARY_KEY' : '';
		//debug($desc, $self); exit();
		return $self;
	}

	static function unQuote($string)
	{
		$first = $string[0];
		if ($first == '"' || $first == "'") {
			$string = str_replace($first, '', $string);
		}
		return $string;
	}

	function __toString()
	{
		$copy = get_object_vars($this);
		$copy['isNull'] = $copy['isNull'] ? 'is NULL' : 'NOT NULL';
		$copy['default'] = $copy['default'] ? 'DEFAULT [' . $copy['default'] . ']' : '';
		$copy['extra'] = implode(' ', $copy['extra']);
		return implode(' ', $copy);
	}

}
