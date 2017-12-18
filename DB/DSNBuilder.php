<?php

class DSNBuilder {

	public static function make($scheme, $host, $user, $pass, $db, $port) {
		$classMap = [
//			'mysql' => DSNBuilderMySQL::class,
			'mysql' => 'DSNBuilderMySQL',
//			'pgsql' => DSNBuilderPostgreSQL::class,
			'pgsql' => 'DSNBuilderPostgreSQL',
//			'sqlite' => DSNBuilderSQLite::class,
			'sqlite' => 'DSNBuilderSQLite',
//			'mssql' => DSNBuilderMSSQL::class,
			'mssql' => 'DSNBuilderMSSQL',
		];
		$dsnClass = $classMap[$scheme];
		$builder = new $dsnClass($host, $user, $pass, $db, $port);
		return $builder;
	}

	function getDSN(array $params) {
		$url = http_build_query($params, NULL, ';', PHP_QUERY_RFC3986);
		$url = str_replace('%20', ' ', $url);	// back convert
		$url = urldecode($url);
		return $url;
	}

}
