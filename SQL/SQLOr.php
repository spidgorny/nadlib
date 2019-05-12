<?php

/**
 * Class SQLOr - the parameters inside SHOULD contain key => value pairs.
 * This may not be used as an alternative to 'makeOR'. Use SQLIn instead.
 */

class SQLOr extends SQLWherePart
{

	protected $or = array();

	/**
	 * @var DBInterface
	 */
	protected $db;

	protected $join = ' OR ';

	public function __construct(array $ors)
	{
		//parent::__construct();
		$this->or = $ors;
		$this->db = Config::getInstance()->getDB();
	}

	/**
	 * Please make SQLOrBijou, SQLOrORS and so on classes.
	 * This one should be just simple general.
	 * @return string
	 * @throws MustBeStringException
	 */
	public function __toString()
	{
		$ors = array();
		//debug(get_class($this->db));
		if (false && $this->db instanceof DBLayerPG) {
			$ors[] = $this->bijouStyle();
		} elseif (false && $this->db instanceof DBLayer) {
			$ors[] = $this->dciStyle();
		} else {                        // MySQL
			$ors = $this->db->quoteWhere($this->or);
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
		$ors = array();
		foreach ($this->or as $key => $or) {
			if ($this->is_main($key)) {
				$ors[] = $this->db->getWherePart(array(
					$key => $or,
					$key . '.' => $this->or[$key . '.'],
				), false);
			}
		}
		return first($ors);
	}

	public function dciStyle()
	{
		$ors = array();
		// DCI, ORS
		// where is it used? in ORS for sure, but make sure you don't call new SQLOr(array('a', 'b', 'c'))
		// http://ors.nintendo.de/NotifyVersion
		if (is_int($this->field)) {                 // added is_int condition to solve problem with software mngmt & request (hw/sw request)  .. deklesoe 20130514
			foreach ($this->or as $field => $or) {
				$tmp = $this->db->quoteWhere(
					array(trim($field) => $or)
				//array($this->field => $or)    //  commented and replaced with line above due to problem
				//  with query creation for software management .. deklesoe 20130514
				//$or
				);
				$ors[] = implode('', $tmp);
			}
		} elseif (!is_int($this->field)) {
			foreach ($this->or as $field => $or) {
				$tmp = $this->db->quoteWhere(
					array(trim($this->field) => $or)
				//$or
				);
				$ors[] = implode('', $tmp);
			}
		} else {
			foreach ($this->or as $field => $p) {
				if ($p instanceof SQLWherePart) {
					$p->injectField($field);
				}
			}
			$ors = $this->db->quoteWhere($this->or);
		}
		return first($ors);
	}

	public function debug()
	{
		return array($this->field => $this->or);
	}

	public function getParameter()
	{
		$params = array();
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
		return $params;
	}

	private function is_main($key)
	{
		return $key[0] != '.';
	}

}
