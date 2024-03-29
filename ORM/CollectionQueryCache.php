<?php

class CollectionQueryCache extends CollectionQuery
{

	/**
	 * Wrapper for retrieveDataFromDB() to store/retrieve data from the cache file
	 * @param bool $allowMerge
	 * @throws Exception
	 */
	public function retrieveDataFromCache($allowMerge = false)
	{
		if (!$this->data) {                                                    // memory cache
			$this->query = $this->getQuery();
			if ($this->doCache) {
				// this query is intentionally without
				if ($this->pager) {
					$this->pager->setNumberOfRecords(PHP_INT_MAX);
					$this->pager->detectCurrentPage();
					//$this->pager->debug();
				}
				$fc = new MemcacheOne($this->query . '.' . $this->pager->currentPage, 60 * 60);            // 1h
				$this->log('key: ' . substr(basename($fc->map()), 0, 7));
				$cached = $fc->getValue();                                    // with limit as usual
				if ($cached && count($cached) === 2) {
					list($this->count, $this->data) = $cached;
					if ($this->pager) {
						$this->pager->setNumberOfRecords($this->count);
						$this->pager->detectCurrentPage();
					}
					$this->log('found in cache, age: ' . $fc->getAge());
				} else {
					$this->retrieveData($allowMerge);    // getQueryWithLimit() inside
					$fc->set([$this->count, $this->data]);
					$this->log('no cache, retrieve, store');
				}
			} else {
				$this->retrieveData($allowMerge);
			}
			if ($_REQUEST['d']) {
				//debug($cacheFile = $fc->map($this->query), $action, $this->count, filesize($cacheFile));
			}
		}
	}

}
