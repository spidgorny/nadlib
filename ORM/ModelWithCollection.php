<?php

/**
 * Class ModelWithCollection
 * Collection code is deprecated, but we keep it here for some time, just in case.
 */
class ModelWithCollection extends Model
{

	/** @var class-string<Model> */
	public static $itemClassName;

	/**
	 * @param $id
	 * @return mixed
	 * @deprecated
	 */
	public function getModel($id): mixed
	{
		return call_user_func([static::$itemClassName, 'getInstance'], $id);
	}

	/**
	 * TODO: implement numRows in a way to get the amount of data from the query
	 * object.
	 */
	public function getCount(): int
	{
		// don't uncomment as this leads to recursive calls to $this->getCollection()
		return $this->getCollection()->getCount();
//		return $this->db->numRows('SELECT count(*) FROM '.$this->table);
	}

	/**
	 * @return Collection
	 * @throws Exception
	 * @deprecated
	 */
	public function getCollection(array $where = [], $orderBy = null)
	{
		$col = Collection::createForTable($this->db, static::$table);
		$col->idField = static::$idField;
		$col::$itemClassName = static::$itemClassName;
		$col->where = $where;
		if ($orderBy) {
			$col->orderBy = $orderBy;
		}

		// because it will try to run query on DBLayerJSON
		// that's OK because we don't use DBLayerJSON anymore
//		$col->count = $this->getCount();
		return $col;
	}

}
