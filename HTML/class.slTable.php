<?php

class slTable {

	/**
	 * <table id=""> will be generated
	 * @var string
	 */
	var $ID = NULL;

	/**
	 * 2D array of rows and columns
	 * @var array
	 */
	var $data = array();

	/**
	 * Class for each ROW(!)
	 * @var array
	 */
	var $dataClass = array();

	var $iRow = -1;

	var $iCol = 0;

	/**
	 * Columns definition. Will be generated if missing.
	 * @var array
	 */
	var $thes = array();

	/**
	 * Appended to <table> tag
	 * @var string
	 */
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

	/**
	 * the first row after the header - used for filters
	 * @var string
	 */
	var $dataPlus = '';

	/**
	 * $_REQUEST[$this->prefix]
	 * @var string
	 */
	var $prefix = 'slTable';

	var $sortBy, $sortOrder;

	/**
	 * last line
	 * @var array
	 */
	var $footer = array();

	/**
	 * Vertical stripes
	 * @var bool
	 */
	var $isAlternatingColumns = FALSE;

	/**
	 * Horizontal stripes
	 * @var bool
	 */
	var $isOddEven = TRUE;

	/**
	 * @var string <tr $thesMore>
	 */
	var $thesMore;

	/**
	 * @var string before <tbody>
	 */
	var $thesPlus = '';

	/**
	 * @var string
	 */
	public $trmore;

	/**
	public $arrowDesc = '<img src="img/arrow_down.gif" align="absmiddle" />';

	public $arrowAsc = '<img src="img/arrow_up.gif" align="absmiddle" />';

	/**
	 * @var BijouDBConnector
	 */
	protected $db;

	function __construct($id = NULL, $more="", array $thes = array()) {
		if (is_array($id) || is_object($id)) {	// Iterator object
			$this->data = $id;
			$this->ID = md5(microtime());
		} else if ($id) {
			$this->ID = $id;
		} else {
			$this->ID = md5(microtime());
		}
		$this->more = $more ? $more : $this->more;
		$this->thes($thes);
		$this->db = class_exists('Config') ? Config::getInstance()->db : NULL;
		if (!file_exists('img/arrow_down.gif')) {
			$this->arrowDesc = '&#x25bc;';
			$this->arrowAsc = '&#x25b2;';
		}
		$this->sortLinkPrefix = new URL();
		$this->generation = new HTMLTableBuf();
	}

	/**
	 * @param array $aThes
	 * @param string $thesMore
	 */
	function thes(array $aThes, $thesMore = NULL) {
		$this->thes = $aThes;
		if ($thesMore !== NULL) {
			$this->thesMore = $thesMore;
		}
	}

