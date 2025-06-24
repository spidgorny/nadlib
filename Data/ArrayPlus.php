<?php

/**
 * Usage:
 * $source = array(
 *        array(    // row 1
 *            'col1' => 'val1',
 *            'col2' => 'val2',
 *        ),
 *        'row2' => array(
 *            'col1' => 'val3',
 *            'col2' => 'val4',
 *        ),
 * );
 * $ap = new ArrayPlus($source);
 * $column = $ap->column('col2');
 *
 * $column = array(
 *        '0' => 'val2',
 *        'row2' => 'val4',
 * );
 *
 * class ArrayPlus
 * Rules:
 * 1. Don't access $this->data, use $this as an array
 * 2. Don't access by reference - data is not updated.
 * http://stackoverflow.com/questions/8685186/arrayaccess-in-php-assigning-to-offset-by-reference
 * 3. Don't set $this->data = $new, use $this->setData($new)
 */

class ArrayPlus extends ArrayObject implements HasGetter
{

	public function __construct(array $array = [])
	{
		parent::__construct($array);
		$this->setData($array);
	}

	/**
	 * Chainable
	 */
	public function setData(array $data): static
	{
		$this->exchangeArray($data);
		return $this;
	}

	/**
	 * http://php.net/manual/en/function.array-splice.php#111204
	 * @param array $input
	 * @param int $offset - key of the element to insert BEFORE(!)
	 * @param int $length
	 * @param array $replacement
	 */
	public static function array_splice_assoc(&$input, $offset, $length, $replacement = []): array
	{
		$replacement = (array)$replacement;
		$key_indices = array_flip(array_keys($input));
		if (isset($input[$offset]) && $offset) {
			$offset = $key_indices[$offset];
		}

		if (isset($input[$length]) && $length) {
			$length = $key_indices[$length] - $offset;
		}

		$extract = array_slice($input, $offset, $length, true);

		$input = array_slice($input, 0, $offset, true)
			+ $replacement
			+ array_slice($input, $offset + $length, null, true);
		return $extract;
	}

	public static function isRecursive(array $array): bool
	{
		foreach ($array as $item) {
			if (is_array($item)) {
				return true;
			}
		}

		return false;
	}

	public function column_coalesce($col1, $col2): array
	{
		$return = [];
		foreach ((array)$this as $key => $row) {
			$return[$key] = ifsetor($row[$col1]) ? $row[$col1] : $row[$col2];
		}

		return $return;
	}

	public function pluck($key): ArrayPlus
	{
		return $this->column($key);
	}

	/**
	 * Returns an array of the elements in a specific column.
	 * @param string $col
	 */
	public function column($col): ArrayPlus
	{
		$return = [];
		foreach ((array)$this as $key => $row) {
			$return[$key] = ifsetor($row[$col]);
		}

		return new ArrayPlus($return);
	}

	public function pick($key): array
	{
		return $this->getMap(function ($el) use ($key) {
			//			debug($el, $key);
			return $el->$key;
		});
	}

	/**
	 * @param callable $callback
	 */
	public function getMap($callback): array
	{
		return array_map($callback, $this->getData());
	}

	public function getData(): array
	{
		return (array)$this;
	}

	public function pickAssoc($name, $key = 'id'): array
	{
		$keys = $this->getMap(function ($el) use ($key) {
			return $el->$key;
		});
		$names = $this->getMap(function ($el) use ($name) {
			return $el->$name;
		});
		return array_combine($keys, $names);
	}

	public function column_assoc($key, $val): static
	{
		$data = [];
		foreach ((array)$this as $row) {
			$data[$row[$key]] = $row[$val];
		}

		$this->setData($data);
		return $this;
	}

	/**
	 * Use to filter input array to keep some data that fits the DB schema
	 * @return $this
	 */
	public function keepColumns(array $keep): static
	{
		$data = [];
		foreach ((array)$this as $i => $row) {
			$row = array_intersect_key($row, array_combine($keep, $keep));
			$data[$i] = $row;
		}

		$this->setData($data);
		return $this;
	}

