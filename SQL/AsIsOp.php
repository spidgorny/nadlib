<?php

/**
 * Helps to distinguish normal data types of PHP from explicit SQL commands
 * that don't need to be quoted inside SQLBuilder class.
 * Usage: $data['mtime'] = new AsIs('NOW()');
 * Before that you would have to use this:
 * $data['mtime'] = 'now()';
 * $data['mtime.'] = array('asis' => TRUE);
 * It's effectively just a container for the value but it's easy to check like this:
 * if ($val instanceof AsIs) {...
 *
 * Well, it's clever enough to use "=" sign for WHERE and UPDATE queries and nothing in INSERT.
 */
class AsIsOp extends AsIs {

	protected $value;

	function __construct($val = '') {
		parent::__construct($val);
	}

	function debug() {
		return array(
			'field' => $this->field,
			'value' => $this->value,
		);
	}

}
