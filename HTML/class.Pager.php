<?php

class Pager
{
	var $numberOfRecords = 0;
	var $itemsPerPage = 20;
	var $startingRecord = 0;
	var $currentPage = 0;

	/**
	 * @var URL
	 */
	var $url;
	var $pagesAround = 3;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * Identifies pager preferences on different pages
	 * @var string
	 */
	protected $prefix;

	/**
	 * @var LoginUser
	 */
	protected $user;

	public $showPageJump = true;

	public $showPager = true;

	/**
	 * @var PageSize
	 */
	public $pageSize;

	static $cssOutput = false;

	function __construct($itemsPerPage = NULL, $prefix = '')
	{
		if ($itemsPerPage instanceof PageSize) {
			$this->pageSize = $itemsPerPage;
		} else {
			$this->pageSize = new PageSize($itemsPerPage ?: $this->itemsPerPage);
		}
		$this->setItemsPerPage($this->pageSize->get()); // only allowed amounts
		$this->prefix = $prefix;
		$this->db = Config::getInstance()->db;
		$this->request = Request::getInstance();
		$this->setUser(Config::getInstance()->user);

		Config::getInstance()->mergeConfig($this);
	}

	/**
	 * To be called only after setNumberOfRecords()
	 */
	function detectCurrentPage()
	{
		if (($pagerData = $_REQUEST['Pager_' . $this->prefix])) {
			if ($pagerData['startingRecord']) {
				$this->startingRecord = (int)($pagerData['startingRecord']);
				$this->currentPage = $this->startingRecord / $this->itemsPerPage;
			} else {
				if ($this->request->getMethod() == 'POST') {
					//Debug::debug_args($pagerData);
					$pagerData['page']--;
				}
				$this->setCurrentPage($pagerData['page']);
				$this->saveCurrentPage();
			}
		} elseif ($this->user && method_exists($this->user, 'getPref')) {
			$pager = $this->user->getPref('Pager.' . $this->prefix);
			if ($pager) {
				//debug(__METHOD__, $this->prefix, $pager['page']);
				$this->setCurrentPage($pager['page']);
			}
		} else {
			$this->setCurrentPage(0);
		}
	}

	function initByQuery($query)
	{
		//debug_pre_print_backtrace();
		$key = __METHOD__ . ' (' . substr($query, 0, 300) . ')';
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer($key);
		$query = "SELECT count(*) AS count FROM (" . $query . ") AS counted";
		$res = $this->db->fetchAssoc($this->db->perform($query));
		$this->setNumberOfRecords($res['count']);
		$this->detectCurrentPage();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer($key);
	}

	/**
	 * @param $i
	 */
	function setNumberOfRecords($i)
	{
		$this->numberOfRecords = $i;
		if ($this->startingRecord > $this->numberOfRecords) {    // required
			$this->setCurrentPage($this->currentPage);
			if ($this->request->isPOST()) {
				$_POST['pager']['page'] = $this->currentPage + 1;
			}
		}
	}

	/**
	 * Make sure to setNumberOfRecords first(!)
	 * @param $page
	 */
	function setCurrentPage($page)
	{
		//max(0, ceil($this->numberOfRecords/$this->itemsPerPage)-1);    // 0-indexed
		$page = min($page, $this->getMaxPage());
		$this->currentPage = max(0, $page);
		$this->startingRecord = $this->getPageFirstItem($this->currentPage);
	}

	function saveCurrentPage()
	{
		//debug(__METHOD__, $this->prefix, $this->currentPage);
		if ($this->user) {
			$this->user->setPref('Pager.' . $this->prefix, array('page' => $this->currentPage));
		}
	}

	/**
	 * @param int $items
	 */
	function setItemsPerPage($items)
	{
		if (!$items) {
			$items = $this->pageSize->selected;
		}
		$this->itemsPerPage = $items;
		$this->startingRecord = $this->getPageFirstItem($this->currentPage);
		//debug($this);
	}

	function getSQLLimit()
	{
		$limit = " LIMIT {$this->itemsPerPage} offset " . $this->startingRecord;
		return $limit;
	}

	function getStart()
	{
		return $this->startingRecord;
	}

	function getLimit()
	{
		return $this->itemsPerPage;
	}

	function getPageFirstItem($page)
	{
		return $page * $this->itemsPerPage;
	}

