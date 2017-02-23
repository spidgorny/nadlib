<?php

class Pager {

	/**
	 * Total amount of rows in database (with WHERE)
	 * @var int
	 */
	var $numberOfRecords = 0;

	/**
	 * Page size
	 * @var int
	 */
	var $itemsPerPage = 20;

	/**
	 * Offset in SQL
	 * @var int
	 */
	var $startingRecord = 0;

	/**
	 * Current Page (0+)
	 * @var int
	 */
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
	 * @var User|LoginUser|blUser|grUser|NadlibUser
	 */
	protected $user;

	public $showPageJump = true;

	public $showPager = true;

	/**
	 * @var PageSize
	 */
	public $pageSize;

	static $cssOutput = false;

	/**
	 * Mouse over tooltip text per page
	 * @var array
	 */
	var $pageTitles = array();

	/**
	 * @var Iterator
	 */
	var $iterator;

	function __construct($itemsPerPage = NULL, $prefix = '') {
		if ($itemsPerPage instanceof PageSize) {
			$this->pageSize = $itemsPerPage;
		} else {
			$this->pageSize = new PageSize($itemsPerPage ?: $this->itemsPerPage);
		}
		$this->setItemsPerPage($this->pageSize->get()); // only allowed amounts
		$this->prefix = $prefix;
		$config = Config::getInstance();
		$this->db = $config->getDB();
		$this->request = Request::getInstance();
		$this->setUser($config->getUser());
		// Inject dependencies, this breaks all projects which don't have DCI class
        //if (!$this->user) $this->user = DCI::getInstance()->user;
		$config->mergeConfig($this);
		$this->url = new URL();	// just in case
	}

	/**
	 * To be called only after setNumberOfRecords()
	 */
	function detectCurrentPage() {
		$pagerData = ifsetor($_REQUEST['Pager.'.$this->prefix],
			ifsetor($_REQUEST['Pager_'.$this->prefix]));
		//debug($pagerData);
		if ($pagerData) {
			if (ifsetor($pagerData['startingRecord'])) {
				$this->startingRecord = (int)($pagerData['startingRecord']);
				$this->currentPage = $this->startingRecord / $this->itemsPerPage;
			} else {
				// when typing page number in [input] box
				if (!$this->request->isAjax() && $this->request->isPOST()) {
					//Debug::debug_args($pagerData);
					$pagerData['page']--;
				}
				$this->setCurrentPage($pagerData['page']);
				$this->saveCurrentPage();
			}
		} elseif ($this->user && method_exists($this->user, 'getPref')) {
			$pager = $this->user->getPref('Pager.'.$this->prefix);
			if ($pager) {
				//debug(__METHOD__, $this->prefix, $pager['page']);
				$this->setCurrentPage($pager['page']);
			}
		} else {
			$this->setCurrentPage(0);
		}
	}

	function initByQuery($originalSQL) {
		if (is_string($originalSQL)) {
			$this->initByStringQuery($originalSQL);
		} elseif ($originalSQL instanceof SQLSelectQuery) {
			$this->initBySelectQuery($originalSQL, $originalSQL->getParameters());
		} else {
			throw new InvalidArgumentException(__METHOD__);
		}
	}

	function initByStringQuery($originalSQL) {
		//debug_pre_print_backtrace();
		$key = __METHOD__.' ('.substr($originalSQL, 0, 300).')';
		TaylorProfiler::start($key);
		$queryObj = new SQLQuery($originalSQL);
		// not allowed or makes no sense
		unset($queryObj->parsed['ORDER']);
		if ($this->db instanceof dbLayerMS) {
			$query = $this->db->fixQuery($queryObj);
		} else {
			$query = $queryObj->getQuery();
		}
		//debug($query->parsed['WHERE']);
		$countQuery = "SELECT count(*) AS count
		FROM (".$query.") AS counted";
//		debug($query.'', $query->getParameters(), $countQuery);
//		exit();
		$res = $this->db->fetchAssoc(
			$this->db->perform($countQuery));
			// , $query->getParameters()
		$this->setNumberOfRecords($res['count']);
		//debug($originalSQL, $query, $res);
		$this->detectCurrentPage();
		TaylorProfiler::stop($key);
	}

