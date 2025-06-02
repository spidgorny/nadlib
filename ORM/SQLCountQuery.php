<?php

class SQLCountQuery
{

	/**
	 * @var CollectionQuery
	 */
	public $cq;

	protected ?\DBInterface $db;

	public function __construct(CollectionQuery $cq, ?DBInterface $db = null)
	{
		$this->cq = $cq;
		$this->db = $db;
	}

	public function __toString(): string
	{
		$query = new SQLSelectQuery($this->db,
			new SQLSelect('count(*) AS count'),
			$this->cq->getQueryWithLimit()
		);

		return $query . '';
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
				throw new RuntimeException(__METHOD__);
			}

			$res = $query->perform();
			$count = $this->cq->db->numRows($res);
		}

		return $count;
	}

	public function alternative(): void
	{
		$countCollection = new Collection(null, [], '', $this->db);
		$countCollection->select = 'count(*) as id';
//		$countCollection->where = $this->cq->where;
		$countCollection->orderBy = '';
		$countCollection->orderBy = str_replace('LIMIT 50', '', $countCollection->orderBy);
		$countCollection->allowMerge = true;
		// count() can = 0
		$firstRow = $this->cq->db->fetchAssoc($countCollection->getQueryWithLimit() . '');
		first($firstRow);
	}
}
