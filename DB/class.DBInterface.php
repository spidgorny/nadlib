<?php

interface DBInterface {

	//function __construct();

	// parameters are different
	//function connect();

	function perform($query);

	function numRows($res = NULL);

	function affectedRows();

	function getTables();

	function lastInsertID();

	function free($res);

	function quoteKey($key);

	function escapeBool($value);

	function fetchAssoc($res);

	function transaction();

	function commit();

	function rollback();

}
