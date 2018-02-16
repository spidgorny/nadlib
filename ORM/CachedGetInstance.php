<?php

trait CachedGetInstance {

	/**
	 * array[get_called_class()][$id]
	 */
	static $instances = array();

	/**
	 * @param $id
	 * @return self|$this|static
	 * @throws Exception
	 */
	static function getInstance($id) {
		return static::getInstanceByID($id);
	}

	/**
	 * // TODO: initialization by array should search in $instances as well
	 * @param $id |array int
	 * @return $this
	 * @throws Exception
	 */
	public static function getInstanceByID($id) {
		$static = get_called_class();
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
			$inst = isset(self::$instances[$static][$id])
				? self::$instances[$static][$id]
				: NULL;
			if (!$inst) {
				//debug('new ', get_called_class(), $id, array_keys(self::$instances));
				/** @var OODBase $inst */
				// don't put anything else here
				$inst = new $static();
				// BEFORE init() to avoid loop
				self::storeInstance($inst, $id);
				// separate call to avoid infinite loop in ORS
				$inst->init($id);
			}
		} elseif (is_array($id)) {
			/** @var OODBase $inst */
			$inst = new $static();
			$intID = $id[$inst->idField];
			//debug($static, $intID, $id);
			$inst = isset(self::$instances[$static][$intID])
				? self::$instances[$static][$intID]
				: $inst;
			if (!$inst->id) {
				$inst->init($id);	// array
				self::storeInstance($inst, $intID);	// int id
			}
		} elseif ($id) {
			//debug($static, $id);
			/** @var OODBase $inst */
			$inst = new $static();
			$inst->init($id);
			self::storeInstance($inst, $inst->id);
		} elseif (is_null($id)) {
			$inst = new $static();
		} else {
			debug($id);
			throw new InvalidArgumentException($static.'->'.__METHOD__);
		}
		return $inst;
	}

	static function storeInstance($inst, $newID = NULL) {
		$static = get_called_class();
		$id = $inst->id ?: $newID;
		if ($id) {
			self::$instances[$static][$id] = $inst;
		}
	}

	static function clearInstances() {
		self::$instances[get_called_class()] = array();
		gc_collect_cycles();
	}

	static function clearAllInstances() {
		self::$instances = array();
		gc_collect_cycles();
	}

	/**
	 * @param $id
	 * @return self
	 * @throws Exception
	 */
	static function tryGetInstance($id) {
		try {
			$obj = self::getInstance($id);
		} catch (InvalidArgumentException $e) {
			$class = get_called_class();
			$obj = new $class();
		}
		return $obj;
	}

	public static function getCacheStats() {
		$stats = array();
		foreach (self::$instances as $class => $list) {
			$stats[$class] = sizeof($list);
		}
		return $stats;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public static function getCacheStatsTable() {
		$stats = OODBase::getCacheStats();
		$stats = ArrayPlus::create($stats)
			->makeTable('count')
			->insertKeyAsColumn('class')
		;
		$max = $stats->column('count')->max();
		if ($max != 0) {
			//debug((array)$stats); exit();
			$stats->addColumn('bar', function ($row, $i) use ($max) {
				return ProgressBar::getImage($row['count'] / $max * 100);
			});
		}
		$stats = $stats->getData();
		$s = new slTable($stats, 'class="table"', array(
			'class' => 'Class',
			'count' => 'Count',
			'bar' => array(
				'no_hsc' => true,
			),
		));
		$content[] = $s->getContent();
		return $content;
	}

	/**
	 * Still searches in DB with findInDB, but makes a new object for you
	 *
	 * @param array $where
	 * @param null $static
	 * @return mixed
	 * @throws Exception
	 */
	static function findInstance(array $where, $static = NULL) {
		if (!$static) {
			if (function_exists('get_called_class')) {
				$static = get_called_class();
			} else {
				throw new Exception('__METHOD__ requires object specifier until PHP 5.3.');
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
	 * @param null $field
	 * @return self|static
	 * @throws Exception
	 */
	static function getInstanceByName($name, $field = NULL) {
		$self = get_called_class();
		//debug(__METHOD__, $self, $name, count(self::$instances[$self]));

		$c = NULL;
		// first search instances
		if (is_array(ifsetor(self::$instances[$self]))) {
			foreach (self::$instances[$self] as $inst) {
				if ($inst instanceof OODBase) {
					$field = $field ? $field : $inst->titleColumn;
					if (ifsetor($inst->data[$field]) == $name) {
						$c = $inst;
						break;
					}
				}
			}
		}

		if (!$c) {
			$c = new $self();
			/** @var $c OODBase */
			$field = $field ? $field : $c->titleColumn;
			if (is_string($field)) {
				$c->findInDBsetInstance(array(
					$field => $name,
				));
			} elseif ($field instanceof AsIs) {
				$c->findInDBsetInstance([
					$field
				]);
			}
		}
		return $c;
	}

}