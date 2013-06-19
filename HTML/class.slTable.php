<?php

class slTable {
	var $ID = NULL;
	var $data = NULL;
	var $dataClass = NULL;
	var $iRow = -1;
	var $iCol = 0;
	var $thes = array();
	var $more = 'class="nospacing"';

	/**
	 * @var HTMLTableBuf
	 */
	var $generation;
	var $sortable = FALSE;

	/**
	 * @var URL
	 */
	var $sortLinkPrefix;
	var $dataPlus = ''; // the first row after the header - used for filters
	var $prefix = 'slTable';
	var $sortBy, $sortOrder;
	var $footer;		// last line
	var $isAlternatingColumns = FALSE;
	var $isOddEven = TRUE;
	var $thesMore;
	var $theadPlus = '';
	public $trmore;
	public $arrowDesc = '<img src="img/arrow_down.gif" align="absmiddle">';
	public $arrowAsc = '<img src="img/arrow_up.gif" align="absmiddle">';

	/**
	 * @var BijouDBConnector
	 */
	protected $db;

	//public $SLTABLE_IMG_CHECK = '<img src="img/check.png">';
	public $SLTABLE_IMG_CHECK = '☑';
	//public $SLTABLE_IMG_CROSS = '<img src="img/uncheck.png">';
	public $SLTABLE_IMG_CROSS = '☐';

	function __construct($id = NULL, $more="", array $thes = array()) {
		if (is_array($id) || is_object($id)) {	// Iterator object
			$this->data = $id;
			$this->ID = md5(time());
		} else if ($id) {
			$this->ID = $id;
		} else {
			$this->ID = md5(time());
		}
		$this->more = $more ? $more : $this->more;
		$this->thes($thes);
		$this->db = class_exists('Config') ? Config::getInstance()->db : NULL;
		if (!file_exists('img/arrow_down.gif')) {
			$this->arrowDesc = '&#x25bc;';
			$this->arrowAsc = '&#x25b2;';
		}
		$this->sortLinkPrefix = new URL();
	}

	function thes($aThes, $thesMore = NULL) {
		$this->thes = $aThes;
		if ($thesMore !== NULL) {
			$this->thesMore = $thesMore;
		}
	}

	function addRow() {
		$this->iRow++;
		$this->iCol = 0;
	}

	function addRowData($row) {
		$this->data[] = $row;
		$this->iRow++;
		$this->iCol = 0;
	}

	function add($val) {
		$this->data[$this->iRow][$this->iCol] = $val;
		$this->iCol++;
	}

	function addVal($col, $val) {
		$this->data[$this->iRow][$col] = $val;
		$this->iCol++;
	}

