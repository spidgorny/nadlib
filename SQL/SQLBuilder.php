<?php

/**
 * Class SQLBuilder - contains database unspecific (general) SQL functions.
 * It has $this->db a database specific (PostgreSQL, MySQL, SQLite, Oracle, PDO) class
 * which is performing the actual queries.
 * This $db class has a back reference to this as $this->db->qb == $this.
 * Usage in controllers/models:
 * $this->db = new MySQL();
 * $this->db->qb = new SQLBuilder();
 * $this->db->qb->db = $this->db;
 * $this->db->fetchSelectQuery(...);
 *
 * Note that the creation of objects above is handled by DIContainer
 * but it's not shown above for comprehensibility.
 * @mixin DBLayerBase
 * @method describeView($viewName)
 * @method getFirstValue($query)
 * @method performWithParams($query, $params)
 * @method getInfo()
 * @method getConnection()
 * @method getViews()
 * @method getScheme()
 * @method quoteKeys(array $keys)
 * @method quoteKey($key)
 * @method perform($query, array $params = [])
 * @method fetchAssoc($res)
 */
class SQLBuilder
{

	/**
     * @var string
     */
    public $lastQuery;

    /**
	 * Update/Insert is storing the found row for debugging
	 * @var mixed
	 */
	public $found;

	/**
	 * @var DBInterface
	 */
	public $db;

	/**
	 * @var Config
	 */
	public $config;

	public $logToLog = false;

	public function __construct(DBInterface $db)
	{
		if (class_exists('Config')) {
			$this->config = Config::getInstance();
		}

		$this->db = $db;
	}

	/**
     * 2010/09/12: modified according to mantis request 0001812    - 4th argument added
     * @param $array
     * @param $field
     * @param string $conditioner
     */
    public static function array_intersect($array, string $field, string $joiner = 'OR', $conditioner = 'ANY'): string
	{
		//$res[] = "(string_to_array('".implode(',', $value)."', ',')) <@ (string_to_array(bug.".$field.", ','))";
		// why didn't it work and is commented?

		//debug($array);
		if (count($array) !== 0) {
			$or = [];
			foreach ($array as $langID) {
				//2010/09/12: modified according to mantis request 0001812	- if/else condition for 4th argument added
				if ($conditioner === 'ANY') {
					$or[] = "'" . $langID . "' = ANY(string_to_array(" . $field . ", ','))"; // this line is the original one
				} else {
					$or[] = "'" . $langID . "' = " . $field . " ";
				}
			}

			$content = '(' . implode(' ' . $joiner . ' ', $or) . ')';
		} else {
			$content = ' 1 = 1 ';
		}

		return $content;
	}

	/**
     * @param $table
     * @param string $addSelect
     * @throws Exception
     * @throws MustBeStringException
     */
    public function getSelectQueryString($table, array $where = [], string $order = "", $addSelect = ''): string
	{
		$table1 = $this->getFirstWord($table);
		if ($table == $table1) {
			$from = $this->db->quoteKey($table);    // table name always quoted
		} else {
			$from = $table; // not quoted
		}

		$select = $addSelect ?: $this->quoteKey($table1) . ".*";
		$q = "SELECT {$select}\nFROM " . $from;
		$set = $this->quoteWhere($where);
		if ($set !== []) {
			$q .= "\nWHERE\n" . implode("\nAND ", $set);
		}

		return $q . ("\n" . $order);
	}

	public static function getFirstWord($table)
	{
		if (!$table) {
			throw new InvalidArgumentException(__METHOD__ . ' called on [' . $table . ']');
		}

		$table1 = trimExplode(' ', $table);
		$table0 = $table1[0];
		$table1 = trimExplode("\t", $table0);
		$table0 = $table1[0];
		$table1 = trimExplode("\n", $table0);
		//debug($table, $table1, $table0);
		return $table1[0];
	}

