<?php

/**
 * Class SQLOr - the parameters inside SHOULD contain key => value pairs.
 * This may not be used as an alternative to 'makeOR'. Use SQLIn instead.
 */

class SQLOr extends SQLWherePart
{

	protected $or = array();

	/**
	 * @var dbLayerPG
	 */
	protected $db;

	function __construct(array $ors)
	{
		//parent::__construct();
		$this->or = $ors;
		$this->db = Config::getInstance()->db;
		$this->qb = Config::getInstance()->getQb();
	}

	function __toString()
	{
		if ($this->qb->db instanceof dbLayerPG) {        // ???
			$ors = array();
			foreach ($this->or as $key => $or) {
				if (is_main($key)) {
					$ors[] = $this->db->getWherePart(array(
						$key => $or,
						$key . '.' => $this->or[$key . '.'],
					), false);
				}
			}
		} else if ($this->qb->db instanceof dbLayer) {    // DCI, ORS
			// where is it used? in ORS for sure, but make sure you don't call new SQLOr(array('a', 'b', 'c'))
			// http://ors.nintendo.de/NotifyVersion
			if (is_int($this->field)) {                 // added is_int condition to solve problem with software mngmt & request (hw/sw request)  .. deklesoe 20130514
				$ors = array();
				foreach ($this->or as $field => $or) {
					$tmp = $this->db->quoteWhere(
						array($field => $or)
					//array($this->field => $or)    //  commented and replaced with line above due to problem
					//  with query creation for software management .. deklesoe 20130514
					//$or
					);
					$ors[] = implode('', $tmp);
				}
			} elseif (!is_int($this->field)) {
				$ors = array();
				foreach ($this->or as $field => $or) {
					$tmp = $this->qb->quoteWhere(
						array($this->field => $or)
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
		} else {                                        // MySQL
			$ors = $this->db->quoteWhere($this->or);
		}
		if ($ors) {
			$res = '(' . implode(' OR ', $ors) . ')';
		} else {
			$res = '/* EMPTY OR */';
		}
		//debug($this, $ors, $res);
		return $res;
	}

	function debug()
	{
		return array($this->field => $this->or);
	}

}
