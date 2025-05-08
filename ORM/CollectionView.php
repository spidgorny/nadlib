<?php

class CollectionView
{

	protected \Collection $collection;

	public $noDataMessage = 'No data';

	/**
	 * Indication to slTable
	 * @var bool
	 */
	public $useSorting = true;

	public $tableMore = [
		'class' => 'nospacing table table-striped',
		'width' => '100%',
	];

	public $wrapTag = 'div';

	public function __construct(Collection $col)
	{
		$this->collection = $col;
	}

	public function __toString(): string
	{
		return MergedContent::mergeStringArrayRecursive($this->renderMembers());
	}

	public function wrap($content)
	{
		if ($this->wrapTag) {
			[$tagClass, $id] = trimExplode('#', $this->wrapTag, 2);
			[$tag, $class] = trimExplode('.', $tagClass, 2);
			$content = [
				'<' . $tag . ' class="' . get_class($this->collection) . ' ' . $class . '" id="' . $id . '">',
				$content,
				'</' . $tag . '>'
			];
		}

		return $content;
	}

	public function renderMembers()
	{
		$content = [];
//		llog(sizeof($this->collection->objectify()));
		if (!$this->collection->objectify()) {
			if ($this->noDataMessage) {
				//Index::getInstance()->ll->debug = true;
				$content[] = '<div class="message alert alert-warning">' . __($this->noDataMessage) . '</div>';
			}
			return $content;
		}

		/**
		 * @var OODBase $obj
		 */
		foreach ($this->collection->objectify() as $obj) {
			$content[] = $obj->render();
			$content[] = "\n";
		}

		$content = $this->wrap($content);

		if ($this->collection->pager) {
			$pages = $this->collection->pager->renderPageSelectors();
			$content = [$pages, $content, $pages];
		}

		return $content;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function renderTable()
	{
		$count = $this->collection->getCount();
//		llog($this->collection->getQueryWithLimit() . '', $count);
		if ($count === 0) {
			return '<div class="message alert alert-warning">' . __($this->noDataMessage) . '</div>';
		}

		$this->prepareRender();
		//debug($this->tableMore);
		$s = $this->getDataTable();
		if ($this->collection->pager) {
			$pages = $this->collection->pager->renderPageSelectors();
			$content[] = [$pages .
				'<div class="collection"
				 id="' . get_class($this->collection) . '">',
				$s,  // not HTML, may need to process later
				'</div>',
				$pages];
		} else {
			$content[] = $s;
		}

		return $this->wrap($content);
	}

	public function prepareRender(): void
	{
		$this->collection->log(get_class($this) . '::' . __FUNCTION__ . '()');
		$data = $this->collection->getProcessedData();
		$count = $this->collection->getCount();
		// Iterator by reference (PHP 5.4.15 crash)
		foreach ($data as $i => $row) {
			$row = $this->collection->prepareRenderRow($row);
			$data[$i] = $row;
		}

		$this->collection->setData($data);
		$this->collection->count = $count;
		$this->collection->log(get_class($this) . '::' . __FUNCTION__ . '() done');
	}

	public function getDataTable(): \slTable
	{
		$this->collection->log(get_class($this) . '::' . __FUNCTION__ . '()');
		$data = $this->collection->getData()->getData();
		$s = new slTable($data, HTMLTag::renderAttr($this->tableMore));
		$s->thes($this->collection->thes);
		$s->ID = get_class($this->collection);
		$s->sortable = $this->useSorting;

		// removed for phpstan, but not sure if it is needed
//		if (class_exists('Index') && Index::getInstance()) {
//			$index = Index::getInstance();
//			$controller = $index->getController();
//			$sort = ifsetor($controller->sort);
////			debug($sort);
//			if ($sort) {
//				$s->setSortBy(ifsetor($sort['sortBy']), ifsetor($sort['sortOrder']));    // UGLY
//				//debug(Index::getInstance()->controller);
//				$s->sortLinkPrefix = new URL(
//					null,
//					ifsetor($controller->linkVars)
//						? $controller->linkVars
//						: []);
//			}
//		}

		$this->collection->log(get_class($this) . '::' . __FUNCTION__ . '() done');
		return $s;
	}

}