	/**
	 * Use to filter a set of data into a different set of keys
	 * @param array $keep ['new' => 'existing'] it's reversed, keys are what you want to have
	 * @return $this
	 */
	public function remap(array $keep): static
	{
		$data = [];
		foreach ($keep as $i => $row) {
			if (isset($this[$row])) {    // don't make empty SQL UPDATE SET a = NULL
				$data[$i] = $this[$row];
			}
		}

		$this->setData($data);
		return $this;
	}

	public function remapColumns(array $keys): static
	{
		$data = $this->getData();
		foreach ($data as &$row) {
			$row = array_combine($keys, $row);
		}

		$this->setData($data);
		return $this;
	}

	/**
	 * Modifies itself
	 * @param string $key
	 * @param bool $allowMerge
	 * @throws Exception
	 */
	public function IDalize($key = 'id', $allowMerge = false): static
	{
		$data = [];
		foreach ($this as $row) {
			$keyValue = $row[$key];
			if (!$keyValue && !$allowMerge) {
				$error = __METHOD__ . '#' . __LINE__ . ' You may need to specify $this->idField in your model.';
//				debug([
//					'error' => $error,
//					'key' => $key,
//					'row' => $row,
//					'data' => $this->getData(),
//				]);
				throw new RuntimeException($error);
			}

			$data[$keyValue] = $row;
		}

		$this->setData($data);
		return $this;
	}

	/**
	 * [
	 *  ['a' => 'b'],
	 *  ['a' => 'c']
	 * ] ---> [
	 *  ['a' => ['b', 'c']]
	 * ]
	 * @param string $key
	 * @return $this
	 */
	public function combine($key = 'id'): static
	{
		$data = [];
		foreach ($this as $row) {
			$keyValue = $row[$key];
			$data[$keyValue][] = $row;
		}

		$this->setData($data);
		return $this;
	}

	#[ReturnTypeWillChange]
	public function append(mixed $value, $key = null): static
	{
		if (!is_null($key)) {
			$this[$key] = $value;
		} else {
			$this[] = $value;
		}

		return $this;
	}

	/**
	 * Callback = function ($value, [$index]) {}
	 *
	 * @param callable $callback
	 */
	public function each($callback): static
	{
		foreach ($this as $i => $el) {
			//$el = $callback($el, $i);
			$el = call_user_func($callback, $el, $i);
			$this[$i] = $el;
		}

		return $this;
	}

	/**
	 * Callback = function ($value, [$index]) {}
	 *
	 * @param callable $callback
	 */
	public function eachCollect($callback): string
	{
		$content = '';
		foreach ($this as $i => $el) {
			$plus = $callback($el, $i);
			$content .= $plus;
		}

		return $content;
	}

	public function ksort(int $flags = SORT_REGULAR): true
	{
		$arrayCopy = $this->getArrayCopy();
		ksort($arrayCopy);
		$this->setData($arrayCopy);
		return true;
	}