	/**
	 * To sort, $this->thes with all datatypes should be known
	 */
	function tabSortByUrl($a, $b) {
		$by = $this->sortBy;
		$so = $this->sortOrder;
		$aa = $a[$by];
		$bb = $b[$by];

		// get $aa && $bb
		if ($this->thes[$by]['type'] == 'date') {
			$aa = strtotime2($aa);
			$bb = strtotime2($bb);
		} else if ($this->thes[$by]['type'] == 'int') {
			$aa = intval(strip_tags($aa));
			$bb = intval(strip_tags($bb));
		} else {
			// otherwise it's a string
			$aa = strip_tags($aa);
			$bb = strip_tags($bb);
			if (!$so) {
				return strcmp($aa, $bb);
			} else {
				return strcmp($bb, $aa);
			}
		}

		//debug($by, $so, $aa, $bb);
		// $aa && $bb are known
		if ($aa == $bb) {
			return 0;
		} else {
			if (!$so) {
				return $aa > $bb ? +1 : -1;
			} else {
				return $aa < $bb ? +1 : -1;
			}
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $by - can be array (for easy explode(' ', 'field DESC') processing
	 * @param unknown_type $or
	 */
	function setSortBy($by = NULL, $or = NULL) {
		if ($by === NULL && $or === NULL) {
			$by = $_REQUEST['slTable']['sortBy'];
			$or = $_REQUEST['slTable']['sortOrder'];
			//debug(array($by, $or));
		} else if (is_array($by)) {
			list($by, $or) = $by;
		}

		// sortBy for th linking and sorting below
		$this->sortBy = $by;
		$this->sortOrder = $or;
		if (!$this->sortBy) {
			reset($this->thes);
			//debug($this->thes);
			list($this->sortBy) = current($this->thes);
			//$this->sortBy = 'l3';
		}
	}

	function sort() {
		//$this->setSortBy();	// don't use - use SQL
		//debug('$this->sortBy', $this->sortBy);
		if ($this->sortable && $this->sortBy) {
			//print view_table($this->data);
			uasort($this->data, array($this, 'tabSortByUrl')); // $this->sortBy is used inside
			//print view_table($this->data);
			//debug($this->thes[$this->sortBy]);
			if (isset($this->thes[$this->sortBy])) {
				if (is_array($this->thes[$this->sortBy])) {
					$th = &$this->thes[$this->sortBy]['name'];
				} else {
					$th = &$this->thes[$this->sortBy];
				}
				if ($th) {
					$th .= $this->sortOrder ? $this->arrowDesc : $this->arrowAsc;
				}
			}
		}
	}

	function generateThes() {
		if (!sizeof($this->thes)) {
			$thes = array();
			foreach ($this->data as $current) {
				$thes = array_merge($thes, array_keys($current));
				$thes = array_unique($thes);	// if put outside the loop may lead to out of memory error
			}
			if ($thes) {
				$thes = array_combine($thes, $thes);
				foreach ($thes as $i => &$th) {
					if ($i{strlen($i)-1} != '.') {
						$th = array('name' => $th);
					} else {
						unset($thes[$i]);
					}
				} unset($th);
				unset($thes['###TD_CLASS###']);
				$this->thes($thes);
				$this->isOddEven = TRUE;
				//$this->thesMore = 'style="background-color: #5cacee; color: white;"';
				if (!$this->more) {
					$this->more = 'class="nospacing"';
				}
			}
		}
	}

	function generateThead(HTMLTableBuf $t) {
		$thes = $this->thes; //array_filter($this->thes, array($this, "noid"));
		foreach ($thes as $key => $k) {
			if (is_array($k) && isset($k['!show']) && $k['!show']) {
				unset($thes[$key]);
			}
		}

		$thes2 = array();
		$thmore = array();
		if (is_array($thes)) foreach ($thes as $thk => $thv) {
			if (is_array($thv)) {
				$thvName = $thv['name'] ? $thv['name'] : $thv['label'];
				$thmore[$thk] = isset($thv['thmore']) ? $thv['thmore'] : (isset($thv['more']) ? $thv['more'] : NULL);
				if ($thv['align']) {
					$thmore[$thk]['align'] = $thv['align'];
				}
			} else {
				$thvName = $thv;
			}
			if ($this->sortable) {
				nodebug(array(
					$_REQUEST[$this->prefix]['sortBy'],
					$this->sortBy,
					$thk,
				));
				$sortField = $thv['dbField'] ? $thv['dbField'] : $thk;
				$sortOrder = $this->sortBy == $sortField ? !$this->sortOrder : $this->sortOrder;
				$link = $this->sortLinkPrefix->forceParams(array($this->prefix => array(
					'sortBy' => $sortField,
					'sortOrder' => $sortOrder,
				)));
				$thes2[$thk] = '<a href="'.$link.'">'.$thvName.'</a>';
			} else {
				if (is_array($thv) && isset($thv['clickSort']) && $thv['clickSort']) {
					$link = URL::getCurrent();
					$link->setParam($thv['clickSort'], $thk);
					$thvName = '<a href="'.$link.'">'.$thvName.'</a>';
				}
				$thes2[$thk] = $thvName;
			}
		}

		//debug($thes, $this->sortable);
		if (implode('', $thes2)) { // don't display empty
			$t->thes($thes2, $thmore, $this->thesMore . (is_array($this->more) ? $this->more['thesMore'] : '')); // $t is not $this // sorting must be done before
		}

		// col
		if ($this->isAlternatingColumns) {
			for ($i = 0; $i < sizeof($this->thes); $i++) {
				$t->stdout .= '<col class="'.($i%2?'even':'odd').'" />';
			}
		}

		if (TRUE) {
			$t->stdout .= '<colgroup>';
			foreach ($thes2 as $key => $dummy) {
				$t->stdout .= '<col class="col_'.$key.'" />';
			}
			$t->stdout .= '</colgroup>';
		}

		if ($this->dataPlus) {
			$this->data = array_merge(array($this->dataPlus), $this->data);
		}

		$t->stdout .= $this->theadPlus;
		$t->stdout .= '<tbody>';
	}

	function generate($caller = '') {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__." ({$caller})");
		if (!$this->generation) {	// cache
			if ((sizeof($this->data) && $this->data != FALSE) || $this->footer) {	// footer needs to be displayed
				$this->generateThes();

				if ($this->sortable) {
					$this->sort();
				}

				$t = new HTMLTableBuf();
				$t->table('id="'.$this->ID.'" '.(is_string($this->more) ? $this->more : $this->more['tableMore']));

				$this->generateThead($t);

				if (is_array($this->data) || $this->data instanceof Traversable) {
					$data = $this->data;
				} else {
					$data = array();
				}
				$i = -1;
				foreach ($data as $key => $row) { // (almost $this->data)
					$class = array();
					if (isset($row['###TD_CLASS###'])) {
						$class[] = $row['###TD_CLASS###'];
					} else {
						// only when not manually defined
						if ($this->isOddEven) {
							$class[] = (++$i%2?'even':'odd');
						}
					}
					if ($this->dataClass[$key]) {
						$class[] = $this->dataClass[$key];
					}
					$tr = 'class="'.implode(' ', $class).'"';
					//debug($tr);
					$t->tr($tr . ' ' . str_replace('###ROW_ID###', isset($row['id']) ? $row['id'] : '', $this->trmore));
					$this->genRow($t, $row);
					$t->tre();
				}
				$t->stdout .= '</tbody>';
				if ($this->footer) {
					$t->stdout .= '<tfoot>';
					$class = array();
					if ($this->isOddEven) {
						$class[] = (++$i%2?'even':'odd');
					}
					$class[] = 'footer';
					$tr = 'class="'.implode(' ', $class).'"';
					$t->tr($tr);
					$this->genRow($t, $this->footer);
					$t->tre();
					$t->stdout .= '</tfoot>';
				}
				$t->tablee();
				$this->generation = $t;
			} else {
				$this->generation = new HTMLTableBuf();
				$this->generation->stdout = '<div class="message">'.__('No Data').'</div>';
			}
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__." ({$caller})");
	}

	function genRow(HTMLTableBuf $t, array $row) {
		$skipCols = 0;
		$iCol = 0;
		foreach ($this->thes as $col => $k) {
			$k = is_array($k) ? $k : array('name' => $k);

			// whole column desc is combined with single cell desc
			if (isset($row[$col.'.']) && is_array($row[$col.'.'])) {
				$k += $row[$col.'.'];
			}
			if ($row[$col] instanceof slTableValue) {
				$k += $row[$col]->desc;
			}

			if ($skipCols) {
				$skipCols--;
			} else if (isset($k['!show']) && $k['!show']) {
			} else {
				$val = isset($row[$col]) ? $row[$col] : NULL;
				if ($val instanceof HTMLTag && in_array($val->tag, array('td', 'th'))) {
					$t->tag($val);
					if ($val->attr['colspan']) {
						$skipCols = $val->attr['colspan'] - 1;
					}
				} else if ($val instanceof HTMLnoTag) {
					// nothing
				} else {
					if (!$val) {
						$val = isset($row[strtolower($col)]) ? $row[strtolower($col)] : NULL;
					}
					$val = new slTableValue($val, $k);

					$out = (isset($k['before']) ? $k['before'] : '')
						. $val->render($col, $row) .
						(isset($k['after']) ? $k['after'] : '');
					if ($k['wrap']) {
						$wrap = $k['wrap'] instanceof Wrap ? $k['wrap'] : new Wrap($k['wrap']);
						$out = $wrap->wrap($out);
					}
					$more = array();
					if ($this->isAlternatingColumns) {
						$more['class'][] = ($iCol%2?'even':'odd');
					}
					if ($k['colspan']) {
						$skipCols = isset($k['colspan']) ? $k['colspan'] - 1 : 0;
					}

					if (isset($k['more'])) {
						if (is_array($k['more'])) {
							$more += $k['more'];
						} else {
							debug(__METHOD__, $col, $k, $row);
							die(' Consider making your "more" an array');
							$more .= $k['more'];
						}
					}

					if (isset($k['colspan'])) {
						$more['colspan'] = $k['colspan'];
					}
					if (isset($k['align'])) {
						$more['align'] = $k['align'];
					}
					if (isset($width[$iCol])) {
						$more['width'] = $width[$iCol];
					}

					$t->cell($out, $more);
					$iCol++;
				}
			}
		}
	}

	function show() {
		if (!$this->generation) {
			$this->generate();
		}
		$this->generation->render();
	}

	function render() {
		$this->show();
	}

	function getContent($caller = '') {
		if (!$this->generation) {
			$this->generate($caller);
		}
		return $this->generation->getContent();
	}

	function getData($table) {
		$db = Config::getInstance()->db;
		$cols = $db->getTableColumns($table);
		$data = $db->getTableDataEx($table, "deleted = 0");
		for ($i = 0; $i < sizeof($data); $i++) {
			$this->addRow();
			$iCol = 0;
			foreach ($data[$i] as $val) {
				$this->addVal($cols[$iCol++], $val);
			}
		}
	}

	function addRowWithMore($row) {
		$this->addRow();
		foreach ($row as $col => $val) {
			$this->addVal($col, $val);
		}
	}

	function __toString() {
		return $this->getContent();
	}

	/**
	 * Used by GenReport to auto-generate footer
	 *
	 * @return array
	 */
	public function getTotals() {
		$footer = array();
		$this->generateThes();
		reset($this->data);
		$first = current($this->data);
		//debug($this->data, $first);
		if ($first) {
			foreach ($this->thes as $col => $_) {
				$first[$col] = strip_tags($first[$col]);
				if ($this->is_time($first[$col])) {
					$footer[$col] = $this->getColumnTotalTime($this->data, $col);
				} else {//if (floatval($first[$col])) {
					$footer[$col] = 0;
					foreach ($this->data as $row) {
						$footer[$col] += floatval(strip_tags($row[$col]));
					}
				//} else {
				//	$footer[$col] = '&nbsp;';
				}
				$footer[$col] = $footer[$col] ? new slTableValue($footer[$col], array(
					'align' => 'right',
				)) : '';
			}
		}
		return $footer;
	}

	protected function getColumnTotalTime($data, $col) {
		$total = 0;
		foreach ($data as $row) {
			$total += TT::getMinutesFromString($row[$col]); // converting to minutes
		}
		$total = TT::getTimeFromDB($total); // converting to string
		return $total;
	}

	protected function is_time($val) {
		$parts = explode(':', $val);
		return (is_numeric($parts[0]) && is_numeric($parts[1]) && strlen($parts[0]) == 2 && strlen($parts[1]) == 2);
	}

	public static function showAssoc(array $assoc, $isRecursive = false, $showNumericKeys = true) {
		foreach ($assoc as $key => &$val) {
			if ($isRecursive && (is_array($val) || is_object($val))) {
				if (is_object($val)) {
					$val = get_object_vars($val);
				}
				$val = slTable::showAssoc($val, $isRecursive, $showNumericKeys).'';
			}
			if (!$showNumericKeys && is_numeric($key)) {
				$key = '';
			}
			$val = array(
				0 => $key,
				'' => $val,
			);
		}
		$s = new self($assoc, '', array(
			0 => '',
			'' => array('no_hsc' => true),
		));
		return $s;
	}

	function download($filename) {
		$content = $this->getContent();
		header('Content-type: application/vnd.ms-excel');
		header('Content-disposition: attachment; filename="'.$filename.'.xls"');
		header('Content-length: '.strlen($content));
		echo $content;
		exit();
	}

}