	/**
     * Quotes the values as quoteValues does, but also puts the key out and the correct comparison.
     * In other words, it takes care of col = 'NULL' situation and makes it 'col IS NULL'
     *
     * @throws MustBeStringException
     * @throws Exception
     */
    public function quoteWhere(array $where): array
	{
		$set = [];
		foreach ($where as $key => $val) {
			$endsWithDot = is_string($key) && $key[strlen($key) - 1] !== '.';
			if (!is_string($key) || $endsWithDot) {
				$equal = new SQLWhereEqual($key, $val);
				$equal->injectDB($this->db);
				$set[] = $equal->__toString();
			}
		}

		//debug($set);
		return $set;
	}

	public function getDefaultInsertFields(): array
	{
		return [];
	}

	public function runSelectQuerySW($table, SQLWhere $where, $order = '', $addSelect = '')
	{
		$query = $this->getSelectQuerySW($table, $where, $order, $addSelect);
		//debug($query);
		return $this->db->perform($query);
	}

	public function getSelectQuerySW($table, SQLWhere $where, $order = "", $addSelect = ''): \SQLSelectQuery
	{
		$table1 = $this->getFirstWord($table);
		$select = $addSelect ?: $this->quoteKey($table1) . ".*";
		return $this->getSelectQuery($table, $where, $order, $select);
	}

	public function getSelectQuery($table, $where = [], $orderAndLimit = '', $addSelect = ''): \SQLSelectQuery
	{
		$sqlWhere = $where instanceof SQLWhere ? $where : new SQLWhere($where);
		if (strpos($orderAndLimit, 'LIMIT') > 0) {  // ORDER BY xxx LIMIT yyy
			[$order, $limit] = explode('LIMIT', $orderAndLimit);
			$limit = 'LIMIT ' . $limit;  // fix after split
		} elseif (str_startsWith($orderAndLimit, 'ORDER')) {
			$order = $orderAndLimit;
			$limit = '';
		} elseif (str_startsWith($orderAndLimit, 'LIMIT')) {
			$order = '';
			$limit = $orderAndLimit;
		} else {
			$order = '';
			$limit = '';
		}

//		llog($orderAndLimit, '=>', $order, $limit);

		$orderBy = str_startsWith($order, 'ORDER') ? new SQLOrder($order) : null;
		$limitBy = str_startsWith($limit, 'LIMIT') ? new SQLLimit(
			str_replace('LIMIT', '', $limit)
		) : null;
		$groupBy = str_startsWith($orderAndLimit, 'GROUP') ? new SQLGroup($orderAndLimit) : null;

		return new SQLSelectQuery(
			$this->db,
			new SQLSelect($addSelect ?? '*'),
			new SQLFrom($table),
			$sqlWhere,
			null,
			$groupBy,
			null,
			$orderBy,
			$limitBy);
	}

	/**
     * Will search for $where and then either
     * - update $fields + $where or
     * - insert $fields + $where + $insert
     * @param $table
     * @return bool|int
     * @throws MustBeStringException
     */
    public function runInsertUpdateQuery($table, array $fields, array $where, array $insert = [])
	{
		TaylorProfiler::start(__METHOD__);
		$this->db->transaction();
		$res = $this->runSelectQuery($table, $where);
		$this->found = $this->fetchAssoc($res);
		if ($this->db->numRows($res)) {
			$query = $this->getUpdateQuery($table, $fields, $where);
			$this->perform($query);
			$inserted = $this->found['id'];
		} else {
			$query = $this->getInsertQuery($table, $fields + $where + $insert);
			// array('ctime' => NULL) # TODO: make it manually now
			$res = $this->perform($query);
			$inserted = $this->db->lastInsertID($res, $table);
		}

		//debug($query);
		$this->db->commit();
		$this->lastQuery = $query;  // overwrite 'commit'
		TaylorProfiler::stop(__METHOD__);
		return $inserted;
	}

	public function runSelectQuery($table, array $where = [], $order = '', $addSelect = '')
	{
		$query = $this->getSelectQuery($table, $where, $order, $addSelect);
		//debug($query);
		return $this->db->perform($query);
	}

