<?php

namespace nadlib\DB\Driver;

/**
 * Class MongoDB
 * @deprecated
 */
class MongoDB
{

	/**
	 * @var string
	 */
	public $dbName;

	/**
	 * @var Mongo
	 */
	protected $mongo;

	/**
	 * @var
	 */
	protected $connection;

	function __construct($dbName)
	{
		$this->dbName = $dbName;
		$this->connect($this->dbName);
	}

	function connect($db)
	{
		$server = [
			'mongodb://localhost:27017',
		];

		if (!class_exists('Mongo')) {
			die("Mongo support required. Install mongo pecl extension with 'pecl install mongo; echo \"extension=mongo.so\" >> php.ini'");
		}
		try {
			$this->mongo = new \Mongo($server[0], [
				'connect' => true
			]);
		} catch (MongoConnectionException $ex) {
			error_log($ex->getMessage());
			die("Failed to connect to MongoDB");
		}
		$this->connection = $this->mongo->selectDB($db);
	}

	/**
	 * @param string $colName
	 * @return \MongoCollection
	 */
	function getCollection($colName)
	{
		$collection = $this->connection->selectCollection($colName);
		return $collection;
	}

}
