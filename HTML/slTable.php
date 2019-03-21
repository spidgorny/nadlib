<?php

use spidgorny\nadlib\HTTP\URL;

/**
 * Class slTable - renders the whole table array into HTML.
 * Has powerful configuration options.
 * @see slTableValue for a single table cell renderer.
 */
class slTable
{

	/**
	 * <table id=""> will be generated
	 * @var string
	 */
	var $ID = null;

	/**
	 * 2D array of rows and columns
	 * @var array
	 */
	var $data = [];

	/**
	 * Class for each ROW(!)
	 * @var array
	 */
	var $dataClass = [];

	var $iRow = -1;

	var $iCol = 0;

	/**
	 * Columns definition. Will be generated if missing.
	 * @var array
	 */
	var $thes = [];

	/**
	 * Appended to <table> tag
	 * @var string
	 */
	var $more = [
		'class' => "nospacing",
	];

	/**
	 * @var HTMLTableBuf
	 */
	var $generation;

	var $sortable = false;

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
	var $footer = [];

	/**
	 * Vertical stripes
	 * @var bool
	 */
	var $isAlternatingColumns = false;

	/**
	 * Horizontal stripes
	 * @var bool
	 */
	var $isOddEven = true;

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

	public $arrowDesc = '<img src="img/arrow_down.gif" align="absmiddle" />';

	public $arrowAsc = '<img src="img/arrow_up.gif" align="absmiddle" />';

	/**
	 * @var Request
	 */
	protected $request;

	public $isCLI = false;

	function __construct($id = null, $more = "", array $thes = [], Request $request = null)
	{
		if (is_array($id) || is_object($id)) {    // Iterator object
			$this->data = $id;
			$this->ID = md5(microtime());
		} elseif ($id) {
			$this->ID = $id;
		} else {
			$this->ID = md5(microtime());
		}
		$this->more = $more ? HTMLTag::parseAttributes($more)
			: $this->more;
		if (isset($this->more['id'])) {
			$this->ID = $this->more['id'];
		}
		$this->thes($thes);
		if (!@file_exists('img/arrow_down.gif')) {
			$this->arrowDesc = '&#x25bc;';
			$this->arrowAsc = '&#x25b2;';
		}
		$this->sortLinkPrefix = new URL();
		$this->generation = new HTMLTableBuf();
		$this->setRequest($request ?: Request::getInstance());
		$this->detectSortBy();
		$this->isCLI = Request::isCLI();
	}