	/**
     * @param string $table
     * @param array $columns
     * @throws MustBeStringException
     */
    public function getUpdateQuery($table, $columns, array $where, string $orderBy = ''): string
	{
		//$columns['mtime'] = date('Y-m-d H:i:s');
		$table = $this->quoteKey($table);
		$q = "UPDATE {$table}\nSET ";
		$set = $this->quoteLike($columns, '$key = $val');
		$q .= implode(",\n", $set);
		$q .= "\nWHERE\n";
		$q .= implode("\nAND ", $this->quoteWhere($where));
		return $q . (' ' . $orderBy);
	}

	/**
	 *
	 * @param $columns [a => b, c => d]
	 * @param $like "$key ILIKE '%$val%'"
	 * @return array    [a ILIKE '%b%', c ILIKE '%d%']
	 * @throws MustBeStringException
	 */
	public function quoteLike($columns, $like): array
	{
		$set = [];
		foreach ($columns as $key => $val) {
			$key = $this->quoteKey($key);
			$val = $this->quoteSQL($val, $key);
			$from = ['$key', '$val'];
			$to = [$key, $val];
			$set[] = str_replace($from, $to, $like);
		}

		//d($_POST, $_REQUEST, $columns, $set, ini_get('magic_quotes_gpc'), get_magic_quotes_gpc(), get_magic_quotes_runtime());
		return $set;
	}

	/**
	 * Used to really quote different values so that they can be attached to "field = "
	 *
	 * @param mixed $value
	 * @param string $key
	 * @return string
	 * @throws MustBeStringException
	 */
	public function quoteSQL($value, $key = null)
	{
		if ($value instanceof SQLNow) {     // check subclass first
			$value->injectDB($this->db);
			return $value . '';
		}

		if ($value instanceof AsIsOp) {     // check subclass first
			$value->injectDB($this->db);
			$value->injectField($key);
			return $value->__toString();
		}

		if ($value instanceof AsIs) {
			$value->injectDB($this->db);
			//$value->injectField($key); not needed as it will make the field name twice
			return $value->__toString();
		}

		if ($value instanceof SQLOr) {
			return $value->__toString();
		}

		if ($value instanceof Time) {
			return $this->quoteSQL($value->getMySQL(), $key);
		}

		if ($value instanceof SQLDate) {
			//debug($content, $value);
			return "'" . $this->db->escape($value->__toString()) . "'";
		}

		if ($value instanceof Time) {
			//debug($content);
			return "'" . $this->db->escape($value->toSQL()) . "'";
		}

		if ($value instanceof SimpleXMLElement && $this->getScheme() == 'mysql') {
			return "COMPRESS('" . $this->db->escape($value->asXML()) . "')";
		}

		if (is_object($value)) {
			if ($value instanceof stdClass) {
				debug($value);
			}

			return "'" . $this->db->escape((string)$value) . "'";
		}

		if ($value === null) {
			return "NULL";
		}

		if (is_numeric($value) && !$this->isExp($value)) {
			//$set[] = "($key = ".$val." OR {$key} = '".$val."')";
			return "'" . $value . "'";    // quoting will not hurt, but will keep leading zeroes if necessary
			// /* numeric */";		// this makes SQLQuery not work
		}

		if (is_bool($value)) {
			//debug($value, $key, get_class($this->db), $res);
			return $this->db->escapeBool($value);
		}

		if (is_scalar($value)) {
			$sql = "'" . $this->db->escape($value) . "'";
			if ($this->db->getScheme() == 'ms') {
				$sql = 'N' . $sql;    // UTF-8 encoding
			}

			return $sql;
		}

		debug([
			'key' => $key,
			'value' => $value,
			'problem' => 'MustBeStringException',
		]);
		throw new MustBeStringException('Must be string.');
	}

	/**
     * http://stackoverflow.com/a/4964120
     * @param $number
     */
    public function isExp($number): bool
	{
		return is_numeric($number) && $number != number_format($number, 0, '', '');
	}

