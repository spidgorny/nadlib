<?php

define('SLTABLE_IMG_CHECK', '<img src="img/check.png">');
define('SLTABLE_IMG_CROSS', '<img src="img/uncheck.png">');

class slTable {
	var $ID = NULL;
	var $data = NULL;
	var $dataClass = NULL;
	var $iRow = -1;
	var $iCol = 0;
	var $thes = array();
	var $more = 'class="nospacing"';
	var $generation = '';
	var $sortable = FALSE;
	var $sortLinkPrefix = '';
	var $dataPlus = ''; // the first row after the header - used for filters
	var $prefix = 'slTable';
	var $sortBy, $sortOrder;
	var $footer;		// last line
	var $isAlternatingColumns = FALSE;
	var $isOddEven = TRUE;
	var $thesMore;
	var $theadPlus = '';
	public $trmore;

	/**
	 * @var BijouDBConnector
	 */
	protected $db;

	function slTable($id = NULL, $more="", array $thes = array()) {
		if (is_array($id)) {
			$this->data = $id;
			$this->ID = md5(time());
		} else if ($id) {
			$this->ID = $id;
		} else {
			$this->ID = md5(time());
		}
		$this->more = $more ? $more : $this->more;
		$this->thes($thes);
		try {
			//$this->db = new BijouDBConnector();
		} catch (Exception $e) {}
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
					if ($this->sortOrder) {
						$th .= '<img src="img/arrow_down.gif" align="absmiddle">';
					} else {
						$th .= '<img src="img/arrow_up.gif" align="absmiddle">';
					}
				}
			}
			//debug($this->thes[$this->sortBy]);
		}
	}

	function generateThes() {
		$thes = array();
		foreach ($this->data as $current) {
			$thes = array_merge($thes, array_keys($current));
		}
		$thes = array_unique($thes);
		$thes = array_combine($thes, $thes);
		foreach ($thes as &$th) {
			$th = array('name' => $th);
		} unset($th);
		unset($thes['###TD_CLASS###']);
		$this->thes($thes);
		$this->isOddEven = TRUE;
		//$this->thesMore = 'style="background-color: #5cacee; color: white;"';
		if (!$this->more) {
			$this->more = 'class="nospacing"';
		}
	}

	function generateThead(HTMLTableBuf $t) {
		//th
		$thes = $this->thes; //array_filter($this->thes, array($this, "noid"));
		$thes2 = array();
		$thmore = array();
		if (is_array($thes)) foreach ($thes as $thk => $thv) {
			if (is_array($thv)) {
				$thvName = $thv['name'] ? $thv['name'] : $thv['label'];
				$thmore[$thk] = isset($thv['thmore']) ? $thv['thmore'] : (isset($thv['more']) ? $thv['more'] : NULL);
			} else {
				$thvName = $thv;
			}
			if ($this->sortable) {
				no_debug(array(
					$_REQUEST[$this->prefix]['sortBy'],
					$this->sortBy,
					$thk,
				));
				if ($this->sortBy == $thk) {
					$newSO = !$this->sortOrder;
				} else {
					$newSO = $this->sortOrder;
				}
				$link = ($this->sortLinkPrefix ? $this->sortLinkPrefix . '&' : $_SERVER['PHP_SELF'].'?').$this->prefix.'[sortBy]='.$thk.'&'.$this->prefix.'[sortOrder]='.$newSO;
				$thes2[$thk] = '<a href="'.$link.'">'.$thvName.'</a>';
			} else {
				if (is_array($thv) && $thv['clickSort']) {
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
				$t->stdout .= '<col class="'.($i%2?'even':'odd').'"></col>';
			}
		}

		if (TRUE) {
			$t->stdout .= '<colgroup>';
			foreach ($thes2 as $key => $dummy) {
				$t->stdout .= '<col class="col_'.$key.'"></col>';
			}
			$t->stdout .= '</colgroup>';
		}

		if ($this->dataPlus) {
			$this->data = array_merge(array($this->dataPlus), $this->data);
		}

		$t->stdout .= $this->theadPlus;
		$t->stdout .= '<tbody>';
	}

	function generate($width = array()) {
		global $db;

		if (!$this->generation) {
			if (!sizeof($this->thes) && sizeof($this->data) && $this->data != FALSE) {
				$this->generateThes();
			}

			if ($this->sortable) {
				$this->sort();
			}

			$t = new HTMLTableBuf();
			$t->table('id="'.$this->ID.'"'.(is_string($this->more) ? $this->more : $this->more['tableMore']));

			$this->generateThead($t);

			// td
			if (!is_array($this->data)) {
				$data = array();
			} else {
				$data = $this->data;
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
				$iCol = 0;
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
		}
	}

	function genRow(HTMLTableBuf $t, $row) {
		$skipCols = 0;
		$iCol = 0;
		foreach ($this->thes as $col => $k) {
			$k = is_array($k) ? $k : array('name' => $k);
			if ($skipCols) {
				$skipCols--;
			} else if (isset($k['!show']) && $k['!show']) {
			} else {
				$val = $row[$col];
				if ($val instanceof HTMLTag && in_array($val->tag, array('td', 'th'))) {
					$t->tag($val);
				} else if ($val instanceof HTMLnoTag) {
					// nothing
				} else {
					if (!$val) {
						$val = $row[strtolower($col)];
					}
					$out = $k['before'] . $this->getCell($col, $val, $k, $row) . $k['after'];
					$more = ($this->isAlternatingColumns ? 'class="'.($iCol%2?'even':'odd').'"' : '');
					if (is_array($row[$col.'.'])) {
						//$more .= $row[$col.'.']['colspan'] ? ' colspan="'.$row[$col.'.']['colspan'].'"' : '';
						$skipCols = $row[$col.'.']['colspan'] ? $row[$col.'.']['colspan'] - 1 : 0;
					}
					$more .= (isset($k['more']) ? $k['more'] : NULL).
						($row[$col.'.']['colspan'] ? 'colspan="'.$row[$col.'.']['colspan'].'"' : '');
					$t->cell($out, isset($width[$iCol]) ? $width[$iCol] : NULL, $more);
					$iCol++;
				}
			}
		}
	}

	function getCell($col, $val, $k, array $row) {
		switch (isset($k['type']) ? $k['type'] : NULL) {
			case "select":
			case "selection":
				//t3lib_div::debug($val);
				//t3lib_div::debug($k);
				if ($val) {
					if (!$k['options']) {
						$what = $k['title'] ? $k['title'] : $col;
						$options = $this->db->fetchSelectQuery($k['from'], array("uid" => $val));
						// TODO: idealize
					} else {
						$options = $k['options'];
					}
					//debug($options); exit();
					$out = $options[$val];;
				} else {
					$out = "";
				}
			break;
			case "date":
				if ($val) {
					$out = date($k['format'] ? $k['format'] : 'Y-m-d H:i:s', $val);
				} else {
					$out = '';
				}
			break;
			case "sqldate":
				if ($val) {
					$val = strtotime(substr($val, 0, 15)); // cut milliseconds
					$out = date($k['format'], $val);
				} else {
					$out = '';
				}
			break;
			case "file":
				$out = str::ahref($val, $GLOBALS['uploadURL'].$val, FALSE);
			break;
			case "money":
				$out = $val . "&nbsp;&euro;";
			break;
			case "delete":
				$out = str::ahref("Del", "?perform[do]=delete&perform[table]={$this->ID}&perform[id]=".$row['id'], FALSE);
			break;
			case "datatable":
				//$f = new HTMLForm();
				//$f->prefix($this->prefixId);
				//$out = $f->datatable($col, $val, $k);
				//$out .="col ".$col." val ".$val." k ".$k;
			break;
			case "checkbox":
				if ($val) {
					$img = SLTABLE_IMG_CHECK;
				} else {
					$img = SLTABLE_IMG_CROSS;
				}
				if ($row[$col.'.link']) {
					$out = str::ahref($img, $row[$col.'.link'], FALSE);
				} else {
					$out = $img;
				}
			break;
			case "bool":
				if (intval($val)) {
					$out = $k['true'];
				} else {
					$out = $k['false'];
				}
				//$out .= t3lib_div::view_array(array('val' => $val, 'k' => $k, 'out' => $out));
			break;
			case "excel":
				$out = str_replace(',', '.', $val); // from excel?
				$out = number_format($out, 2, ',', '.');
			break;
			case "percent":
				$out = number_format($val * 100, 2, ',', '') . '%';// . ' ('.$val.')';
			break;
			case "callback":
				$out = call_user_func($k['callback'], $val, $k, $row);
			break;
			default:
				//t3lib_div::debug($k);
				if (isset($k['hsc']) && $k['hsc']) {
					$val = htmlspecialchars($val);
				}
				if (isset($k['nl2br']) && $k['nl2br']) {
					$val = nl2br($val);
				}
				if (is_object($val)) {
					if (method_exists($val, 'getName')) {
						$val = $val->getName();
					}
				}
				$out = /*stripslashes*/($val);
			break;
		}
		return $out;
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

	function getContent() {
		if (!$this->generation) {
			$this->generate();
		}
		return $this->generation->getContent();
	}

	function getData($table) {
		$cols = $GLOBALS['db']->getTableColumns($table);
		$data = $GLOBALS['db']->getTableDataEx($table, "deleted = 0");
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
		reset($this->data);
		$first = current($this->data);
		if ($first) {
			foreach ($this->thes as $col => $_) {
				$first[$col] = strip_tags($first[$col]);
				if (is_numeric($first[$col])) {
					$footer[$col] = $this->getColumnTotal($this->data, $col);
				} else if ($this->is_time($first[$col])) {
					$footer[$col] = $this->getColumnTotalTime($this->data, $col);
				} else if (floatval($first[$col])) {
					$footer[$col] = 0;
					foreach ($this->data as $row) {
						$footer[$col] += floatval($row[$col]);
					}
				} else {
					$footer[$col] = '&nbsp;';
				}
			}
		}
		return $footer;
	}

	protected function getColumnTotal($data, $col) {
		$total = 0;
		foreach ($data as $row) {
			$total += strip_tags($row[$col]);
		}
		return '<div align="right">'.$total.'</div>';
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

	public static function showAssoc(array $assoc) {
		foreach ($assoc as $key => &$val) {
			$val = array(
				0 => $key,
				'' => $val,
			);
		}
		$s = new self($assoc);
		$s->thes = array(0 => '', '' => '');
		return $s;
	}

}