	function initBySelectQuery(SQLSelectQuery $originalSQL, array $parameters = []) {
		$key = __METHOD__.' ('.substr($originalSQL, 0, 300).')';
		TaylorProfiler::start($key);
		$queryWithoutOrder = clone $originalSQL;
		$queryWithoutOrder->unsetOrder();

		$subquery = new SQLSubquery($queryWithoutOrder, 'counted');
		$subquery->parameters = $parameters;

		$query = new SQLSelectQuery(
			new SQLSelect('count(*) AS count'),
			$subquery);
		$query->injectDB($this->db);

		$res = $query->fetchAssoc();
		$this->setNumberOfRecords($res['count']);
		$this->detectCurrentPage();
		TaylorProfiler::stop($key);
	}

	/**
	 * @param $i
	 */
	function setNumberOfRecords($i) {
		$this->numberOfRecords = $i;
		if ($this->startingRecord > $this->numberOfRecords) {	// required
			$this->setCurrentPage($this->currentPage);
			if ($this->request->isPOST()) {
				$_POST['pager']['page'] = $this->currentPage+1;
			}
		}
	}

	/**
	 * Make sure to setNumberOfRecords first(!)
	 * @param $page
	 */
	function setCurrentPage($page) {
		//max(0, ceil($this->numberOfRecords/$this->itemsPerPage)-1);    // 0-indexed
		$page = min($page, $this->getMaxPage());
		$this->currentPage = max(0, $page);
		$this->startingRecord = $this->getPageFirstItem($this->currentPage);
	}

	function saveCurrentPage() {
		//debug(__METHOD__, $this->prefix, $this->currentPage);
		if ($this->user instanceof UserWithPreferences) {
			$this->user->setPref('Pager.'.$this->prefix, array(
				'page' => $this->currentPage
			));
		}
	}

	/**
	 * @param int $items
	 */
	function setItemsPerPage($items) {
		if (!$items) {
			$items = $this->pageSize->selected;
		}
			$this->itemsPerPage = $items;
			$this->startingRecord = $this->getPageFirstItem($this->currentPage);
		//debug($this);
	}

	function getSQLLimit($query) {
		$scheme = $this->db->getScheme();
		if ($scheme == 'ms') {
			$query = $this->db->addLimit($query, $this->itemsPerPage, $this->startingRecord);
		} elseif ($query instanceof SQLSelectQuery) {
			$query->setLimit(new SQLLimit($this->itemsPerPage, $this->startingRecord));
		} else {
			$limit = "\nLIMIT ".$this->itemsPerPage.
			"\nOFFSET " . $this->startingRecord;
			$query .= $limit;
		}
		return $query;
	}

	function getStart() {
		return $this->startingRecord;
	}

	function getLimit() {
		return $this->itemsPerPage;
	}

	function getPageFirstItem($page) {
		return $page*$this->itemsPerPage;
	}

	function getPageLastItem($page) {
		return min($this->numberOfRecords, $page*$this->itemsPerPage + $this->itemsPerPage);
	}

	function isInPage($i) {
		return $i >= $this->getPageFirstItem($this->currentPage) &&
			   $i < ($this->getPageFirstItem($this->currentPage)+$this->itemsPerPage);
	}

	/**
	 * 0 - page 10
	 * Alternative maybe ceil($div)-1 ?
	 * @return float
	 */
	function getMaxPage() {
		//$maxpage = ceil($this->numberOfRecords/$this->itemsPerPage);
		if ($this->itemsPerPage) {
			//$maxpage = floor($this->numberOfRecords/$this->itemsPerPage);	// because a single page is 0

			// new:
			$div = $this->numberOfRecords/$this->itemsPerPage;

			// zero based, this is wrong
			//$maxpage = ceil($div);

			// because a single page is 0
			$maxpage = floor($div);

			// 39/20 = 1.95 - correct
			// 40/20 = 2.00, but will fit in two pages
			// 41/20 = 2.05 - floor will make 2 (= 3 pages)
			//$maxpage += (!($div % 1)) ? -1 : 0;	// will fit completes in maxpage-1 pages
			$maxpage += ($div == floor($div)) ? -1 : 0;	// will fit completes in maxpage-1 pages
			$maxpage = max(0, $maxpage);	// not -1


		} else {
			$maxpage = 0;
		}
		return $maxpage;
	}