	/**
     * @param string $table Table name
     * @param array $columns array('name' => 'John', 'lastname' => 'Doe')
     * @throws MustBeStringException
     */
    public function getInsertQuery($table, array $columns, array $where = []): string
	{
		$fields = implode(", ", $this->quoteKeys(array_keys($columns)));
		$values = implode(", ", $this->quoteValues(array_values($columns)));
		$table = $this->quoteKey($table);
		$q = sprintf('INSERT INTO %s (%s) ', $table, $fields);
		if ($where !== []) {
			$q .= sprintf('SELECT %s ', $values);
			$q .= 'WHERE ' . implode(' AND ', $this->quoteWhere($where));
		} else {
			$q .= sprintf('VALUES (%s)', $values);
		}

		return $q;
	}

	/**
     * Quotes the complete array if necessary.
     *
     * @throws MustBeStringException
     */
    public function quoteValues(array $a): array
	{
		//		debug(__METHOD__, $a);
		$c = [];
		foreach ($a as $key => $b) {
			$c[] = $this->quoteSQL($b, $key);
		}

		return $c;
	}

	/**
     * Inserts only if not yet found.
     *
     * @param $table
     * @return resource
     * @throws Exception
     */
    public function runInsertNew($table, array $fields, array $insert = [])
	{
		TaylorProfiler::start(__METHOD__);
		$resInsert = null;
		$res = $this->runSelectQuery($table, $fields);
		if (!$this->db->numRows($res)) {
			$query = $this->getInsertQuery($table, $fields + $insert);
			//debug($query);
			$resInsert = $this->db->perform($query);
		}

		TaylorProfiler::stop(__METHOD__);
		return $resInsert;
	}

	public function runReplaceQuery(string $table, array $columns, array $primaryKeys)
	{
		TaylorProfiler::start(__METHOD__ . '(' . $table . ')');
		$ret = $this->db->runReplaceQuery($table, $columns, $primaryKeys);
		TaylorProfiler::stop(__METHOD__ . '(' . $table . ')');
		return $ret;
	}

	public function getFoundOrLastID($inserted)
	{
		return $inserted ? $this->db->lastInsertID($inserted) : $this->found['id'];
	}

	/**
     * Return ALL rows
     * This used to retrieve a single row !!!
     * @param string $table
     * @param array $where
     * @param string $order
     * @param string $addFields
     * @param string $idField - will return data as assoc indexed by this column
     */
    public function fetchSelectQuery($table, $where = [], $order = '', $addFields = '', $idField = null): array
	{
		// commented to allow working with multiple MySQL objects (SQLBuilder instance contains only one)
		//$res = $this->runSelectQuery($table, $where, $order, $addFields);
		$query = $this->getSelectQuery($table, $where, $order, $addFields);

		//debug($query); if ($_COOKIE['debug']) { exit(); }

		$res = $this->perform($query);
		return $this->fetchAll($res, $idField);
	}

	/**
     * @param resource|string $res
     * @param string $key can be set to NULL to avoid assoc array
     */
    public function fetchAll($res, $key = null): array
	{
		TaylorProfiler::start(__METHOD__);
		if (is_string($res) || $res instanceof SQLSelectQuery) {
			$res = $this->db->perform($res);
		}

		$data = [];
		do {
			$row = $this->db->fetchAssoc($res);
			if ($row === false || $row == [] || $row === null) {
				break;
			}

			if ($key) {
				if (!isset($row[$key])) {
					debug($key, $row);
				}

				$keyValue = $row[$key];
				$data[$keyValue] = $row;
			} else {
				$data[] = $row;
			}
		} while (true);

		//debug($this->lastQuery, sizeof($data));
		//debug_pre_print_backtrace();
		$this->db->free($res);
		TaylorProfiler::stop(__METHOD__);
		return $data;
	}

