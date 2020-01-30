<?php

class SQLCountQuery
{

	/**
	 * @var CollectionQuery
	 */
	public $cq;

	public function __construct(CollectionQuery $cq)
	{
		$this->cq = $cq;
	}

	public function getCount()
	{
		$queryWithLimit = $this->cq->getQueryWithLimit();
//				debug(__METHOD__, $queryWithLimit.'');
		if (contains($queryWithLimit, 'LIMIT')) {    // no pager - no limit
			// we do not preProcessData()
			// because it's irrelevant for the count
			// but can make the processing too slow
			// like in QueueEPES
			$data = $this->cq->retrieveData();
			// will set the count
			// actually it doesn't
			$count = count($data);
		} elseif ($queryWithLimit instanceof SQLSelectQuery) {
			$count = $this->cq->db->getCount($queryWithLimit);
		} else {
			// this is the same query as $this->retrieveData() !
			$query = $this->cq->getQuery();
			//debug('performing', $query);
			if (is_string($query)) {
				xdebug_break();
			}
			$res = $query->perform();
			$count = $this->cq->db->numRows($res);
		}
		return $count;
	}
}
