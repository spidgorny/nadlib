<?php

interface DBInterface
{

	//function __construct();

	// parameters are different
	//function connect();

	function perform($query);

	function numRows($res);

	function affectedRows();

	function getTables();

	function lastInsertID();

	function free($res);

	function quoteKey($key);

	function escapeBool($value);

}