	/**
     * Originates from BBMM
     * @param string $sword
     */
    public function getSearchWhere($sword, array $fields): array
	{
		$where = [];
		$words = $this->getSplitWords($sword);
		foreach ($words as $word) {
			$like = [];
			foreach ($fields as $field) {
				$like[] = $field . " LIKE '%" . $this->db->escape($word) . "%'";
			}

			$where[] = new AsIsOp(' (' . implode(' OR ', $like) . ')');
		}

		//debug($where);
		return $where;
	}

	/**
     * @return mixed[]
     */
    public function getSplitWords($sword): array
	{
		$sword = trim($sword);
		$words = explode(' ', $sword);
		$words = array_map('trim', $words);
		$words = array_filter($words);
		$words = array_unique($words);
		//$words = $this->combineSplitTags($words);
		$words = array_values($words);
		return $words;
	}

	/**
     * @return string[]
     */
    public function combineSplitTags($words): array
	{
		$new = [];
		$i = 0;
		$in = false;
		foreach ($words as $word) {
			$word = new StringPlus($word);
			if ($word->contains('[')) {
				++$i;
				$in = true;
			}

			$new[$i] = $new[$i] !== '' && $new[$i] !== '0' ? $new[$i] . ' ' . $word : $word . '';
			if (!$in || ($in && $word->contains(']'))) {
				++$i;
				$in = false;
			}
		}

		//debug(array($words, $new));
		return $new;
	}

	public function runDeleteQuery($table, array $where)
	{
		$delete = $this->getDeleteQuery($table, $where);
		$w = new SQLWhere($where);
		$params = $w->getParameters();
		$delete = $w->replaceParams($delete);
		//		debug($delete, $params);
		return $this->db->perform($delete, $params);
	}

	/**
     * @param string $table
     * @param string $what [LOW_PRIORITY] [QUICK] [IGNORE]
     * @throws MustBeStringException
     * @throws Exception
     */
    public function getDeleteQuery($table, array $where = [], string $what = ''): string
	{
		$q = "DELETE " . $what . " FROM " . $this->db->quoteKey($table) . " ";
		$set = $this->quoteWhere($where);
		if ($set !== []) {
			$q .= "\nWHERE " . implode(" AND ", $set);
		} else {
			$q .= "\nWHERE 1 = 0"; // avoid truncate()
		}

		return $q;
	}

	public function __call($method, array $params)
	{
		return call_user_func_array([$this->getDB(), $method], $params);
	}

	public function getDB()
	{
		return $this->db = $this->db ?: $this->config->getDB();
	}

	/**
     * @return mixed[]
     */
    public function getTableOptions(string $table, $titleField, $where = [], $order = null, string $idField = 'id', $prefix = null, ?string $addFields = ''): array
	{
		$prefix = $prefix ?: $table . '.';

		if (!str_contains($titleField, ' AS ')) {
			$addSelect = 'DISTINCT ' . $prefix . $this->quoteKey($titleField) . ' AS title,';
		} else {
			$addSelect = $titleField;
		}

		$query = $this->getSelectQuery(
			$table,
			$where,
			$order,
			$addSelect . ' ' .
			$this->quoteKey($prefix . $idField) . ' AS id_field' .
			($addFields ? ', ' . $addFields : '')
		);

		// $prefix.'*, is not selected as DISTINCT will not work

//		llog('Query', $query . '');
		$res = $this->perform($query);
		$data = $this->fetchAll($res, 'id_field');
		$keys = array_keys($data);
		$values = array_map(static function (array $arr) {
			return $arr["title"];
		}, $data);
        //d($keys, $values);
        $options = $keys && $values ? array_combine($keys, $values) : [];

		//debug($this->db->lastQuery, @$this->db->numRows($res), $titleField, $idField, $data, $options);
		//		$options = AP($data)->column_assoc($idField, $titleField)->getData();
		return $options;
	}

