<?php

interface DBInterface {

	//function __construct();

	// parameters are different
	//function connect();

	function perform($query);

	function numRows($res = NULL);

	function affectedRows($res = NULL);

	function getTables();

	function lastInsertID($res, $table = NULL);

	function free($res);

	function quoteKey($key);

	function escapeBool($value);

	function fetchAssoc($res);

	function transaction();

	function commit();

	function rollback();

}
