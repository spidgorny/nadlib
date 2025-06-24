<?php

/**
 * Class SQLOr - the parameters inside SHOULD contain key => value pairs.
 * This may not be used as an alternative to 'makeOR'. Use SQLIn instead.
 */

class SQLOr extends SQLWherePart
{

	/**
	 * @var Array<SQLLike|string> $or
	 */
	protected array $or;

	/**
	 * @var DBInterface
	 */
	protected $db;

	protected $join = ' OR ';

	public function __construct(array $ors)
	{
		parent::__construct();
		$this->or = $ors;
	}

	/**
	 * Please make SQLOrBijou, SQLOrORS and so on classes.
	 * This one should be just simple general.
	 * @throws MustBeStringException
	 */
	public function __toString(): string
	{
		$ors = [];
//		llog(typ($this->db)->cli());
		if (!$this->db) {
			ob_start();
			debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$bt = ob_get_clean();
			llog($bt);
			$e = new RuntimeException('SQLOr does not have $db set');
			trigger_error($e, E_USER_ERROR);
//			throw $e;	// unable to throw, return without quoteWhere()
		} else {                        // MySQL
			$ors = $this->db->quoteWhere($this->or);
//			llog($this->or, $ors);
		}


		//debug($this, $ors, $res);
		return $ors ? '(' . implode($this->join, $ors) . ')' : '/* EMPTY OR */';
	}

	public function dciStyle(): mixed
	{
		$ors = [];
		// DCI, ORS
		// where is it used? in ORS for sure, but make sure you don't call new SQLOr(array('a', 'b', 'c'))
		// https://ors.nintendo.de/NotifyVersion
		if (is_int($this->field)) {
			// added is_int condition to solve problem
			// with software mngmt & request (hw/sw request)  .. deklesoe 20130514
			foreach ($this->or as $field => $or) {
				$tmp = $this->db->quoteWhere(
					[trim($field) => $or]
				//array($this->field => $or)    //  commented and replaced with line above due to problem
				//  with query creation for software management .. deklesoe 20130514
				//$or
				);
				$ors[] = implode('', $tmp);
			}
		} else {
			foreach ($this->or as $or) {
				$tmp = $this->db->quoteWhere(
					[trim($this->field) => $or]
				//$or
				);
				$ors[] = implode('', $tmp);
			}

			// alternative to investigate
//			foreach ($this->or as $field => $p) {
//				if ($p instanceof SQLWherePart) {
//					$p->injectField($field);
//					$p->injectDB($this->db);
//				}
//			}

			$ors = $this->db->quoteWhere($this->or);
		}

		return first($ors);
	}

	public function debug(): array
	{
		return [$this->field => $this->or];
	}

	public function getParameter(): array
	{
		$params = [];
		foreach ($this->or as $sub) {
			if ($sub instanceof SQLWherePart) {
				$plus = $sub->getParameter();
				if ($plus) {
					$params[] = $plus;
				}
			}
		}

		return $params;
	}

}