	function getPageLastItem($page)
	{
		return min($this->numberOfRecords, $page * $this->itemsPerPage + $this->itemsPerPage);
	}

	function isInPage($i)
	{
		return $i >= $this->getPageFirstItem($this->currentPage) &&
			$i < ($this->getPageFirstItem($this->currentPage) + $this->itemsPerPage);
	}

	/**
	 * 0 - page 10
	 * Alternative maybe ceil($div)-1 ?
	 * @return float
	 */
	function getMaxPage()
	{
		//$maxpage = ceil($this->numberOfRecords/$this->itemsPerPage);
		if ($this->itemsPerPage) {
			//$maxpage = floor($this->numberOfRecords/$this->itemsPerPage);	// because a single page is 0

			// new:
			$div = $this->numberOfRecords / $this->itemsPerPage;

			// zero based, this is wrong
			//$maxpage = ceil($div);

			// because a single page is 0
			$maxpage = floor($div);

			// 39/20 = 1.95 - correct
			// 40/20 = 2.00, but will fit in two pages
			// 41/20 = 2.05 - floor will make 2 (= 3 pages)
			//$maxpage += (!($div % 1)) ? -1 : 0;	// will fit completes in maxpage-1 pages
			$maxpage += ($div == floor($div)) ? -1 : 0;    // will fit completes in maxpage-1 pages
			$maxpage = max(0, $maxpage);    // not -1


		} else {
			$maxpage = 0;
		}
		return $maxpage;
	}

	function getCSS()
	{
		$l = new lessc();
		$css = $l->compileFile(dirname(__FILE__) . '/../CSS/PaginationControl.less');
		return '<style>' . $css . '</style>';
	}

	function renderPageSelectors(URL $url = NULL)
	{
		$content = '';
		$this->url = $url;

		if (!self::$cssOutput) {
			if (class_exists('Index') && $this->request->apacheModuleRewrite()) {
				//Index::getInstance()->header['ProgressBar'] = $this->getCSS();
				Index::getInstance()->addCSS('vendor/spidgorny/nadlib/CSS/PaginationControl.less');
			} elseif (false && $GLOBALS['HTMLHEADER']) {
				$GLOBALS['HTMLHEADER']['PaginationControl.less']
					= '<link rel="stylesheet" href="vendor/spidgorny/nadlib/CSS/PaginationControl.less" />';
			} elseif (!Request::isCLI()) {
				$content .= $this->getCSS();    // pre-compiles LESS inline
			}
			self::$cssOutput = true;
		}

		$content .= '<div class="pagination paginationControl">';
		$content .= $this->showSearchBrowser();
		if ($this->showPager) {
			$content .= $this->renderPager();
		}
		$content .= '</div>';
		return $content;
	}

	public function debug()
	{
		return array(
			'pager hash' => spl_object_hash($this),
			'numberOfRecords' => $this->numberOfRecords,
			'itemsPerPage' => $this->itemsPerPage,
			'pageSize->selected' => $this->pageSize->selected,
			'currentPage [0..]' => $this->currentPage,
			'floatPages' => $this->numberOfRecords / $this->itemsPerPage,
			'getMaxPage()' => $this->getMaxPage(),
			'startingRecord' => $this->startingRecord,
			'getSQLLimit()' => $this->getSQLLimit(),
			'getPageFirstItem()' => $this->getPageFirstItem($this->currentPage),
			'getPageLastItem()' => $this->getPageLastItem($this->currentPage),
			'getPagesAround()' => $pages = $this->getPagesAround($this->currentPage, $this->getMaxPage()),
			'url' => $this->url,
			'pagesAround' => $this->pagesAround,
			'showPageJump' => $this->showPageJump,
			'showPager' => $this->showPager,
			'prefix' => $this->prefix,
		);
	}

	function renderPager()
	{
		$this->pageSize->setURL(new URL(NULL, array()));
		$this->pageSize->selected = $this->itemsPerPage;
		$content = '<div class="pageSize pull-right">' . $this->pageSize->render() . ' ' . __('per page') . '</div>';
		return $content;
	}

