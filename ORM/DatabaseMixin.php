<?php


trait DatabaseMixin
{

	/**
	 * @var DBInterface
	 */
	protected $db;

	public static function getTableName()
	{
		return null;
	}

	public static function findByID(DBLayerSQLite $db, $id)
	{
		$row = $db->fetchOneSelectQuery(static::getTableName(), [
			'id' => $id,
		]);
//		debug($row);
		$instance = new static($row);
		$instance->db = $db;
		return $instance;
	}

	public static function findOne(DBLayerSQLite $db, array $where, $orderBy = '')
	{
		$row = $db->fetchOneSelectQuery(static::getTableName(), $where, $orderBy);
		$instance = new static($row);
		$instance->db = $db;
		return $instance;
	}

	public static function findAll(DBLayerSQLite $db, array $where, $orderBy = '')
	{
		$rows = $db->fetchAllSelectQuery(static::getTableName(), $where, $orderBy);
		$instances = array_map(static function ($row) use ($db) {
			$instance = new static($row);
			$instance->db = $db;
			return $instance;
		}, $rows);
		return $instances;
	}

	/**
	 * DatabaseMixin constructor.
	 * @override me
	 * @param array $data
	 */
	public function __construct(array $data)
	{
		throw new Exception('Override me ' . __METHOD__);
	}

}