	/**
	 * @deprecated - use addRowData
	 */
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
	 * @public to be callable
	 */
	public function tabSortByUrl($a, $b) {
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
	 * Call this manually to allow sorting. Otherwise it's assumed that you sort manually (SQL) in advance.
	 * Useful only when the complete resultset is visible on a single page.
	 * Otherwise you're sorting just a portion of the data.
	 *
	 * @param string $by - can be array (for easy explode(' ', 'field DESC') processing
	 * @param boolean $or
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
				if ($th && !endsWith($th, $this->arrowAsc) && !endsWith($th, $this->arrowDesc)) {
					$th .= $this->sortOrder ? $this->arrowDesc : $this->arrowAsc;
				}
			}
		}
		//debug_pre_print_backtrace();
		//debug($this->thes[$this->sortBy]);
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
				unset($thes['###TR_MORE###']);
				$this->thes($thes);
				$this->isOddEven = TRUE;
				//$this->thesMore = 'style="background-color: #5cacee; color: white;"';
				if (!$this->more) {
					$this->more = 'class="nospacing"';
				}
			}
		}
	}

	function getThesNames() {
		$names = array();
		foreach ($this->thes as $field => $thv) {
			if (is_array($thv)) {
				$thvName = isset($thv['name'])
					? $thv['name']
					: (isset($thv['label']) ? $thv['label'] : '');
			} else {
				$thvName = $thv;
			}
			$names[$field] = $thvName;
		}
		return $names;
	}

	function generateThead() {
		$thes = $this->thes; //array_filter($this->thes, array($this, "noid"));
		foreach ($thes as $key => $k) {
			if (is_array($k) && isset($k['!show']) && $k['!show']) {
				unset($thes[$key]);
			}
		}

		$thes2 = array();
		$thMore = array();
		if (is_array($thes)) foreach ($thes as $thk => $thv) {
			if (!is_array($thv)) {
				$thv = array('name' => $thv);
			}
			$thvName = $thv['name'] ? $thv['name'] : $thv['label'];
			$thMore[$thk] = ifsetor($thv['thmore'], ifsetor($thv['more']));
			if (!is_array($thMore)) {
				$thMore = array('' => $thMore);
			}
			if ($thv['align']) {
				$thMore[$thk]['align'] = $thv['align'];
			}
			if ($this->sortable) {
				if ((isset($thv['dbField']) && $thv['dbField']) || !isset($thv['dbField'])) {
					$sortField = $thv['dbField'] ? $thv['dbField'] : $thk;	// set to null - don't sort
					$sortOrder = $this->sortBy == $sortField ? !$this->sortOrder : $this->sortOrder;
					$link = $this->sortLinkPrefix->forceParams(array($this->prefix => array(
						'sortBy' => $sortField,
						'sortOrder' => $sortOrder,
					)));
					$thes2[$thk] = '<a href="'.$link.'">'.$thvName.'</a>';
				} else {
					$thes2[$thk] = $thvName;
				}
			} else {
				if (is_array($thv) && isset($thv['clickSort']) && $thv['clickSort']) {
					$link = URL::getCurrent();
					$link->setParam($thv['clickSort'], $thk);
					$thvName = '<a href="'.$link.'">'.$thvName.'</a>';
				}
				$thes2[$thk] = $thvName;
			}
		}

		$this->generation->thead['colgroup'] = $this->getColGroup($thes2);
		$this->generation->addTHead('<thead>');
		//debug($thes, $this->sortable, $thes2, implode('', $thes2));
		if (implode('', $thes2)) { // don't display empty
			$more = is_array($this->more)
				? HTMLTag::renderAttr($this->more['thesMore'])
				: '';
			$this->generation->thes($thes2, $thMore, $this->thesMore . $more);
			// $t is not $this // sorting must be done before
		}

		if ($this->dataPlus) {
			$this->data = array_merge(array($this->dataPlus), $this->data);
		}

		$this->generation->addTHead($this->colgroup);
		$this->generation->addTHead('</thead>');
	}

	function getColGroup(array $thes2) {
		$colgroup = '<colgroup>';
		$i = 0;
		foreach ($thes2 as $key => $dummy) {
			$key = strip_tags($key);
			// <col class="col_E-manual<img src="design/manual.gif">" />

			if ($this->isAlternatingColumns) {
				$key .= ' '.(++$i%2?'even':'odd');
			}
			$colgroup .= '<col class="col_'.$key.'" />';
		}
		$colgroup .= '</colgroup>';
		return $colgroup;
	}

	function generate($caller = '') {
		TaylorProfiler::start(__METHOD__." ({$caller})");
		// footer needs to be displayed
		if ((sizeof($this->data) && $this->data != FALSE) || $this->footer) {
			$this->generateThes();

			$this->sort();

			$t = $this->generation;
			$t->table('id="'.$this->ID.'" '.(is_string($this->more)
					? $this->more
					: $this->more['tableMore']));

			$this->generateThead();
			$this->generation->text('<tbody>');

			if (is_array($this->data) || $this->data instanceof Traversable) {
				$data = $this->data;
			} else {
				$data = array();
			}
			$i = -1;
			foreach ($data as $key => $row) { // (almost $this->data)
                ++$i;
                $class = array();
				if (isset($row['###TD_CLASS###'])) {
					$class[] = $row['###TD_CLASS###'];
				} else {
					// only when not manually defined
					if ($this->isOddEven) {
						$class[] = ($i%2?' even':' odd');
					}
				}
				if ($this->dataClass[$key]) {
					$class[] = $this->dataClass[$key];
				}
				$tr = 'class="'.implode(' ', $class).'"';
				$tr .= ' '.$row['###TR_MORE###']; // used in class.Loan.php	// don't use for "class"
				$t->tr($tr . ' ' . str_replace('###ROW_ID###', isset($row['id']) ? $row['id'] : '', $this->trmore));
				//debug_pre_print_backtrace();
				$this->genRow($t, $row);
				$t->tre();
			}
			$this->generation->text('</tbody>');
			$this->genFooter();
			$t->tablee();
			$this->generation = $t;
		} else {
			$this->generation->text('<div class="message">'.__('No Data').'</div>');
		}
		TaylorProfiler::stop(__METHOD__." ({$caller})");
	}

	function genFooter() {
		if ($this->footer) {
			$this->generation->tfoot('<tfoot>');
			$class = array();
			$class[] = 'footer';
			$tr = 'class="'.implode(' ', $class).'"';
			$this->generation->ftr($tr);
			$this->genRow($this->generation, $this->footer);
			$this->generation->ftre();
			$this->generation->tfoot('</tfoot>');
		}
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

					if (!($val instanceof slTableValue)) {
						$val = new slTableValue($val, $k);
					}

					$out = (isset($k['before']) ? $k['before'] : '').
						   $val->render($col, $row) .
						   (isset($k['after']) ? $k['after'] : '');

					if ($k['colspan']) {
						$skipCols = isset($k['colspan']) ? $k['colspan'] - 1 : 0;
					}

					$more = $this->getCellMore($k, $iCol, $col, $row);
					$t->cell($out, $more);
					$iCol++;
				}
			}
		}
	}

	/**
	 * @param array $k
	 * @param $iCol
	 * @param $col
	 * @param array $row
	 * @return array
	 */
	function getCellMore(array $k, $iCol, $col, array $row) {
		$more = array();
		if ($this->isAlternatingColumns) {
			$more['class'][] = ($iCol%2?'even':'odd');
		}

		if (isset($k['more'])) {
			if (is_array($k['more'])) {
				$more += $k['more'];
			} else {
				debug(__METHOD__, $col, $k, $row);
				die('Consider making your "more" an array');
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
		return $more;
	}

	function show() {
		if (!$this->generation->isDone()) {
			$this->generate();
		}
		$this->generation->render();
	}

	function render() {
		$this->show();
	}

	function getContent($caller = '') {
		if (!$this->generation->isDone()) {
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
		return Request::isCLI()
			? $this->getCLITable()
			: $this->getContent();
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

	public static function showAssoc(array $assoc, $isRecursive = false, $showNumericKeys = true, $no_hsc = false) {
		foreach ($assoc as $key => &$val) {
			if ($isRecursive && (is_array($val) || is_object($val))) {
				if (is_object($val)) {
					$val = get_object_vars($val);
				}
				$val = slTable::showAssoc($val, $isRecursive, $showNumericKeys, $no_hsc);
				$val = new htmlString($val); 	// to prevent hsc later
			}
			if (!$showNumericKeys && is_numeric($key)) {
				$key = '';
			}

			if ($val instanceof htmlString || $val instanceof HTMLTag) {
				//$val = $val;
			} else {
				if (!$no_hsc) {
					if (mb_strpos($val, "\n") !== FALSE) {
						$val = htmlspecialchars($val);
						$val = new htmlString('<pre>' . $val . '</pre>');
					} else {
						$val = htmlspecialchars($val);
					}
				}
			}

			$val = array(
				0 => htmlspecialchars($key),
				'' => $val,
			);
		}
		$s = new self($assoc, 'class="visual nospacing table"', array(
			0 => '',
			'' => array('no_hsc' => $no_hsc),
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

	/**
	 * TODO: use getThesNames()
	 */
	function prepare4XLS() {
		$this->generateThes();
		//debug($this->thes);

		$xls = array();
		foreach ($this->thes as $th) {
			$row[] = is_array($th) ? $th['name'] : $th;
		}
		$xls[] = $row;

		foreach ($this->data as $row) {
			$line = array();
			foreach ($this->thes as $col => $_) {
				$val = $row[$col];
				$line[] = strip_tags($val);
			}
			$xls[] = $line;
		}
		return $xls;
	}

	/**
	 * Separation by "\t" is too stupid. We count how many chars are there in each column
	 * and then padd it accordingly
	 * @param bool $cutTooLong
	 * @param bool $useAvg
	 * @return string
	 */
	function getCLITable($cutTooLong = false, $useAvg = false) {
		$this->generateThes();
		$widthMax = array();
		$widthAvg = array();
		foreach ($this->data as $row) {
			foreach ($this->thes as $field => $name) {
				$value = $row[$field];
				$value = strip_tags($value);
				$widthMax[$field] = max($widthMax[$field], mb_strlen($value));
				$widthAvg[$field] += mb_strlen($value);
			}
		}
		if ($useAvg) {
			foreach ($this->thes as $field => $name) {
				$widthAvg[$field] /= sizeof($this->data);
				//$avgLen = round(($widthMax[$field] + $widthAvg[$field]) / 2);
				$avgLen = $widthAvg[$field];
				$widthMax[$field] = max(8, 1+$avgLen);
			}
		}

		$dataWithHeader = array_merge(array($this->getThesNames()), $this->data);

		$content = "\n";
		foreach ($dataWithHeader as $row) {
			$padRow = array();
			foreach ($this->thes as $field => $name) {
				$value = $row[$field];
				$value = strip_tags($value);
				if ($cutTooLong) {
					$value = substr($value, 0, $widthMax[$field]);
				}
				$value = str_pad($value, $widthMax[$field], ' ', STR_PAD_RIGHT);
				$padRow[] = $value;
			}
			$content .= implode(" ", $padRow)."\n";
		}
		return $content;
	}

}
