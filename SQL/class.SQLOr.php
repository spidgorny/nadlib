<?php

/**
 * Class SQLOr - the parameters inside SHOULD contain key => value pairs.
 * This may not be used as an alternative to 'makeOR'. Use SQLIn instead.
 */

class SQLOr extends SQLWherePart {

	protected $or = array();

	/**
	 * @var dbLayerPG|dbLayer
	 */
	protected $db;

	protected $join = ' OR ';

	function __construct(array $ors) {
		//parent::__construct();
		$this->or = $ors;
		$this->db = Config::getInstance()->getDB();
	}

	/**
	 * Please make SQLOrBijou, SQLOrORS and so on classes.
	 * This one should be just simple general.
	 * @return string
	 */
	function __toString() {
		//debug(get_class($this->db));
		if (false && $this->db instanceof dbLayerPG) {		// bijou
			$ors = array();
			foreach ($this->or as $key => $or) {
				if (is_main($key)) {
					$ors[] = $this->db->getWherePart(array(
						$key => $or,
						$key.'.' => $this->or[$key.'.'],
					), false);
				}
			}
		} elseif (false && $this->db instanceof dbLayer) {	// DCI, ORS
			// where is it used? in ORS for sure, but make sure you don't call new SQLOr(array('a', 'b', 'c'))
			// http://ors.nintendo.de/NotifyVersion
            if (is_int($this->field)) {                 // added is_int condition to solve problem with software mngmt & request (hw/sw request)  .. deklesoe 20130514
				$ors = array();
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
                $ors = array();
                foreach ($this->or as $field => $or) {
                    $tmp = $this->qb->quoteWhere(
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
		} else {						// MySQL
			$ors = $this->db->quoteWhere($this->or);
		}
		if ($ors) {
			$res = '('.implode($this->join, $ors).')';
		} else {
			$res = '/* EMPTY OR */';
		}
		//debug($this, $ors, $res);
		return $res;
	}

	function debug() {
		return array($this->field => $this->or);
	}

	function getParameter() {
		$params = [];
		/**
		 * @var string $field
		 * @var SQLLike $oneOR
		 */
		foreach ($this->or as $field => $oneOR) {
			if ($oneOR instanceof SQLWherePart) {
				$plus = $oneOR->getParameter();
				if ($plus) {
					$params[] = $plus;
				}
			}
		}
		return $params;
	}

}