	function setRequest(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * @param array  $aThes
	 * @param string $thesMore
	 */
	function thes(array $aThes, $thesMore = null)
	{
		$this->thes = $aThes;
		if ($thesMore !== null) {
			$this->thesMore = $thesMore;
		}
	}

	/**
	 * @deprecated - use addRowData
	 */
	function addRow()
	{
		$this->iRow++;
		$this->iCol = 0;
	}

	function addRowData($row)
	{
		$this->data[] = $row;
		$this->iRow++;
		$this->iCol = 0;
	}

	function add($val)
	{
		$this->data[$this->iRow][$this->iCol] = $val;
		$this->iCol++;
	}

	function addVal($col, $val)
	{
		$this->data[$this->iRow][$col] = $val;
		$this->iCol++;
	}

	/**
	 * To sort, $this->thes with all datatypes should be known
	 * @public to be callable
	 * @param $a
	 * @param $b
	 * @return int
	 */
	public function tabSortByUrl($a, $b)
	{
		$by = $this->sortBy;
		$so = $this->sortOrder;

		if (!isset($a[$by])) {
//			debug($this->sortable, $this->sortBy, $this->sortOrder,
//				array_keys($this->thes), $a);
			// throw new SortingException('field $by is not set');
		}

		$aa = $a[$by];
		$bb = $b[$by];

		// get $aa && $bb
		$type = isset($this->thes[$by]) ? ifsetor($this->thes[$by]) : null;
		if (is_array($type)) {
			$type = ifsetor($type['type']);
		}
		if ($type == 'date') {
			$aa = strtotime2($aa);
			$bb = strtotime2($bb);
		} elseif ($type == 'int') {
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

	public function detectSortBy()
	{
		$aRequest = $this->request->getArray('slTable');
		$this->sortBy = ifsetor($aRequest['sortBy']);
		$this->sortOrder = ifsetor($aRequest['sortOrder']);
		//debug(array($by, $or));

		// make default softBy if not provided
		if (!$this->sortBy && $this->sortable) {
			$this->generateThes();
			$old = error_reporting(0);    // undefined offset 0
			if (sizeof($this->thes)) {
				$firstElementFromThes = current(array_values( $this->thes ));
				if (is_array($firstElementFromThes)) {
					$firstElementFromThes = current(array_values($firstElementFromThes));
				}
				$this->sortBy = $firstElementFromThes;
			}
			error_reporting($old);
		}
	}

	/**
	 * Call this manually to allow sorting. Otherwise it's assumed that you sort manually (SQL) in advance.
	 * Useful only when the complete result set is visible on a single page.
	 * Otherwise you're sorting just a portion of the data.
	 *
	 * @param string  $by - can be array (for easy explode(' ', 'field DESC') processing
	 * @param boolean $or
	 */
	function setSortBy($by = null, $or = null)
	{
		if (is_array($by)) {
			list($by, $or) = $by;
		}

		// sortBy for th linking and sorting below
		$this->sortBy = $by;
		$this->sortOrder = $or;
//		$this->sort();
	}

	function sort()
	{
		//$this->setSortBy();	// don't use - use SQL
		//debug('$this->sortBy', $this->sortBy);
		if ($this->sortable && $this->sortBy) {
			//print view_table($this->data);
			if ($this->data instanceof ArrayPlus) {
				$this->data->uasort([$this, 'tabSortByUrl']);
			} else {
				uasort($this->data, [$this, 'tabSortByUrl']); // $this->sortBy is used inside
			}
			//print view_table($this->data);
			//debug($this->thes[$this->sortBy]);
			if (isset($this->thes[$this->sortBy])) {
				if (is_array($this->thes[$this->sortBy])) {
					$th = &$this->thes[$this->sortBy]['name'];
				} else {
					$th = &$this->thes[$this->sortBy];
				}
				if ($th
					&& !str_endsWith($th, $this->arrowAsc)
					&& !str_endsWith($th, $this->arrowDesc)
				) {
					$th .= $this->sortOrder ? $this->arrowDesc : $this->arrowAsc;
				}
			}
		}
		//debug_pre_print_backtrace();
		//debug($this->thes[$this->sortBy]);
	}

	function generateThes()
	{
		if (!sizeof($this->thes)) {
			$thes = [];
			foreach ($this->data as $current) {
				$thes = array_merge($thes, array_keys($current));
				$thes = array_unique($thes);    // if put outside the loop may lead to out of memory error
			}
			if ($thes) {
				$thes = array_combine($thes, $thes);
				foreach ($thes as $i => &$th) {
					if (!strlen($i)
						|| (strlen($i) && $i{strlen($i) - 1} != '.')
					) {
						$th = ['name' => $th];
					} else {
						unset($thes[$i]);
					}
				}
				unset($th);
				unset($thes['###TD_CLASS###']);
				unset($thes['###TR_MORE###']);
				$this->thes($thes);
				$this->isOddEven = true;
				//$this->thesMore = 'style="background-color: #5cacee; color: white;"';
				if (!$this->more) {
					$this->more = [
						'class' => "nospacing",
					];
				}
			}
		}
		return $this->thes;
	}

	function getThesNames()
	{
		$names = [];
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

	function generateThead()
	{
		$thes = $this->thes; //array_filter($this->thes, array($this, "noid"));
		foreach ($thes as $key => $k) {
			if (is_array($k) && isset($k['!show']) && $k['!show']) {
				unset($thes[$key]);
			}
		}

		$thes2 = [];
		$thMore = [];
		if (is_array($thes)) foreach ($thes as $thk => $thv) {
			if (!is_array($thv)) {
				$thv = ['name' => $thv];
			}
			$thvName = isset($thv['name'])
				? $thv['name']
				: (isset($thv['label']) ? $thv['label'] : null);
			$thMore[$thk] = isset($thv['thmore'])
				? $thv['thmore']
				: (isset($thv['more']) ? $thv['more'] : null);
			$this->thesMore[$thk] = ifsetor($thv['thmore']);
			if (!is_array($thMore)) {
				$thMore = ['' => $thMore];
			}
			if (isset($thv['align']) && $thv['align']) {
				$thMore[$thk]['style'] = ifsetor($thMore[$thk]['style'])
					. '; text-align: ' . $thv['align'];
			}
			if ($this->sortable) {
				if (
					((isset($thv['dbField'])
							&& $thv['dbField']
						) || !isset($thv['dbField']))
					&& ifsetor($thv['sortable']) !== false
				) {
					$sortField = ifsetor($thv['dbField'], $thk);    // set to null - don't sort
					$sortOrder = $this->sortBy == $sortField
						? !$this->sortOrder
						: $this->sortOrder;
					$link = $this->sortLinkPrefix->forceParams([
						$this->prefix => [
							'sortBy'    => $sortField,
							'sortOrder' => $sortOrder,
						],
					]);
					$thes2[$thk] = '<a href="' . $link . '">' . $thvName . '</a>';
				} else {
					$thes2[$thk] = $thvName;
				}
			} else {
				if (is_array($thv) && isset($thv['clickSort']) && $thv['clickSort']) {
					$link = URL::getCurrent();
					$link->setParam($thv['clickSort'], $thk);
					$thvName = '<a href="' . $link . '">' . $thvName . '</a>';
				}
				$thes2[$thk] = $thvName;
			}
		}

		$this->generation->content['thead'] = [];
		$this->generation->content['thead']['colgroup'] = $this->getColGroup($thes);
		$this->generation->addTHead('<thead>');
		//debug($thes, $this->sortable, $thes2, implode('', $thes2));
		if (implode('', $thes2)) { // don't display empty
//			debug($thMore, $this->thesMore);
			$this->generation->thes($thes2, $thMore, $this->thesMore);
			// $t is not $this // sorting must be done before
		}

		if ($this->dataPlus) {
			$this->data = array_merge([$this->dataPlus], $this->data);
		}

		$this->generation->addTHead('</thead>');
	}

	function getColGroup(array $thes)
	{
		$colgroup = '<colgroup>';
		$i = 0;
		foreach ($thes as $key => $dummy) {
			$key = strip_tags($key);    // <col class="col_E-manual<img src="design/manual.gif">" />
			$key = URL::getSlug($key);    // special cars and spaces
			if ($this->isAlternatingColumns) {
				$key .= ' ' . (++$i % 2 ? 'even' : 'odd');
			}
			if (is_array($dummy)) {
				$colClass = ifsetor($dummy['colClass']);
			} elseif ($dummy instanceof ArrayAccess) {    // HTMLTag('td')
				$colClass = $dummy->offsetGet('colClass');
			} else {
				$colClass = '';
			}
			$key = trim($key . ' ' . $colClass);
			$colgroup .= '<col class="col_' . $key . '" />' . "\n";
		}
		$colgroup .= '</colgroup>';
		return $colgroup;
	}

	function generate($caller = '')
	{
		TaylorProfiler::start(__METHOD__ . " ({$caller})");
		// footer needs to be displayed
		if ((sizeof($this->data) && $this->data != false) || $this->footer) {
			$this->generateThes();

			$this->sort();

			$t = $this->generation;
			$t->table(HTMLTag::renderAttr([
					'id' => $this->ID,
				] + HTMLTag::parseAttributes($this->more)));

			$this->generateThead();
			$this->generation->text('<tbody>');

			if (is_array($this->data) || $this->data instanceof Traversable) {
				$data = $this->data;
			} else {
				$data = [];
			}
			$i = -1;
			foreach ($data as $key => $row) { // (almost $this->data)
				if (!is_array($row)) {
					debug($key, $row);
					throw new Exception('slTable row is not an array');
				}
				++$i;
				$class = [];
				if (is_array($row) && isset($row['###TD_CLASS###'])) {
					$class[] = $row['###TD_CLASS###'];
				} else {
					// only when not manually defined
					if ($this->isOddEven) {
						$class[] = $i % 2 ? 'odd' : 'even';
					}
				}
				if (isset($this->dataClass[$key]) && $this->dataClass[$key]) {
					$class[] = $this->dataClass[$key];
				}
				$tr = 'class="' . implode(' ', $class) . '"';
				if (is_array($row) && isset($row['###TR_MORE###'])) {
					$tr .= ' ' . $row['###TR_MORE###']; // used in class.Loan.php	// don't use for "class"
				}
				$rowID = (is_array($row) && isset($row['id']))
					? $row['id']
					: '';
				$t->tr($tr . ' ' . str_replace('###ROW_ID###', $rowID, $this->trmore));
				//debug_pre_print_backtrace();
				$this->genRow($t, $row);
				$t->tre();
			}
			$this->generation->text('</tbody>');
			$this->genFooter();
			$t->tablee();
			$this->generation = $t;
		} else {
			$this->generation->text('<div class="message">' .
				__('No Data') . '</div>');
		}
		TaylorProfiler::stop(__METHOD__ . " ({$caller})");
	}

	function genFooter()
	{
		if ($this->footer) {
			$this->generation->tfoot('<tfoot>');
			$class = [];
			$class[] = 'footer';
			$tr = 'class="' . implode(' ', $class) . '"';
			$this->generation->ftr($tr);
			$this->generation->curPart = 'tfoot';
			$this->genRow($this->generation, $this->footer);
			$this->generation->curPart = 'tbody';
			$this->generation->ftre();
			$this->generation->tfoot('</tfoot>');
		}
	}

	function genRow(HTMLTableBuf $t, array $row)
	{
		$skipCols = 0;
		$iCol = 0;
		foreach ($this->thes as $col => $k) {
			$k = is_array($k) ? $k : ['name' => $k];

			// whole column desc is combined with single cell desc
			if (isset($row[$col . '.']) && is_array($row[$col . '.'])) {
				$k += $row[$col . '.'];
			}
			if (isset($row[$col]) && $row[$col] instanceof slTableValue) {
				$k += $row[$col]->desc;
			}

			if ($skipCols) {
				$skipCols--;
			} elseif (isset($k['!show']) && $k['!show']) {
			} else {
				$val = isset($row[$col]) ? $row[$col] : null;
				if ($val instanceof HTMLTag && in_array($val->tag, ['td', 'th'])) {
					$t->tag($val);
					if (ifsetor($val->attr['colspan'])) {
						$skipCols = $val->attr['colspan'] - 1;
					}
				} elseif ($val instanceof HTMLnoTag) {
					// nothing
				} else {
					if (!$val) {
						$val = isset($row[strtolower($col)]) ? $row[strtolower($col)] : null;
					}

					if (!($val instanceof slTableValue)) {
						$val = new slTableValue($val, $k);
					}

					$out = (isset($k['before']) ? $k['before'] : '');
					$out .= MergedContent::mergeStringArrayRecursive($val->render($col, $row));
					$out .= (isset($k['after']) ? $k['after'] : '');

					if (isset($k['colspan']) && $k['colspan']) {
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
	 * @param       $iCol
	 * @param       $col
	 * @param array $row
	 * @return array
	 */
	function getCellMore(array $k, $iCol, $col, array $row)
	{
		$more = [];
		if ($this->isAlternatingColumns) {
			$more['class'][] = ($iCol % 2 ? 'even' : 'odd');
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
		if (isset($k['width'])) {
			$more['width'] = $k['width'];
		}
		if (ifsetor($k['title'])) {
			$more['title'] = $k['title'] == '###SELF###'
				? $row[$col]
				: $k['title'];
		}
		return $more;
	}

	/**
	 * @throws Exception
	 */
	function render()
	{
		echo $this->getContent();
	}

	/**
	 * @param string $caller
	 *
	 * @return string
	 * @throws Exception
	 */
	function getContent($caller = '')
	{
		if (!$this->generation->isDone()) {
			$this->generate($caller);
		}
		if ($this->isCLI) {
			$content = $this->getCLITable();
		} else {
			$content = $this->generation->getContent();
		}
		return $content;
	}

	/*
	 * @throws Exception
	 */
	function addRowWithMore($row)
	{
		$this->addRow();
		foreach ($row as $col => $val) {
			$this->addVal($col, $val);
		}
	}

	function __toString()
	{
		return $this->getContent();
	}

	/**
	 * Used by GenReport to auto-generate footer
	 *
	 * @return array
	 */
	public function getTotals()
	{
		$footer = [];
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
				$footer[$col] = $footer[$col] ? new slTableValue($footer[$col], [
					'align' => 'right',
				]) : '';
			}
		}
		return $footer;
	}

	protected function getColumnTotalTime($data, $col)
	{
		$total = 0;
		foreach ($data as $row) {
			$total += TT::getMinutesFromString($row[$col]); // converting to minutes
		}
		$total = TT::getTimeFromDB($total); // converting to string
		return $total;
	}

	protected function is_time($val)
	{
		$parts = explode(':', $val);
		return (sizeof($parts) == 2
			&& is_numeric($parts[0])
			&& is_numeric($parts[1])
			&& strlen($parts[0]) == 2
			&& strlen($parts[1]) == 2);
	}

	public static function showAssoc(array $assoc, $isRecursive = false, $showNumericKeys = true, $no_hsc = false)
	{
		foreach ($assoc as $key => &$val) {
			if ($isRecursive && (is_array($val) || is_object($val))) {
				if (is_object($val)) {
					$val = get_object_vars($val);
				}
				$val = slTable::showAssoc($val, $isRecursive, $showNumericKeys, $no_hsc);
				$val = new htmlString($val);    // to prevent hsc later
			}
			if (!$showNumericKeys && is_numeric($key)) {
				$key = '';
			}

			if ($val instanceof htmlString || $val instanceof HTMLTag) {
				//debug($val);
				//$val = $val;
			} elseif (is_array($val)) {
				//debug($key, $val);
				//throw new InvalidArgumentException('slTable array instead of scalar');
				//return '['.implode(', ', $val).']';
			} else {
				if (!$no_hsc) {
					if (is_object($val)) {
						$val = '[' . get_class($val) . ']';
					} elseif (mb_strpos($val, "\n") !== false) {
						$val = htmlspecialchars($val);
						$val = new htmlString('<pre style="white-space: pre-wrap;">' . htmlspecialchars($val) . '</pre>');
					} else {
						$val = htmlspecialchars($val, ENT_NOQUOTES);
					}
					$no_hsc = true;
				} else {
					// will be done by slTable
					//$val = htmlspecialchars($val);
				}
			}

			$val = [
				//0 => $key instanceof htmlString ? $key : htmlspecialchars($key),
				0  => htmlspecialchars($key),
				'' => $val,
			];
		}
		$s = new self($assoc, 'class="visual nospacing table table-striped"', [
			0  => '',
			'' => ['no_hsc' => $no_hsc],
		]);
		return $s;
	}

	function download($filename)
	{
		$content = $this->getContent();
		header('Content-type: application/vnd.ms-excel');
		header('Content-disposition: attachment; filename="' . $filename . '.xls"');
		header('Content-length: ' . strlen($content));
		echo $content;
		exit();
	}

	/**
	 * TODO: use getThesNames()
	 */
	function prepare4XLS()
	{
		$this->generateThes();
		//debug($this->thes);

		$xls = [];
		$xls[] = $this->getThesNames();

		foreach ($this->data as $row) {
			$line = [];
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
	 * and then pad it accordingly
	 * @param bool $cutTooLong
	 * @param bool $useAvg
	 * @return string
	 */
	function getCLITable($cutTooLong = false, $useAvg = false)
	{
		$this->generateThes();
		$ct = new CLITable($this->data, $this->thes);
		$ct->footer = $this->footer;
		return $ct->render($cutTooLong, $useAvg);
	}

	function autoFormat()
	{
		$this->generateThes();
		foreach ($this->thes as $key => $name) {
			$this->thes[$key] = ['name' => $name];
			$col = array2::array_column($this->data, $key);
			$numeric = true;
			foreach ($col as $val) {
				if (!is_numeric($val)) {
					$numeric = false;
					break;
				}
			}
			//debug($col, $numeric);
			if ($numeric) {
				$this->thes[$key]['more']['align'] = "right";
			}
		}
	}

	function hideEmptyColumns()
	{
		$visible = [];
		foreach ($this->data as $row) {
			foreach ($this->thes as $th => $desc) {
				if ($row[$th]) {
					$visible[$th] = true;
				}
			}
		}
		foreach ($this->thes as $th => $desc) {
			if (!ifsetor($visible[$th])) {
				unset($this->thes[$th]);
			}
		}
	}

}
