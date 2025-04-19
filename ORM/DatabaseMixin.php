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

	public static function findByID(DBInterface $db, $id): ?self
	{
		$row = $db->fetchOneSelectQuery(static::getTableName(), [
			'id' => $id,
		]);
//		debug($row);
		if (!$row) {
			return null;
		}

		$instance = new static($row);
		$instance->db = $db;
		return $instance;
	}

	public static function findOne(DBInterface $db, array $where, $orderBy = ''): ?self
	{
		$row = $db->fetchOneSelectQuery(static::getTableName(), $where, $orderBy);
		if (!$row) {
			return null;
		}

		$instance = new static($row);
		$instance->db = $db;
		return $instance;
	}

	public static function findAll(DBInterface $db, array $where = [], $orderBy = ''): array
	{
		$rows = $db->fetchAllSelectQuery(static::getTableName(), $where, $orderBy);
		return array_map(static function ($row) use ($db): static {
			$instance = new static($row);
			$instance->db = $db;
			return $instance;
		}, $rows);
	}

	/**
     * DatabaseMixin constructor.
     * @override me
     */
    public function __construct(array $data)
	{
		throw new Exception('Override me ' . __METHOD__);
	}

}
