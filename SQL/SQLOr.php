<?php

/**
 * Class SQLOr - the parameters inside SHOULD contain key => value pairs.
 * This may not be used as an alternative to 'makeOR'. Use SQLIn instead.
 */

class SQLOr extends SQLWherePart
{

	protected $or = [];

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

	public function inject(DBInterface $db)
	{
		$this->db = $db;
		foreach ($this->or as $p) {
			if ($p instanceof SQLWherePart) {
				$p->injectDB($this->db);
			}
		}
		return $this;
	}

	/**
	 * Please make SQLOrBijou, SQLOrORS and so on classes.
	 * This one should be just simple general.
	 * @return string
	 * @throws MustBeStringException
	 */
	public function __toString()
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
		} elseif (false && $this->db instanceof DBLayerPG) {
			$ors[] = $this->bijouStyle();
		} elseif (false && $this->db instanceof DBLayer) {
			$ors[] = $this->dciStyle();
		} else {                        // MySQL
			$ors = $this->db->quoteWhere($this->or);
//			llog($this->or, $ors);
		}

		if ($ors) {
			$res = '(' . implode($this->join, $ors) . ')';
		} else {
			$res = '/* EMPTY OR */';
		}
		//debug($this, $ors, $res);
		return $res;
	}

	public function bijouStyle()
	{
		// bijou
		$ors = [];
		foreach ($this->or as $key => $or) {
			if ($this->is_main($key)) {
				$ors[] = $this->db->getWherePart([
					$key => $or,
					$key . '.' => $this->or[$key . '.'],
				], false);
			}
		}
		return first($ors);
	}

	private function is_main($key)
	{
		return $key[0] != '.';
	}

	public function dciStyle()
	{
		$ors = [];
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
		} elseif (!is_int($this->field)) {
			foreach ($this->or as $field => $or) {
				$tmp = $this->db->quoteWhere(
					[trim($this->field) => $or]
				//$or
				);
				$ors[] = implode('', $tmp);
			}
		} else {
			foreach ($this->or as $field => $p) {
				if ($p instanceof SQLWherePart) {
					$p->injectField($field);
					$p->injectDB($this->db);
				}
			}
			$ors = $this->db->quoteWhere($this->or);
		}
		return first($ors);
	}

	public function injectField($field)
	{
		$this->field = $field;
		foreach ($this->or as $p) {
			if ($p instanceof SQLWherePart) {
//				$p->injectField($field);
			}
		}
		return $this;
	}

	public function debug()
	{
		return [
			'class' => get_class($this),
			'field' => $this->field,
			'or' => collect($this->or)->map(function ($sub) {
				return $sub->debug();
			})->toArray()
		];
	}

	public function getParameter()
	{
		$params = [];
		/**
		 * @var string $field
		 * @var SQLLike $sub
		 */
		foreach ($this->or as $sub) {
			if ($sub instanceof SQLWherePart) {
				$plus = $sub->getParameter();
				if ($plus) {
					$params[] = $plus;
				}
			}
		}
//		llog($this->debug(), '=>', $params);
		return $params;
	}

}