	function getCSS() {
		if (class_exists('lessc')) {
			$l = new lessc();
			$css = $l->compileFile(dirname(__FILE__) . '/../CSS/PaginationControl.less');
			return '<style>' . $css . '</style>';
		} else {
			return '';
		}
	}

	function renderPageSelectors(URL $url = NULL) {
		$content = '';
		if ($url) {
			$this->url = $url;
		}

		if (!self::$cssOutput) {
			$al = AutoLoad::getInstance();
			$index = class_exists('Index') ? Index::getInstance() : NULL;
			if ($index && $this->request->apacheModuleRewrite()) {
				//Index::getInstance()->header['ProgressBar'] = $this->getCSS();
				$index->addCSS($al->nadlibFromDocRoot . 'CSS/PaginationControl.less');
			} elseif (false && $GLOBALS['HTMLHEADER']) {
				$GLOBALS['HTMLHEADER']['PaginationControl.less']
					= '<link rel="stylesheet" href="'.$al->nadlibFromDocRoot.'CSS/PaginationControl.less" />';
			} elseif (!Request::isCLI()) {
				$content .= $this->getCSS();	// pre-compiles LESS inline
			}
			self::$cssOutput = true;
		}

		$content .= '<div class="paginationControl pagination">';
		$content .= $this->showSearchBrowser();
		if ($this->showPager) {
			$content .= $this->renderPageSize();	// will render UL inside
		}
		$content .= '</div>';
		return $content;
	}

