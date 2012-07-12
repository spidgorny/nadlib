<?php

class Pager {
	var $numberOfRecords = 0;
	var $itemsPerPage = 20;
	var $startingRecord = 0;
	var $currentPage = 0;
	var $url;
	var $pagesAround = 3;

	function Pager($itemsPerPage = NULL) {
		if ($itemsPerPage) {
			$this->setItemsPerPage($itemsPerPage);
		}
		if (($pagerData = $_REQUEST['pager'])) {
//			printbr("Pager initialized with REQUEST");
			$this->currentPage = (int)($pagerData['page']);
			Config::getInstance()->user->setPref('Pager', array('page' => $this->currentPage));
		} else if (($pager = Config::getInstance()->user->getPref('Pager'))) {
//			printbr("Pager initialized with SESSION");
			$this->currentPage = $pager['page'];
		} else {
//			printbr("Pager initialized with default");
			$this->currentPage = 0;
		}
		$this->startingRecord = $this->getPageFirstItem($this->currentPage);
	}

	function initByQuery($query) {
		$query = "SELECT count(*) AS count FROM (".$query.") AS counted";
		$res = $GLOBALS['i']->db->fetchAssoc($query);
		$this->setNumberOfRecords($res['count']);
	}

	function setNumberOfRecords($i) {
		$this->numberOfRecords = $i;
		if ($this->startingRecord > $this->numberOfRecords) {
			$this->startingRecord = 0;
			$this->currentPage = 0;
		}
	}

	function setCurrentPage($page) {
		$this->currentPage = $page;
		$this->startingRecord = $this->getPageFirstItem($this->currentPage);
	}

	function setItemsPerPage($items) {
		$this->itemsPerPage = $items;
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

	protected function showSearchBrowser() {
		$maxpage = $this->getMaxPage();
 		$pages = $this->getPagesAround($this->currentPage, $maxpage);
 		//debug(array($pages, $current['searchIndex'], sizeof($tmpArray)));
 		if ($this->currentPage > 0) {
			$link = $this->url.'&pager[page]='.($this->currentPage-1);
			$content .= '<a href="'.$link.'">&lt;</a>';
 		} else {
	 		$content .= '<span class="disabled">&lt;</span>';
 		}
 		foreach ($pages as $k) {
 			if ($k === 'gap1' || $k === 'gap2') {
 				$content .= '<div class="page">  &hellip;  </div>';
 			} else {
				$link = $this->url.'&pager[page]='.$k;
				if ($k == $this->currentPage) {
					$content .= '<span class="active">'.($k+1).'</span>';
				} else {
					$content .= '<a href="'.$link.'">'.($k+1).'</a>';
				}
 			}
		}
 		if ($this->currentPage < (sizeof($pages)-1)) {
			$link = $this->url.'&pager[page]='.($this->currentPage+1);
			$content .= '<a href="'.$link.'">&gt;</a>';
 		} else {
	 		$content .= '<span class="disabled">&gt;</span>';
 		}
		$form = "<form action='".$this->url."' method='POST'>
			&nbsp;<input name='pager[page]' class='normal' value='".($_POST['pager']['page'])."' size='3'>
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

	function renderPageSelectors($url) {
//		$ret = array();
		$this->url = $url;
		$c = $this->showSearchBrowser();
//		$pages = ceil($this->numberOfRecords/$this->itemsPerPage);
//		for ($p = 0; $p < $pages; $p++) {
//			$startItem = $this->numberOfRecords - $this->getPageFirstItem($p);
//			$endItem  = $startItem - $this->itemsPerPage;
//			if ($endItem < 0) $endItem = 0;
//			$endItem++;
//			$text = $p+1;
//			$title = 'title="Show another page"';
//			if ($this->currentPage == $p) {
//				$ret[] = ahref($text, $url . '&pager[page]='.$p, NULL, 'class="active"'.$title);
//			} else {
//				$ret[] = ahref($text, $url . '&pager[page]='.$p, NULL, $title);
//			}
//		}
		return $c;//'<div class="pages">'.implode(" ", $ret).'</div><br clear="left" style="font-size: 1px;"/>';
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
}