	protected function showSearchBrowser()
	{
		$content = '';
		$maxpage = $this->getMaxPage();
		$pages = $this->getPagesAround($this->currentPage, $maxpage);
		//debug($pages, $maxpage);
		if ($this->currentPage > 0) {
			$link = $this->url->setParam('Pager_' . $this->prefix, array('page' => $this->currentPage - 1));
			$link = $this->url->setParam('pageSize', $this->pageSize->selected);
			$content .= '<li><a href="' . $link . '" rel="prev">&lt;</a></li>';
		} else {
			$content .= '<li class="disabled"><span class="disabled">&larr;</span></li>';
		}
		foreach ($pages as $k) {
			if ($k === 'gap1' || $k === 'gap2') {
				$content .= '<li class="disabled">
 					<span class="page"> &hellip; </span>
 				</li>';
			} else {
				$content .= $this->getSinglePageLink($k, $k + 1);
			}
		}
		if ($this->currentPage < $maxpage) {
			$link = $this->url->setParam('Pager_' . $this->prefix, array('page' => $this->currentPage + 1));
			$content .= '<li><a href="' . $link . '" rel="next">&gt;</a></li>';
		} else {
			$content .= '<li class="disabled"><span class="disabled">&rarr;</span></li>';
		}
		if ($this->showPageJump) {
			$form = "<form action='" . $this->url . "' method='POST' class='anyPageForm'>
				&nbsp;<input
					name='Pager_{$this->prefix}[page]'
					type='text'
					class='normal'
					value='" . ($this->currentPage + 1) . "'
					style='width: 2em; margin: 0' />
				<input type='submit' value='Page' class='submit' />
			</form>";
		}
		//debug($term);
		$content = '<ul>' . $content . '&nbsp;' . '</ul>' . $form;
		return $content;
	}

	function getSinglePageLink($k, $text)
	{
		$link = $this->url->setParam('Pager_' . $this->prefix, array('page' => $k));
		if ($k == $this->currentPage) {
			$content = '<li class="active"><a href="' . $link . '" class="active">' . $text . '</a></li>';
		} else {
			$content = '<li><a href="' . $link . '">' . $text . '</a></li>';
		}
		return $content;
	}

	function getPagesAround($current, $max)
	{
		$size = $this->pagesAround;
		$pages = array();
		for ($i = 0; $i < $size; $i++) {
			$k = $i;
			if ($k >= 0 && $k < $max) {
				$pages[] = $k;
			}
		}
		if ($k + 1 < $current - $size) {
			$pages[] = 'gap1';
		}
		for ($i = -$size; $i <= $size; $i++) {
			$k = $current + $i;
			if ($k >= 0 && $k <= $max) {
				$pages[] = $k;
			}
		}
		if ($max - $size > $k + 1) {
			$pages[] = 'gap2';
		}
		for ($i = $max - $size; $i <= $max; $i++) {
			$k = $i;
			if ($k >= 0 && $k <= $max) {
				$pages[] = $k;
			}
		}
		$pages = array_unique($pages);

		return $pages;
	}

	/**
	 * Converts the dbEdit init query into count(*) query by getCountQuery() method and runs it. Old style.
	 *
	 * @param dbEdit $dbEdit
	 * @return int
	 */
	function getCountedRows($dbEdit)
	{
		global $dbLayer;
		$queryCount = $dbEdit->getCountQuery();
		$res = $dbLayer->perform($queryCount);
		$row = pg_fetch_array($res);
		//debug($row, $queryCount);
		list($countedRows) = $row;
		return $countedRows;
	}

	function __toString()
	{
		$properties = get_object_vars($this);
		unset($properties['graphics']);
		foreach ($properties as $key => &$val) {
			if (is_object($val) && method_exists($val, '__toString')) {
				$val = $val->__toString();
			} else if (is_array($val)) {
				foreach ($val as &$v) {
					if (is_array($v)) {
						$v = $v->__toString();
					}
				}
			}
		}
		return '<blockquote style="background-color: silver; border: solid 1px lightblue;"><pre>' . get_class($this) . ' [' . print_r($properties, TRUE) . ']</pre></blockquote>';
	}

	function getURL()
	{
		return $this->url . '&pager[page]=' . ($this->currentPage);
	}

	function getObjectInfo()
	{
		return get_class($this) . ': "' . $this->itemsPerPage . '" (id:' . $this->id . ' #' . spl_object_hash($this) . ')';
	}

	/**
	 * @param \LoginUser $user
	 */
	public function setUser($user)
	{
		$this->user = $user;
	}

	/**
	 * @return \LoginUser
	 */
	public function getUser()
	{
		return $this->user;
	}
}