	/**
	 * @param string $query
	 * @param string|null $className - if provided it will return DatabaseInstanceIterator
	 * @return DatabaseInstanceIterator|DatabaseResultIteratorAssoc
	 * @throws DatabaseException
	 */
	public function getIterator($query, $className = null)
	{
		if ($className) {
			$f = new DatabaseInstanceIterator($this->db, $className);
			if (is_string($query)) {
				$f->perform($query);
			} else {
				$f->setResult($query);
			}

			return $f;
		}

		if ($this->db instanceof DBLayerPDO) {
			return $this->db->perform($query);
		}

		if (is_string($query)) {
			$f = new DatabaseResultIteratorAssoc($this->db);
			$f->perform($query);
			return $f;
		}

		if (is_resource($query)) {
			$f = new DatabaseResultIteratorAssoc($this->db);
			$f->setResult($query);
			return $f;
		}

		throw new InvalidArgumentException(__METHOD__ . ' __/(:-)\__ ' . $query);
	}

	public function fetchOneSelectQuery($table, $where = [], $order = '', $selectPlus = '')
	{
		$query = $this->getSelectQuery($table, $where, $order, $selectPlus);
		if (!str_contains($query->__toString(), 'LIMIT')) {
			// speed improvement
			$query->setLimit(new SQLLimit(1));
		}

		if ($this->logToLog) {
			llog($query . '', $query->getParameters(), get_class($this->db), $this->db->getConnection());
		}

		$res = $this->db->perform($query, $query->getParameters());
		if ($this->logToLog) {
			llog('$res', $res);
		}

		return $this->db->fetchAssoc($res);
	}

	public function runUpdateInsert($table, array $set, array $where): string
	{
		$found = $this->runSelectQuery($table, $where);
		if ($this->db->numRows($found)) {
			$res = 'update';
			$this->runUpdateQuery($table, $set, $where);
		} else {
			$res = 'insert';
			$this->runInsertQuery($table, $set + $where);
		}

		return $res;
	}

	public function runUpdateQuery($table, array $columns, array $where, string $orderBy = '')
	{
		$query = $this->getUpdateQuery($table, $columns, $where, $orderBy);
		return $this->db->perform($query);
	}

	public function runInsertQuery(string $table, array $columns, array $where = [])
	{
		TaylorProfiler::start(__METHOD__ . '(' . $table . ')');
		$query = $this->getInsertQuery($table, $columns, $where);
		$ret = $this->db->perform($query);
		TaylorProfiler::stop(__METHOD__ . '(' . $table . ')');
		return $ret;
	}

	/**
     * @param string $table
     * @param string $order
     * @param string $selectPlus
     * @param $key
     * @return array[]
     */
    public function fetchAllSelectQuery($table, array $where, $order = '', $selectPlus = '', $key = null)
	{
		$res = $this->runSelectQuery($table, $where, $order, $selectPlus);
		return $this->db->fetchAll($res, $key);
	}

	public function getWhereString(array $where): string
	{
		$set = $this->quoteWhere($where);
		return implode(' AND ', $set);
	}

	/**
     * The query is supposed to return two columns only
     * @param $query
     */
    public function fetchOptions($query): array
	{
		$data = [];
        $result = is_string($query) || $query instanceof SQLSelectQuery ? $this->perform($query) : $query;

		$row = $this->fetchAssoc($result);
		while ($row != false && $row != null) {
			[$key, $val] = array_values($row);
			$data[$key] = $val;

			$row = $this->fetchAssoc($result);
		}

		return $data;
	}

	public function getCount(SQLSelectQuery $query)
	{
		$queryWithoutOrder = clone $query;
		$queryWithoutOrder->unsetOrder();

		$subQuery = new SQLSubquery($queryWithoutOrder, 'counted');
		$subQuery->setParameters($query->getParameters());

		$query = new SQLSelectQuery($this->db,
			new SQLSelect('count(*) AS count'),
			$subQuery
		);
		$query->injectDB($this->db);

		$res = $query->fetchAssoc();
		//		debug($res);
		return ifsetor($res['count']);
	}

	public function getReserved()
	{
		if ($this->db instanceof DBLayerPDO) {
			if ($this->db->isMySQL()) {
				return [];
			}

			if ($this->db->isPostgres()) {
				return (new DBLayer())->getReserved();
			}

			return [];
		}

		return [];
	}
}