	/**
	 * Returns the first found row
	 * @param string $key
	 * @param mixed $val
	 * @return mixed
	 */
	public function searchColumn($key, $val)
	{
		foreach ($this as $row) {
			if ($row[$key] == $val) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * @return mixed[]
	 */
	public function getAssoc($key, $val): array
	{
		$ret = [];
		foreach ($this as $row) {
			$ret[$row[$key]] = $row[$val];
		}

		return $ret;
	}

	public function trim(): static
	{
		foreach ($this as $i => $value) {
			$this[$i] = trim($value);
		}

		return $this;
	}

	public function stripTags(): static
	{
		foreach ($this as $i => $value) {
			$this[$i] = strip_tags($value);
		}

		return $this;
	}

	/**
	 * Will keep the assoc keys
	 * @param callable $callback
	 */
	public function mapBoth($callback): static
	{
		$data = $this->getData();
		$keys = $this->getKeys();
		$mapped = array_map(function ($key) use ($callback, $data) {
			return $callback($key, $data[$key]);
		}, $keys->getData());
		$mapped = array_combine($keys->getData(), $mapped);
		$this->setData($mapped);
		return $this;
	}

	public function getKeys(): self
	{
		return new self(array_keys($this->getData()));
	}

	public function wrap(string $a, string $b): array
	{
		foreach ($this as $i => $value) {
			$this[$i] = $a . $value . $b;
		}

		return $this->getData();
	}

	/**
	 * Not working as expected.
	 * PHP 4 doesn't have seek() function
	 * PHP 5 doesn't have prev() method
	 * @param string $key
	 */
	public function getPrevNext($key): void
	{
		$this->where('id', $key);
		// works, but how to get next?
		# http://stackoverflow.com/questions/4792673/php-get-previous-array-element-knowing-current-array-key
		# http://www.php.net/manual/en/arrayiterator.seek.php
		$arrayobject = new ArrayObject($this);
		$iterator = $arrayobject->getIterator();

		if ($iterator->valid()) {
			$iterator->seek(array_search($key, array_keys((array)$this), true));
			$row3 = $iterator->current();
			$iterator->next();
			$next = $iterator->current();
//			$iterator->previous();
//			$iterator->previous();
			$prev = $iterator->current();
		}

//		debug($key, $row, $row2, $row3['id'], $next['id'], $prev['id']); //, $rc->data);//, $rc);
	}

	/**
	 * Filter rows where $key = $value
	 * @param string $key
	 * @param mixed $value
	 * @return static
	 */
	public function where($key, $value): self
	{
		$copy = $this->getData();
		foreach ($copy as $i => $row) {
			if ($row[$key] != $value) {
				unset($copy[$i]);
			}
		}

		// no setData() here
		return new static($copy);
	}

	/**
	 * @return mixed - single row
	 * @throws Exception
	 */
	public function findInData(array $where)
	{
		//debug($where);
		//echo new slTable($this->data);
		foreach ($this->getData() as $row) {
			$intersect1 = array_intersect_key($row, $where);
			$intersect2 = array_intersect_key($where, $row);
			if ($intersect1 === $intersect2) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * http://stackoverflow.com/a/9944080/417153
	 * @param string|int $key
	 */
	public function getPrevKey($key): false|int|string
	{
		$keys = array_keys($this->getData());
		$found_index = array_search($key, $keys, true);
		if ($found_index === false || $found_index === 0) {
			return false;
		}

		return $keys[$found_index - 1];
	}

	/**
	 * http://stackoverflow.com/a/9944080/417153
	 * @param string|int $key
	 */
	public function getNextKey($key): false|int|string
	{
		$keys = array_keys($this->getData());
		$found_index = array_search($key, $keys, true);
		if ($found_index === false || $key == end($keys)) {
			return false;
		}

		return $keys[$found_index + 1];
	}

	/**
	 * Searches inside Recursive tree
	 * @param string $needle
	 * @return array|null
	 */
	public function find($needle)
	{
		foreach ($this as $key => $val) {
			//debug($needle, $key, $val);
			if ($val instanceof Recursive) {
				$sub = new ArrayPlus($val->getChildren());
				$find = $sub->find($needle);
				//$find = $val->findPath($)
				if ($find) {
					//debug($needle, $key, $find);
					array_unshift($find, $key);
					//debug($find);
				}
			} else {
				$find = ($key == $needle) ? [$key] : null;
			}

			if ($find) {
				return $find;
			}
		}

		return null;
	}

	public function findAlternativeFromMenu($current)
	{
		/** @var Recursive|null $rec */
		foreach ($this->getData() as $rec) {
			//$found = $rec->findPath($this->current);
			if ($rec instanceof Recursive) {
				$children = $rec->getChildren();
				//debug($children, $found, $key, $this->current);
				return $children[$current] ?? null;
			}
		}

		return null;
	}

	public function first()
	{
		if ($this->count() === 0) {
			return null;
		}

		$var = $this->getData();
		reset($var);
		return current($var);
	}

	public function count(): int
	{
		return parent::count();
	}

	/**
	 * Bug. Only one element per sorted field is allowed.
	 * @param string $column
	 * @return $this
	 */
	public function sortBy($column): static
	{
		$this->insertKeyAsColumn();
		// buggy
		//$this->IDalize($column, true);	// allow merge
		//$this->ksort();

		// correct
		$copy = clone $this;
		$sortCol = $copy->column($column)->getData();
		$aCopy = $this->getData();
		array_multisort($sortCol, $aCopy);
		// Associative (string) keys will be maintained, but numeric keys will be re-indexed.
		$this->exchangeArray($aCopy);
		$this->extractKeyFromColumn();
		return $this;
	}

	/**
	 * Enters array key as ['__key__']
	 * @param string $keyColumnName
	 * @return $this
	 */
	public function insertKeyAsColumn($keyColumnName = '__key__'): static
	{
		foreach (array_keys($this->getData()) as $key) {
			$this[$key][$keyColumnName] = $key;
		}

		return $this;
	}

	/**
	 * Extracts key from array as ['__key__']
	 * @param string $column
	 * @param bool $unset
	 */
	public function extractKeyFromColumn($column = '__key__', $unset = true): static
	{
		$new = [];
		foreach ($this as $row) {
			$key = $row[$column];
			if ($unset) {
				unset($row[$column]);
			}

			$new[$key] = $row;
		}

		$this->setData($new);
		return $this;
	}

	public function transpose(): static
	{
		$out = [];
		foreach ($this as $key => $subarr) {
			foreach ($subarr as $subkey => $subvalue) {
				$out[$subkey][$key] = $subvalue;
			}
		}

		$this->setData($out);
		return $this;
	}

	public function unshift(array $column): static
	{
		reset($column);
		foreach ($this as $i => $row) {
			$this[$i] = [current($column)] + $row;
			next($column);
		}

		return $this;
	}

	public function min()
	{
		if ($this->count() !== 0) {
			return min($this->getData());
		} else {
			return null;
		}
	}

	public function max()
	{
		if ($this->count() !== 0) {
			return max($this->getData());
		} else {
			return null;
		}
	}

	public function avg(): int|float|null
	{
		$count = $this->count();
		if ($count != 0) {
			return $this->sum() / $count;
		}

		return null;
	}

	public function sum(): float|int
	{
		return array_sum($this->getData());
	}

	/**
	 * Runs get_object_vars() recursively
	 * @param array $data
	 */
	public function object2array(?array $data = null): static
	{
		$this->setData($this->objectToArray($this));
		return $this;
	}

	/**
	 * http://www.if-not-true-then-false.com/2009/php-tip-convert-stdclass-object-to-multidimensional-array-and-convert-multidimensional-array-to-stdclass-object/
	 * @param object|array|scalar $d
	 * @return array|scalar
	 */
	protected function objectToArray($d)
	{
		if (is_object($d)) {
			// Gets the properties of the given object
			// with get_object_vars function
			$d = get_object_vars($d);
		}

		if (is_array($d)) {
			/*
			* Return array converted to object
			* Using __FUNCTION__ (Magic constant)
			* for recursive call
			*/
			return array_map([$this, __FUNCTION__], $d);
		}

// Return array
		return $d;
	}

	/**
	 *    $tree = AP(array('a' => array('b' => array('c' => 'd'))));
	 * $linear = $tree->linearize();
	 * return slTable::showAssoc($linear, true, true);
	 * == "c": "d"
	 * @param array $data
	 */
	public function linearize(?array $data = null): array
	{
		$data = $data ?: $this;
		$linear = [];
		foreach ($data as $key => $val) {
			if (is_array($val) && $val) {
				$linear = array_merge($linear, $this->linearize($val));
			} else {
				$linear[$key] = $val;
			}
		}

		return $linear;
	}

	public function filterBoth($callback = null): static
	{
		$new = $callback ? array_filter($this->getData(), $callback, ARRAY_FILTER_USE_BOTH) : array_filter($this->getData());

		$this->setData($new);
		return $this;
	}

	public function filterContains($needle): static
	{
		return $this->filter(function ($el) use ($needle): bool {
			return str_contains($el, $needle);
		});
	}

	public function filter($callback = null): static
	{
		$new = $callback ? array_filter($this->getData(), $callback) : array_filter($this->getData());

		$this->setData($new);
		return $this;
	}

	/**
	 * @return mixed[]
	 */
	public function typoscript(?string $prefix = ''): array
	{
		$replace = [];
		foreach ($this as $key => $val) {
			$prefixKey = $prefix ? $prefix . '.' . $key : $key;
			if (is_array($val)) {
				$plus = self::create($val)->typoscript($prefixKey);
				$replace += $plus;
			} else {
				$replace[$prefixKey] = $val;
			}
		}

		return $replace;
	}

	/**
	 * Static initializers can be chained in PHP
	 */
	public static function create(array $data = []): self
	{
		return new self($data);
	}

	public function concat(): string
	{
		return implode('', $this->getData());
	}

	public function count_if($k): int
	{
		$count = 0;
		foreach ($this as $val) {
			if (ifsetor($val[$k])) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Searches table for specific columns and counts where $k2 is true
	 * @param       $k2
	 */
	public function count_if_sub(array $k1s, $k2): int
	{
		$count = 0;
		foreach ($this as $val) {
			foreach ($val as $key2 => $val2) {
				if (in_array($key2, $k1s) && $val2[$k2]) {
					$count++;
					break;
				}
			}
		}

		return $count;
	}

	/**
	 * Chainable
	 *
	 * @param string $i
	 * @param mixed $val
	 * @return $this
	 */
	public function set($i, $val): static
	{
		$this->offsetSet($i, $val);
		return $this;
	}

	/**
	 * Chainable
	 *
	 * @param mixed $i
	 */
	public function un_set($i): static
	{
		$this->offsetUnset($i);
		return $this;
	}

	public function get($i, $subkey = null, ...$rest): mixed
	{
		$element = $this->offsetGet($i);
		if ($subkey) {
			$element = $element[$subkey];
		}

		return $element;
	}

	public function debug(): array
	{
		return [
			'class' => get_class($this),
			'count' => $this->count(),
		];
	}

	/**
	 * @param array $ar2
	 */
	public function merge_recursive_overwrite($ar2): static
	{
		foreach ($ar2 as $key2 => $val2) {
			if (isset($this[$key2]) && is_array($this[$key2])) {
				$tmp = self::create($this[$key2]);
				$tmp->merge_recursive_overwrite($val2);
				$this[$key2] = $tmp->getData();
			} else {
				$this[$key2] = $val2;
			}
		}

		return $this;
	}

	/**
	 * 2D table => 3D table
	 * @param string $groupBy
	 * @return $this
	 */
	public function groupBy($groupBy): static
	{
		$new = [];
		foreach ($this->getData() as $line) {
			$key = $line[$groupBy];
			$new[$key][] = $line;
		}

		$this->setData($new);
		return $this;
	}

	public function sumGroups($field): ArrayPlus
	{
		$new = new ArrayPlus();
		foreach ($this->getData() as $key => $subtable) {
			$ap = ArrayPlus::create($subtable);
			$new[$key] = $ap->column($field)->sum();
		}

		return $new;
	}

	public function columnEmpty($string): bool
	{
		foreach ($this->getData() as $row) {
			if (ifsetor($row[$string])) {
				return false;
			}
		}

		return true;
	}

	public function columnSet($string): bool
	{
		foreach ($this->getData() as $row) {
			if (isset($row[$string])) {
				return true;
			}
		}

		return false;
	}

	public function findDelete($niceName): static
	{
		$ar = $this->getData();
		$index = array_search($niceName, $ar, true);
		if ($index !== false) {
			array_splice($ar, $index, 1);
			$this->setData($ar);
		}

		return $this;
	}

	public function replaceKeys(array $visibleFields): static
	{
		foreach ($visibleFields as $key => $val) {
			$this->replace_key($key, $val);
		}

		return $this;
	}

	/**
	 * @param string $oldKey
	 * @param string $newKey
	 * @throws Exception
	 */
	public function replace_key($oldKey, $newKey): static
	{
		$keys = array_keys((array)$this);
		if (false === $index = array_search($oldKey, $keys, true)) {
			throw new Exception(sprintf('Key "%s" does not exit', $oldKey));
		}

		$keys[$index] = $newKey;
		$this->exchangeArray(array_combine($keys, array_values((array)$this)));
		return $this;
	}

	/**
	 * Used in HTTP protocol
	 * @return array|string
	 */
	public function getHeaders($joinWith = null)
	{
		$headers = [];
		foreach ($this as $key => $val) {
			$headers[] = $key . ': ' . $val;
		}

		if ($joinWith) {
			$headers = implode($joinWith, $headers);
		}

		return $headers;
	}

	/**
	 * If we store array of objects, we can retrieve a specific property of all objects
	 * @param string $name
	 */
	public function getProperty($name): ArrayPlus
	{
		$result = [];
		foreach ($this->getData() as $i => $object) {
			if (is_object($object)) {
				$result[$i] = $object->$name;
			}
		}

		return new ArrayPlus($result);
	}

	public function call($method): ArrayPlus
	{
		$result = [];
		foreach ($this->getData() as $i => $object) {
			if (is_object($object)) {
				$result[$i] = $object->$method();
			}
		}

		return self::from($result);
	}

	public static function from(array $getSystems): ArrayPlus
	{
		return self::create($getSystems);
	}

	public function callMutate($method): static
	{
		$result = [];
		foreach ($this->getData() as $i => $object) {
			if (is_object($object)) {
				$result[$i] = $object->$method();
			}
		}

		$this->setData($result);
		return $this;
	}

	public function andAll()
	{
		$result = true;
		foreach ($this->getData() as $object) {
			$result = $result && $object;
		}

		return $result;
	}

	public function orAll()
	{
		$result = false;
		foreach ($this->getData() as $object) {
			$result = $result || $object;
		}

		return $result;
	}

	public function __toString(): string
	{
		return (string)json_encode($this->getArrayCopy(), JSON_PRETTY_PRINT);
	}

	/**
	 * @return string[]
	 */
	public function toStringEach(): array
	{
		$new = [];
		foreach ($this->getData() as $i => $mixed) {
			$new[$i] = (string)$mixed;
		}

		return $new;
	}

	public function combineSelf(): static
	{
		return $this->setData(array_combine(
			$this->getData(),
			$this->getData()
		));
	}

	public function stringArray(): static
	{
		$new = [];
		foreach ($this->getData() as $i => $mixed) {
			$new[$i] = MergedContent::mergeStringArrayRecursive($mixed);
		}

		$this->setData($new);
		return $this;
	}

	public function containsPartly($string): bool
	{
		foreach ($this as $element) {
			if (str_contains($element, $string)) {
				return true;
			}
		}

		return false;
	}

	public function convertTo($className): static
	{
		foreach ($this as $key => $row) {
			$instance = method_exists($className, 'getInstance') ? $className::getInstance($row) : new $className($row);

			$this[$key] = $instance;
		}

		return $this;
	}

	public function makeTable($newKey): static
	{
		$copy = [];
		foreach ($this->getData() as $key => $row) {
			$copy[$key] = is_array($row) ? $row : [
				$newKey => $row,
			];
		}

		$this->setData($copy);
		return $this;
	}

	public function sortByValue(): static
	{
		$data = $this->getData();
		asort($data);
		$this->setData($data);
		return $this;
	}

	public function sortByKey(): static
	{
		$data = $this->getData();
		ksort($data);
		$this->setData($data);
		return $this;
	}

	public function reverse(): static
	{
		$this->setData(array_reverse($this->getData()));
		return $this;
	}

	public function getSlice($from = 0, $length = null): array
	{
		return array_slice($this->getData(), $from, $length, true);
	}

	public function addColumn($columnName, $callback): static
	{
		$copy = $this->getData();
		foreach ($copy as $i => $row) {
			$copy[$i][$columnName] = call_user_func($callback, $row, $i);
		}

		$this->setData($copy);
		return $this;
	}

	public function values(): static
	{
		$this->setData(array_values($this->getData()));
		return $this;
	}

	/**
	 * @param ArrayPlus|array $ap
	 */
	public function diff($ap): static
	{
		$new = clone($this);
		$ap = $ap instanceof ArrayPlus ? $ap->getData() : $ap;
		$new->setData(array_diff($new->getData(), $ap));
		return $new;
	}

	/**
	 * Filter rows where $key = $value
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	public function filterWhere($key, $value): static
	{
		$copy = $this->getData();
		foreach ($copy as $i => $row) {
			if ($row[$key] != $value) {
				unset($copy[$i]);
			}
		}

		$this->setData($copy);
		return $this;
	}

	public function filterBy(array $where): static
	{
		//		debug($where, sizeof($this->events));
		$this->setData(
			array_filter($this->getData(), static function (array|object $row) use ($where): bool {
				//			$same = array_intersect_key((array)$row, $where);

				$okList = [];
				foreach ($where as $k => $v) {
//					if (is_object($v)) {
					//					var_dump($v);
//					}

					if ($v instanceof FilterBetween) {
						$ok = $v->apply($row->$k);
					} elseif (is_array($v)) {
						$ok = in_array($row->$k, $v);
					} else {
						$value = is_object($row) ? $row->$k : ifsetor($row[$k]);
						$ok = $v == $value;
					}

					$okList[$k] = $ok;
				}

				$okList = array_filter($okList);
				//			debug($where, $okList);
				return count($okList) === count($where);
			})
		);
		return $this;
	}

	public function apply(callable $fn): void
	{
		$this->map($fn);
	}

	/**
	 * Keys are reindexed
	 * @param callable $callback
	 */
	public function map($callback): static
	{
		$this->setData(array_map($callback, $this->getData()));
		return $this;
	}

	public function has($el): bool
	{
		return in_array($el, $this->getArrayCopy());
	}

	public function __debugInfo(): array
	{
		return [
			'count' => $this->count(),
			'is_assoc' => self::has_string_keys($this->getArrayCopy()),
		];
	}

	/**
	 * http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
	 * @static because it's used in the constructor of VisibleColumns
	 */
	public static function has_string_keys(array $array): bool
	{
		return array_filter(array_keys($array), 'is_string') !== [];
	}

	public function reindex(callable $keyGenerator): ArrayPlus
	{
		$new = new ArrayPlus();
		foreach ($this as $key => $val) {
			$newKey = $keyGenerator($key, $val);
			$new[$newKey][] = $val;
		}

		return $new;
	}

	public function reindexOne(callable $keyGenerator): ArrayPlus
	{
		$new = new ArrayPlus();
		foreach ($this as $key => $val) {
			$newKey = $keyGenerator($key, $val);
			$new[$newKey] = $val;
		}

		return $new;
	}

	/**
	 * @return int[]
	 */
	public function countEach(): array
	{
		$set = [];
		foreach ($this as $key => $val) {
			$set[$key] = is_array($val) ? count($val) : 1;
		}

		return $set;
	}

	public function insertBefore($key, $content): static
	{
		$indexes = array_keys($this->getArrayCopy());
		$intPos = array_search($key, $indexes, true);
		$beforeKeys = $intPos ? array_slice($indexes, 0, $intPos) : [];

		$values = array_values($this->getArrayCopy());
		$before = array_combine($beforeKeys, array_slice($values, 0, $intPos));
//		debug($indexes, $intPos, $beforeKeys, $before);
		// insert
		$before[] = $content;
		// add remaining
		$afterKeys = array_slice($indexes, $intPos);
		$together = array_merge($before, array_combine($afterKeys, array_slice($values, $intPos)));
		$this->setData($together);
		return $this;
	}

	public function insertAfter($position, $insert): static
	{
		if (is_int($position)) {
			$copy = $this->getArrayCopy();
			array_splice($copy, $position, 0, $insert);
			$this->setData($copy);
		} else {
			$pos = array_search($position, array_keys($this->getArrayCopy()), true);
			if (false === $pos) {
				throw new Exception('position ' . $position . ' not found');
			}

			$array = array_merge(
				array_slice($this->getArrayCopy(), 0, $pos + 1),
				$insert,
				array_slice($this->getArrayCopy(), $pos + 1)
			);
			$this->setData($array);
		}

		return $this;
	}

	public function without(array $keys): self
	{
		foreach ($keys as $key) {
			unset($this[$key]);
		}

		return $this;
	}

	// untested from https://stackoverflow.com/questions/3797239/insert-new-item-in-array-on-any-position-in-php

	public function sort($callback): static
	{
		$data = $this->getArrayCopy();
		usort($data, $callback);
		$this->setData($data);
		return $this;
	}

	public function any(Closure $check): bool
	{
		foreach ($this->getData() as $el) {
			$ok = $check($el);
			if ($ok) {
				return true;
			}
		}

		return false;
	}

	public function all(Closure $check): bool
	{
		foreach ($this->getData() as $el) {
			$ok = $check($el);
			if (!$ok) {
				return false;
			}
		}

		return true;
	}

	public function none(Closure $check): bool
	{
		foreach ($this->getData() as $el) {
			$ok = $check($el);
			if ($ok) {
				return false;
			}
		}

		return true;
	}

	public function toArray(): array
	{
		return $this->getData();
	}

	public function isEmpty(): bool
	{
		return $this->getData() === [];
	}

	public function containsAny(ArrayPlus $anotherList): bool
	{
		foreach ($this as $el) {
			if ($anotherList->includes($el)) {
				return true;
			}
		}

		return false;
	}

	public function includes($id): bool
	{
		return $this->contains($id);
	}

	public function contains($string): bool
	{
		return in_array($string, $this->getData());
	}

	public function containsAll(ArrayPlus $anotherList): bool
	{
		foreach ($this as $el) {
			if (!$anotherList->includes($el)) {
				return false;
			}
		}

		return true;
	}

	public function join(string $string): string
	{
		return $this->implode($string);
	}

	public function implode($sep = "\n"): string
	{
		return implode($sep, $this->getData());
	}

	public function toInt(): ArrayPlus
	{
		return self::from($this->map(fn($x): int => (int)$x)->getData());
	}

	public function toFloat(): ArrayPlus
	{
		return self::from($this->map(fn($x): float => (float)$x)->getData());
	}

	public function toString(): ArrayPlus
	{
		return self::from($this->map(fn($x): string => (string)$x)->getData());
	}

//	public function toArray()
//	{
//		return self::from($this->map(fn ($x) => (array) $x)->getData());
//	}

	public function toObject(): ArrayPlus
	{
		return self::from($this->map(fn($x): stdClass => (object)$x)->getData());
	}
}

function AP($a = [])
{
	if ($a instanceof ArrayPlus) {
		return $a;
	}

	if (is_array($a)) {
		return ArrayPlus::create($a);
	}

	throw new InvalidArgumentException(__METHOD__ . ' accepts array');
}
