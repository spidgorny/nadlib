<?php

class CollectionView {

	/**
	 * @var Collection
	 */
	var $collection;

	var $noDataMessage = 'No data';

	/**
	 * Indication to slTable
	 * @var bool
	 */
	public $useSorting = true;

	public $tableMore = array(
		'class' => "nospacing table table-striped",
		'width' => "100%",
	);

	public $wrapTag = 'div';

	function __construct(Collection $col) {
		$this->collection = $col;
	}

	function __toString() {
		return MergedContent::mergeStringArrayRecursive($this->renderMembers());
	}

	function renderMembers() {
		$content = array();
		//debug(sizeof($this->members));
		if ($this->collection->objectify()) {
			/**
			 * @var int $key
			 * @var OODBase $obj
			 */
			foreach ($this->collection->objectify() as $key => $obj) {
				//debug($i++, (strlen($content)/1024/1024).'M');
				if (is_object($obj)) {
					$content[] = $obj->render();
					$content[] = "\n";
				} else {
					$content[] = getDebug(__METHOD__, $key, $obj);
				}
			}
			if ($this->wrapTag) {
				$content = array(
					'<'.$this->wrapTag.' class="' . get_class($this->collection) . '">',
					$content,
					'</'.$this->wrapTag.'>'
				);
			}
		} elseif ($this->noDataMessage) {
			//Index::getInstance()->ll->debug = true;
			$content[] = '<div class="message alert alert-warning">'.__($this->noDataMessage).'</div>';
		}
		if ($this->collection->pager) {
			//$this->pager->debug();
			$url = new URL();
			$pages = $this->collection->pager->renderPageSelectors($url);
			$content = array($pages, $content, $pages);
		}
		return $content;
	}

	function renderTable() {
		TaylorProfiler::start(__METHOD__." ({$this->collection->table})");
		$this->collection->log(get_class($this).'::'.__FUNCTION__.'()');
		if ($this->collection->getCount()) {
			$this->prepareRender();
			//debug($this->tableMore);
			$s = $this->getDataTable();
			if ($this->collection->pager) {
				$url = new URL();
				$pages = $this->collection->pager->renderPageSelectors($url);
				$content = $pages . $s->getContent(get_class($this)) . $pages;
			} else {
				$content = $s;
			}
		} else {
			$content = '<div class="message alert alert-warning">'.__($this->noDataMessage).'</div>';
		}
		TaylorProfiler::stop(__METHOD__." ({$this->collection->table})");
		return $content;
	}

	function prepareRender() {
		TaylorProfiler::start(__METHOD__." ({$this->collection->table})");
		$this->collection->log(get_class($this).'::'.__FUNCTION__.'()');
		$data = $this->collection->getProcessedData();
		$count = $this->collection->getCount();
		// Iterator by reference (PHP 5.4.15 crash)
		foreach ($data as $i => $row) {
			$row = $this->collection->prepareRenderRow($row);
			$data[$i] = $row;
		}
		$this->collection->setData($data);
		$this->collection->count = $count;
		TaylorProfiler::stop(__METHOD__." ({$this->collection->table})");
	}

	function getDataTable() {
		$this->collection->log(get_class($this).'::'.__FUNCTION__.'()');
		$s = new slTable($this->collection->getData()->getData(),
			HTMLTag::renderAttr($this->tableMore));
		$s->thes($this->collection->thes);
		$s->ID = get_class($this->collection);
		$s->sortable = $this->useSorting;
		if (class_exists('Index')) {
			$index = Index::getInstance();
			$controller = $index->getController();
			if ($sort = ifsetor($controller->sort)) {
				$s->setSortBy(ifsetor($sort['sortBy']), ifsetor($sort['sortOrder']));	// UGLY
				//debug(Index::getInstance()->controller);
				$s->sortLinkPrefix = new URL(NULL,
					ifsetor($controller->linkVars)
						? $controller->linkVars
						: array());
			}
		}
		return $s;
	}

}
