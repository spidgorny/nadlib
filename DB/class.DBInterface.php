<?php

interface DBInterface {

	function perform($query);

	function numRows($res);

	function quoteKey($key);

	function lastInsertID();

	function free($res);

	function escapeBool($value);

	function affectedRows();

}
