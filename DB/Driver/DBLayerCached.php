<?php

class DBLayerCached extends DBLayer
{
	protected $cache = [];

	protected function getCacheKey(string $method, $args): ?string
	{
		if (collect($args)->some(fn($x): bool => $x instanceof PgSql\Result)) {
			return null;
		}

		$args = collect($args)->map(fn($x): mixed => $x instanceof SQLSelectQuery ? $x->getQuery() : $x)->toArray();
		return md5($method . serialize($args));
	}

	public function clearCache(): void
	{
		$this->cache = [];
	}

	public function fetchAll($result, $key = null): void
	{
		$cacheKey = $this->getCacheKey(__METHOD__, func_get_args());
		if (isset($this->cache[$cacheKey])) {
//			llog('HIT', __METHOD__, substr($result, 0, 40));
			return $this->cache[$cacheKey];
		}

//		if ($cacheKey) {
//			llog('MISS', __METHOD__, substr($result, 0, 40));
//		}

		$data = parent::fetchAll($result, $key);
		if ($cacheKey) {
			$this->cache[$cacheKey] = $data;
		}

		return $data;
	}

	public function fetchAssoc($res): array|false
	{
		$cacheKey = $this->getCacheKey(__METHOD__, func_get_args());
		if (isset($this->cache[$cacheKey])) {
			return $this->cache[$cacheKey];
		}

		$data = parent::fetchAssoc($res);
		if ($cacheKey) {
			$this->cache[$cacheKey] = $data;
		}

		return $data;
	}

	public function fetchAssocSeek($res): array|false
	{
		$cacheKey = $this->getCacheKey(__METHOD__, func_get_args());
		if (isset($this->cache[$cacheKey])) {
			return $this->cache[$cacheKey];
		}

		$data = parent::fetchAssocSeek($res);
		if ($cacheKey) {
			$this->cache[$cacheKey] = $data;
		}

		return $data;
	}

	public function getAllRows($query)
	{
		$cacheKey = $this->getCacheKey(__METHOD__, func_get_args());
		if (isset($this->cache[$cacheKey])) {
			return $this->cache[$cacheKey];
		}

		$data = parent::getAllRows($query);
		if ($cacheKey) {
			$this->cache[$cacheKey] = $data;
		}

		return $data;
	}

	public function getFirstRow($query): array|false
	{
		$cacheKey = $this->getCacheKey(__METHOD__, func_get_args());
		if (isset($this->cache[$cacheKey])) {
			return $this->cache[$cacheKey];
		}

		$data = parent::getFirstRow($query);
		if ($cacheKey) {
			$this->cache[$cacheKey] = $data;
		}

		return $data;
	}

	public function getFirstValue($query): ?string
	{
		$cacheKey = $this->getCacheKey(__METHOD__, func_get_args());
		if (isset($this->cache[$cacheKey])) {
			return $this->cache[$cacheKey];
		}

		$data = parent::getFirstValue($query);
		if ($cacheKey) {
			$this->cache[$cacheKey] = $data;
		}

		return $data;
	}

	public function runInsertQuery($table, array $data)
	{
		$this->clearCache();
		return parent::runInsertQuery($table, $data);
	}

	public function runInsertUpdateQuery($table, array $fields, array $where, array $insert = [])
	{
		$this->clearCache();
		return parent::runInsertUpdateQuery($table, $fields, $where, $insert);
	}

	public function runUpdateQuery($table, array $columns, array $where, $orderBy = '')
	{
		$this->clearCache();
		return parent::runUpdateQuery($table, $columns, $where, $orderBy);
	}

	public function runDeleteQuery($table, array $where)
	{
		$this->clearCache();
		return parent::runDeleteQuery($table, $where);
	}
}
