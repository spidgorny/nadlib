<?php

/**
 * Class NextPrevBrowser - to display current item from the collection
 * And buttons to go to next/prev item.
 */
class NextPrevBrowser
{

	public $prevText = '&#x25C4;';
	public $nextText = '&#x25BA;';
	protected \Collection $collection;
	/** @var ArrayPlus */
	protected $data;
	/** @var Pager */
	protected $pager;

	public function __construct(Collection $collection)
	{
		$this->collection = $collection;
		$this->data = $collection->getData();
		$this->pager = $collection->pager;
		$this->prevText = __($this->prevText);
		$this->nextText = __($this->nextText);
	}

	/**
     * Only $model->id is used to do ArrayPlus::getNextKey() and $mode->getName() for display
     *
     * If pager is used then it tries to retrieve page before and after to make sure that first and last
     * elements on the page still have prev and next elements. But it's SLOW!
     *
     * @throws Exception
     */
    public function getNextPrevBrowser(OODBase $model): string
	{
		if ($this->pager) {
			//$this->pager->debug();
			if ($this->pager->currentPage > 0) {
				$copy = clone $this->collection;
				$copy->pager->setCurrentPage($copy->pager->currentPage - 1);
				$copy->retrieveData();
				$copy->preprocessData();
				$prevData = $copy->getData()->getData();
			} else {
				$prevData = [];
			}

			llog('loading pageKeys');
			$pageKeys = $this->data->getKeys()->getData();
			llog('pageKeys', $pageKeys);
			if ($this->pager->currentPage < $this->pager->getMaxPage() &&
				end($pageKeys) == $model->id    // last element on the page
			) {
				$copy = clone $this->collection;
				$nextPageNumber = $copy->pager->currentPage + 1;
				llog('nextPageNumber', $nextPageNumber);
				$copy->pager->setCurrentPage($nextPageNumber);
				$copy->retrieveData();
				llog('fetched', $copy->getData()->count());
				$copy->preprocessData();
				$nextData = $copy->getData()->getData();
			} else {
				$nextData = [];
			}
		} else {
            $prevData = [];
            $nextData = [];
        }

		$central = ($this->data instanceof ArrayPlus)
			? $this->data->getData()
			: ($this->data ?: [])  // NOT NULL
		;

//		llog($model->id,
//			str_replace($model->id, '*' . $model->id . '*', implode(', ', array_keys((array)$prevData))),
//			str_replace($model->id, '*' . $model->id . '*', implode(', ', array_keys((array)$this->data))),
//			str_replace($model->id, '*' . $model->id . '*', implode(', ', array_keys((array)$nextData)))
//		);
		$data = $prevData + $central + $nextData; // not array_merge which will reindex
		$ap = ArrayPlus::create($data);
		//debug($data);

		$prev = $ap->getPrevKey($model->id);
		if ($prev) {
			$prev = $this->getNextPrevLink($data[$prev], $this->prevText);
		} else {
			$prev = '<span class="muted">' . $this->prevText . '</span>';
		}

		$next = $ap->getNextKey($model->id);
		if ($next) {
			$next = $this->getNextPrevLink($data[$next], $this->nextText);
		} else {
			$next = '<span class="muted">' . $this->nextText . '</span>';
		}

		$content = $this->renderPrevNext($prev, $model, $next);

		// switch page for the next time
		if (isset($prevData[$model->id])) {
			$this->pager->setCurrentPage($this->pager->currentPage - 1);
			$this->pager->saveCurrentPage();
		}

		if (isset($nextData[$model->id])) {
			$this->pager->setCurrentPage($this->pager->currentPage + 1);
			$this->pager->saveCurrentPage();
		}

		return $content;
	}

	/**
     * Override to make links from different type of objects
     * @param $prev
     * @param $arrow
     */
    protected function getNextPrevLink(array $prev, $arrow): HTMLTag
	{
		if (ifsetor($prev['singleLink'])) {
			$content = new HTMLTag('a', [
				'href' => $prev['singleLink'],
				'title' => ifsetor($prev['name']),
			],
				//'&lt;',			// <
				//'&#x21E6;',			// ⇦
				//'&#25C0;',		// ◀
				//'&#x25C4;',		// ◄
				$arrow,
				true
			);
		} else {
			$content = new HTMLTag('span', [], $arrow, true);
		}

		return $content;
	}

	/**
     * @param $prev
     * @param $model OODBase
     * @param $next
     */
    protected function renderPrevNext(string $prev, $model, string $next): string
	{
		return $prev . ' ' . $model->getName() . ' ' . $next;
	}

}
