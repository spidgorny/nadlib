<?php

class Pager {
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

	function Pager($itemsPerPage = NULL, $prefix = '') {
		if ($itemsPerPage) {
			$this->setItemsPerPage($itemsPerPage);
		}
		$this->prefix = $prefix;
		$this->db = Config::getInstance()->db;
		$this->request = Request::getInstance();
		$this->user = Config::getInstance()->user;
		if (($pagerData = $_REQUEST['Pager_'.$this->prefix])) {
			if ($this->request->getMethod() == 'POST') {
				$pagerData['page']--;
			}
			$this->setCurrentPage($pagerData['page']);
			$this->saveCurrentPage();
		} else if ($this->user && ($pager = $this->user->getPref('Pager.'.$this->prefix))) {
			//debug(__METHOD__, $this->prefix, $pager['page']);
			$this->setCurrentPage($pager['page']);
		} else {
			$this->setCurrentPage(0);
		}
	}

	function initByQuery($query) {
		$query = "SELECT count(*) AS count FROM (".$query.") AS counted";
		$res = $this->db->fetchAssoc($query);
		$this->setNumberOfRecords($res['count']);
	}

	function setNumberOfRecords($i) {
		$this->numberOfRecords = $i;
		if ($this->startingRecord > $this->numberOfRecords) {
			$this->currentPage = max(0, ceil($this->numberOfRecords/$this->itemsPerPage)-1);    // 0-indexed
			//debug($this->currentPage);
			if ($this->request->isPOST()) {
				$_POST['pager']['page'] = $this->currentPage+1;
			}
			$this->startingRecord = $this->getPageFirstItem($this->currentPage);
		}
	}

	function setCurrentPage($page) {
		$this->currentPage = max(0, $page);
		$this->startingRecord = $this->getPageFirstItem($this->currentPage);
	}

	function saveCurrentPage() {
		//debug(__METHOD__, $this->prefix, $this->currentPage);
		$this->user->setPref('Pager.'.$this->prefix, array('page' => $this->currentPage));
	}

	function setItemsPerPage($items) {
		$this->itemsPerPage = $items;
		$this->startingRecord = $this->getPageFirstItem($this->currentPage);
		//debug($this);
	}

	function getSQLLimit() {
		$limit = " LIMIT {$this->itemsPerPage} offset " . $this->startingRecord;
		//printbr($limit);
		return $limit;
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

	function isInPage($i) {
		return $i >= $this->getPageFirstItem($this->currentPage) && $i < ($this->getPageFirstItem($this->currentPage)+$this->itemsPerPage);
	}

	function getMaxPage() {
		$maxpage = ceil($this->numberOfRecords/$this->itemsPerPage);
		return $maxpage;
	}

	function renderPageSelectors(URL $url = NULL) {
		$this->url = $url;
		$c = $this->showSearchBrowser();
		return $c;//'<div class="pages">'.implode(" ", $ret).'</div><br clear="left" style="font-size: 1px;"/>';
	}

	protected function showSearchBrowser() {
		$content = '';
		$maxpage = $this->getMaxPage();
 		$pages = $this->getPagesAround($this->currentPage, $maxpage);
 		//debug(array($pages, $current['searchIndex'], sizeof($tmpArray)));
 		if ($this->currentPage > 0) {
			$link = $this->url->setParam('Pager.'.$this->prefix.'[page]', $this->currentPage-1);
			$content .= '<a href="'.$link.'" rel="prev">&lt;</a>';
 		} else {
	 		$content .= '<span class="disabled">&lt;</span>';
 		}
 		foreach ($pages as $k) {
 			if ($k === 'gap1' || $k === 'gap2') {
 				$content .= '<div class="page">  &hellip;  </div>';
 			} else {
				 $link = $this->url->setParam('Pager.'.$this->prefix.'[page]', $k);
				if ($k == $this->currentPage) {
					$content .= '<span class="active">'.($k+1).'</span>';
				} else {
					$content .= '<a href="'.$link.'">'.($k+1).'</a>';
				}
 			}
		}
 		if ($this->currentPage < $maxpage-1) {
			 $link = $this->url->setParam('Pager.'.$this->prefix.'[page]', $this->currentPage+1);
			$content .= '<a href="'.$link.'" rel="next">&gt;</a>';
 		} else {
	 		$content .= '<span class="disabled">&gt;</span>';
 		}
		$form = "<form action='".$this->url."' method='POST' class='inline'>
			&nbsp;<input name='Pager.'.$this->prefix.'[page]' class='normal' value='".($this->currentPage+1)."' size='3'>
			<input type='submit' value='Page' class='submit'>
		</form>";
 		//debug($term);
		$content = '<div class="paginationControl">'.$content.'&nbsp;'.$form.'</div>';
		//$content = $this->enclose('Search Browser ('.sizeof($tmpArray).')', $content);
		return $content;
	}

	function getPagesAround($current, $max) {
		$size = $this->pagesAround;
		$_s = 3;
		$pages = array();
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
			if ($k >= 0 && $k < $max) {
				$pages[] = $k;
			}
		}
		if ($max - $size > $k+1) {
			$pages[] = 'gap2';
		}
		for ($i = $max-$size; $i < $max; $i++) {
			$k = $i;
			if ($k >= 0 && $k < $max) {
				$pages[] = $k;
			}
		}
		$pages = array_unique($pages);

		return $pages;
	}

	/**
	 * Converts the dbEdit init query into count(*) query by getCountQuery() method and runs it. Old style.
	 *
	 * @param unknown_type $dbEdit
	 * @return unknown
	 */
	function getCountedRows($dbEdit) {
		global $dbLayer;
		$queryCount = $dbEdit->getCountQuery();
		list($countedRows) = pg_fetch_array($dbLayer->perform($queryCount));
		return $countedRows;
	}

	function __toString() {
		$properties = get_object_vars($this);
		unset($properties['graphics']);
		foreach ($properties as $key => &$val) {
			if (is_object($val)) {
				$val = $val->__toString();
			} else if (is_array($val)) {
				foreach ($val as $k => &$v) {
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

}
