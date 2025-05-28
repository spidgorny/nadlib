<?php

trait CachedGetInstance
{

	/**
	 * array<int, static>
	 */
	public static $instances = [];

	public static function clearInstances(): void
	{
		self::$instances[static::class] = [];
		gc_collect_cycles();
	}

	public static function clearAllInstances(): void
	{
		self::$instances = [];
		gc_collect_cycles();
	}

	/**
	 * @param $id
	 * @param DBInterface $db
	 * @return self
	 * @throws Exception
	 */
	public static function tryGetInstance($id, DBInterface $db)
	{
		try {
			$obj = self::getInstance($id, $db);
//			llog(get_called_class(), $id, 'getInstance', spl_object_hash($obj));
		} catch (InvalidArgumentException $invalidArgumentException) {
			/** @var class-string<static> $class */
			$class = static::class;
			$obj = new $class();
			$obj->setDB($db);
//			llog(get_called_class(), $id, 'new', spl_object_hash($obj));
		}

		return $obj;
	}

	/**
	 * @param int $id
	 * @param DBInterface $db
	 * @param mixed ...$args
	 * @return static
	 */
	public static function getInstance($id, DBInterface $db, ...$args): static
	{
		return self::getInstanceByID($id, $db, ...$args);
	}

	/**
	 * // TODO: initialization by array should search in $instances as well
	 * @param $id |array int
	 * @param DBInterface $db
	 * @return static
	 */
	public static function getInstanceByID($id, DBInterface $db): static
	{
		/** @var class-string<static> $static */
		$static = static::class;
		/*nodebug(array(
			__METHOD__,
			'class' => $static,
			'instances' => sizeof(self::$instances[$static]),
			'id' => $id,
			'exists' => self::$instances[$static]
				? implode(', ', array_keys(self::$instances[$static]))
				: NULL,
		));*/
		if (is_scalar($id)) {
			$inst = static::$instances[$static][$id] ?? null;
			if (!$inst) {
				//debug('new ', get_called_class(), $id, array_keys(self::$instances));
				// don't put anything else here
				$inst = new $static(null, $db);
				$inst->setDB($db);
				// BEFORE init() to avoid loop
				static::storeInstance($inst, $id);
				// separate call to avoid infinite loop in ORS
				$inst->init($id);
			}
		} elseif (is_array($id)) {
			$inst = new $static(null, $db);    // only to find ->idField
			$inst->setDB($db);
			$intID = $id[$inst->idField];
			//debug($static, $intID, $id);
			$inst = self::$instances[$static][$intID] ?? $inst;
			if (!$inst->id) {
				$inst->init($id);    // array
				static::storeInstance($inst, $intID);    // int id
			}
		} elseif ($id) {
			//debug($static, $id);
			$inst = new $static(null, $db);
			$inst->setDB($db);
			$inst->init($id);
			static::storeInstance($inst, $inst->id);
		} elseif (is_null($id)) {
			$inst = new $static(null, $db);
		} else {
			throw new InvalidArgumentException($static . '->' . __METHOD__ . ' id=' . $id);
		}

		return $inst;
	}

	public static function storeInstance($inst, $newID = null): void
	{
		$static = static::class;
		$id = $inst->id ?: $newID;
		if ($id) {
			self::$instances[$static][$id] = $inst;
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public static function getCacheStatsTable()
	{
		$stats = OODBase::getCacheStats();
		$stats = ArrayPlus::create($stats)
			->makeTable('count')
			->insertKeyAsColumn('class');
		$max = $stats->column('count')->max();
		if ($max != 0) {
			//debug((array)$stats); exit();
			$stats->addColumn('bar', function (array $row, $i) use ($max): string {
				return ProgressBar::getImage($row['count'] / $max * 100);
			});
		}

		$stats = $stats->getData();
		$s = new slTable($stats, 'class="table"', [
			'class' => 'Class',
			'count' => 'Count',
			'bar' => [
				'no_hsc' => true,
			],
		]);
		$content[] = $s->getContent();
		return $content;
	}

	/**
	 * @return int[]
	 */
	public static function getCacheStats(): array
	{
		$stats = [];
		foreach (self::$instances as $class => $list) {
			if (!is_array($list)) {
				debug($list);
				die;
			}

			$stats[$class] = count($list);
		}

		return $stats;
	}

	/**
	 * Still searches in DB with findInDB, but makes a new object for you
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function findInstance(array $where, $static = null)
	{
		if (!$static) {
			if (function_exists('get_called_class')) {
				$static = static::class;
			} else {
				throw new \RuntimeException('__METHOD__ requires object specifier until PHP 5.3.');
			}
		}

		/** @var static $obj */
		$obj = new $static();
		$obj->findInDB($where);
		if ($obj->id) {
			self::$instances[$static][$obj->id] = $obj;
		}

		return $obj;
	}

	/**
	 * Is cached in instances
	 * @param string $name
	 * @param string|null $field
	 * @param DBInterface $db
	 * @return ?static
	 */
	public static function getInstanceByName($name, $field = null, DBInterface $db): ?static
	{
		$self = static::class;
		//debug(__METHOD__, $self, $name, count(self::$instances[$self]));

		// first search instances
		$c = static::findInstanceByName($name, $field);
		if ($c instanceof \OODBase) {
			return $c;
		}

		$c = new $self();
		$c->setDB($db);
		$field = $field ?: $c->titleColumn;
		if (is_string($field)) {
			$c->findInDBsetInstance([
//					 new SQLWhereEqual(new AsIs('trim(' . $field . ')'), $name),	// __toString error
				new SQLWhereEqual('trim(' . $field . ')', $name),
			]);
		} elseif ($field instanceof AsIs) {
			$c->findInDBsetInstance([$field]);
		} else {
			throw new RuntimeException(__METHOD__);
		}

		return $c;
	}

	public static function findInstanceByName($name, $field = null, ?DBInterface $db = null): ?\OODBase
	{
		$self = static::class;
		if (ifsetor(self::$instances[$self], [])) {
			foreach (self::$instances[$self] as $inst) {
				if ($inst instanceof OODBase) {
					$field = $field ?: $inst->titleColumn;
//					llog(__METHOD__, $inst->data[$field], $name);
					if (ifsetor($inst->data[$field]) === $name) {
						$inst->setDB($db);
						return $inst;
					}
				}
			}
		}

		return null;
	}

}