	public function debug() {
		return array(
			'pager hash' => spl_object_hash($this),
			'numberOfRecords' => $this->numberOfRecords,
			'itemsPerPage' => $this->itemsPerPage,
			'pageSize->selected' => $this->pageSize->selected,
			'currentPage [0..]' => $this->currentPage,
			'floatPages' => $this->numberOfRecords/$this->itemsPerPage,
			'getMaxPage()' => $this->getMaxPage(),
			'startingRecord' => $this->startingRecord,
			//'getSQLLimit()' => $this->getSQLLimit(),
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

	function renderPageSize() {
		$this->pageSize->setURL(new URL(NULL, array()));
		$this->pageSize->selected = $this->itemsPerPage;
		$content = '<div class="pageSize pull-right floatRight">'.
				$this->pageSize->render().'&nbsp;'.__('per page').'</div>';
		return $content;
	}

	public function showSearchBrowser() {
		$content = '';
		$maxpage = $this->getMaxPage();
 		$pages = $this->getPagesAround($this->currentPage, $maxpage);
 		//debug($pages, $maxpage);
 		if ($this->currentPage > 0) {
			$link = $this->url->setParam('Pager_'.$this->prefix, array('page' => $this->currentPage-1));
			$link = $link->setParam('pageSize', $this->pageSize->selected);
			$content .= '<li><a href="'.$link.'" rel="prev">&lt;</a></li>';
 		} else {
	 		$content .= '<li class="disabled"><span class="disabled">&larr;</span></li>';
 		}
 		foreach ($pages as $k) {
 			if ($k === 'gap1' || $k === 'gap2') {
 				$content .= '<li class="disabled">
 					<span class="page"> &hellip; </span>
 				</li>';
 			} else {
				$content .= $this->getSinglePageLink($k, $k+1);
 			}
		}
 		if ($this->currentPage < $maxpage) {
			$link = $this->url->setParam('Pager_'.$this->prefix, array('page' => $this->currentPage+1));
			$content .= '<li><a href="'.$link.'" rel="next">&gt;</a></li>';
 		} else {
	 		$content .= '<li class="disabled"><span class="disabled">&rarr;</span></li>';
 		}
		if ($this->showPageJump) {
			$form = "<form action='".$this->url."' method='POST' class='anyPageForm'>
				&nbsp;<input
					name='Pager_{$this->prefix}[page]'
					type='text'
					class='normal'
					value='".($this->currentPage+1)."'
					style='width: 2em; margin: 0' />
				<input type='submit' value='Page' class='submit' />
			</form>";
		} else {
			$form = '';
		}
 		//debug($term);
		$content = '<ul class="pagination">'.$content.'&nbsp;'.'</ul>'.$form;
		return $content;
	}

	function getSinglePageLink($k, $text) {
		$link = $this->url->setParam('Pager_'.$this->prefix, array('page' => $k));
		if ($k == $this->currentPage) {
			$content = '<li class="active"><a href="'.$link.'"
				class="active"
				title="'.htmlspecialchars(ifsetor($this->pageTitles[$k]), ENT_QUOTES).'"
				>'.$text.'</a></li>';
		} else {
			$content = '<li><a href="'.$link.'"
			title="'.htmlspecialchars(ifsetor($this->pageTitles[$k]), ENT_QUOTES).'"
			>'.$text.'</a></li>';
		}
		return $content;
	}

	/**
	 * @param $current
	 * @param $max
	 * @return array
	 */
	function getPagesAround($current, $max) {
		$size = $this->pagesAround;
		$pages = array();
		$k = 0;
		for ($i = 0; $i < $size; $i++) {
			$k = $i;
			if ($k >= 0 && $k < $max) {
				$pages[] = $k;
			}
		}
		if ($k + 1 < $current-$size) {
			$pages[] = 'gap1';
		}
		for ($i = -$size; $i <= $size; $i++) {
			$k = $current+$i;
			if ($k >= 0 && $k <= $max) {
				$pages[] = $k;
			}
		}
		if ($max - $size > $k+1) {
			$pages[] = 'gap2';
		}
		for ($i = $max-$size; $i <= $max; $i++) {
			$k = $i;
			if ($k >= 0 && $k <= $max) {
				$pages[] = $k;
			}
		}
		$pages = array_unique($pages);

		return $pages;
	}

	function getVisiblePages() {
		return $this->getPagesAround($this->currentPage, $this->getMaxPage());
	}

	function __toString() {
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
		return '<blockquote style="background-color: silver; border: solid 1px lightblue;"><pre>'.get_class($this).' ['.print_r($properties, TRUE).']</pre></blockquote>';
	}

	function getURL() {
		return $this->url.'&pager[page]='.($this->currentPage);
	}

	function getObjectInfo() {
		return get_class($this).': "'.$this->itemsPerPage.'" (id:'.$this->id.' #'.spl_object_hash($this).')';
	}

    /**
     * @param User|LoginUser $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return User|\LoginUser
     */
    public function getUser()
    {
        return $this->user;
    }

	function loadMoreButton($controller) {
		$content = '';
		//debug($pager->currentPage, $pager->getMaxPage());
		if ($this->currentPage < $this->getMaxPage()) {
			$loadPage = $this->currentPage+1;
			$f = new HTMLForm();
			$f->hidden('c', $controller);
			$f->hidden('action', 'loadMore');
			$f->hidden('Pager.[page]', $loadPage);
			$f->formHideArray(array($this->prefix => $this->request->getArray($this->prefix)));
			$f->formMore = 'onsubmit="return ajaxSubmitForm(this);"';
			$f->submit(__('Load more'), array('class' => 'btn'));
			$content .= '<div id="loadMorePage'.$loadPage.'">'.$f.'</div>';
		}
		return $content;
	}

	function setIterator(Iterator $iterator) {
		$this->iterator = $iterator;
		$this->setNumberOfRecords($iterator->count());
		$this->detectCurrentPage();
	}

	function getPageData() {
		$data = [];

		$start = $this->getStart();
		for ($i = 0; $i < $start; $i++) {
			$this->iterator->next();
		}

		$size = $this->itemsPerPage;
		for ($i = 0; $i < $size; $i++) {
			if ($this->iterator->valid()) {
				$data[] = $this->iterator->current();
				$this->iterator->next();
			}
		}

		return $data;
	}

}
