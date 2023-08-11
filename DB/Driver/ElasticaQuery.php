<?php

use Elastica\Filter\BoolAnd;
use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\Query\MatchAll;
use Elastica\Query\QueryString;
use Elastica\Query\Term;

class ElasticaQuery
{

	/**
	 * @var Elastica\Client
	 */
	var $client;

	/**
	 * @var string
	 */
	var $indexName;

	/**
	 * @var Elastica\Query\MatchAll
	 */
	var $queryString;

	/**
	 * @var Elastica\Filter\BoolAnd
	 */
	var $elasticaFilterAnd;

	/**
	 * @var Elastica\Query\Filtered
	 */
	var $filteredQuery;

	/**
	 * @var Elastica\Query
	 */
	var $elasticaQuery;

	/**
	 * @var Pager
	 */
	var $pager;

	/**
	 * @var Elastica\Facet\Terms
	 */
	var $facets;

	function __construct(DIContainer $di)
	{
		$this->client = $di->client;
		$this->indexName = $di->indexName;

		$this->queryString = new MatchAll();

		$this->elasticaFilterAnd = new BoolAnd();

		$this->filteredQuery = new Elastica\Query\Filtered(
			$this->queryString,
			$this->elasticaFilterAnd
		);

		$this->elasticaQuery = new Query();
	}

	function setOrderBy($orderBy)
	{
		foreach ($orderBy as $by => $ascDesc) {
			$this->elasticaQuery->setSort([
				$by => ['order' => $ascDesc ?: 'asc'],
			]);
		}
	}

	function setPager(Pager $pager)
	{
		$this->pager = $pager;
		$this->elasticaQuery->setFrom($pager->getStart());
		$this->elasticaQuery->setSize($pager->getLimit());
	}

	function setWhere(array $where)
	{
		foreach ($where as $field => $condition) {
			$elasticaCondition = $this->switchCondition($field, $condition);
			if ($elasticaCondition) {
				if ($elasticaCondition instanceof AbstractQuery) {
					$elasticaQueryString = $elasticaCondition;
				} else {
					$this->elasticaFilterAnd->addFilter($elasticaCondition);
				}
			} else {
				// throw up
			}
		}
	}

	function fetchSelectQuery($type)
	{
		/** @var Elastica\Query\Filtered $fq */
		$this->filteredQuery->setQuery($this->queryString);
		$this->filteredQuery->setFilter($this->elasticaFilterAnd);
		$this->elasticaQuery->setQuery($this->filteredQuery);
		if ($this->facets) {
			$this->elasticaQuery->addFacet($this->facets);
		}

		$search = new Elastica\Search($this->client);
		$search->addIndex($this->indexName);
		$search->addType($type);
		$resultSet = $search->search($this->elasticaQuery);
		if ($this->pager) {
			$this->pager->setNumberOfRecords($resultSet->getTotalHits());
		}
		//debug('getLastRequest', $this->client->getLastRequest());
		//debug('getLastResponse', $this->client->getLastResponse());
		//debug('query', $this->client->getLastRequest()->getData());
		//$this->index->content[__METHOD__] = '<pre>'.json_encode($this->client->getLastRequest()->getData(), JSON_PRETTY_PRINT).'</pre>';
		//$content[] = $this->client->getLastResponse()->getData();
		return $resultSet;
	}

	/**
	 * @param string $field
	 * @param mixed $condition
	 * @return AbstractQuery
	 */
	function switchCondition($field, $condition)
	{
		$type = is_object($condition)
			? get_class($condition)
			: 'SQLWhereEqual';
		$field = str_replace('`', '', $field);
		$field = trim($field);
		//debug($field, $type, $condition);
		switch ($type) {
			case 'SQLWhereEqual':
				$res = new Elastica\Filter\Term();
				$res->setTerm($field, mb_strtolower($condition));
				break;
			case 'SQLRange':
				$from = $this->getString($condition->from);
				$till = $this->getString($condition->till);
				$res = new Elastica\Filter\Range($field, [
					'from' => $from,
					'to' => $till,
				]);
				break;
			case 'SQLBetween':
				$start = $this->getString($condition->start);
				$end = $this->getString($condition->end);
				$res = new Elastica\Filter\Range($field, [
					'from' => $start,
					'to' => $end,
				]);
				break;
			case 'SQLLike':
				$res = new QueryString();
				$res->setDefaultOperator('AND');
				$res->setQuery($condition->string);
				break;
		}
		return $res;
	}

	function getString($obj)
	{
		if (is_object($obj)) {
			//$obj->injectQB($this);
			$obj = $obj . '';
		}
		return $obj;
	}

	function quoteKey($a)
	{
		return $a;
	}

	function getByID($type, $id)
	{
		//$elasticaTerm  = new \Elastica\Filter\Term();
		//$elasticaTerm->setTerm('_id', $id);
		$elasticaQuery = new Term();
		$elasticaQuery->setTerm('_id', $id);
		$search = new Elastica\Search($this->client);
		$search->addIndex($this->indexName);
		$search->addType($type);
		$resultSet = $search->search($elasticaQuery);
		$aResults = $resultSet->getResults();
		//debug($search->getClient()->getLastRequest()->getData());
		//debug($search->getClient()->getLastResponse()->getData());
		if ($aResults) {
			$first = first($aResults);
			$row = $first->getData();
		}
		//debug($row); exit();
		return $row;
	}

}

/*
{
  "query": {
    "bool": {
      "must": [
        {
          "query_string": {
            "default_field": "_all",
            "query": "Kyiv"
          }
        },
        {
          "term": {
            "City_Name": "Kyiv",
            "Country_Code": "UKR"
          }
        }
      ]
    },
    "filtered": {
      "filter": {
        "term": {
          "City_Name": "Kyiv",
          "Country_Code": "UKR"
        }
      }
    }
  },
  "from": 0,
  "size": 10,
  "sort": [],
  "facets": {}
}
 */
