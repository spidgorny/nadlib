<?php

class ElasticaQuery {

	/**
	 * @var Elastica\Client
	 */
	var $client;

	/**
	 * @var string
	 */
	var $indexName;

	function __construct(DIContainer $di) {
		$this->client = $di->client;
		$this->indexName = $di->indexName;
	}

	function fetchSelectQuery($type, array $where) {
		//$elasticaQueryString  = new \Elastica\Query\QueryString();
		//$elasticaQueryString->setDefaultOperator('AND');
		//$elasticaQueryString->setQuery('Kyiv');
		$elasticaQueryString = new \Elastica\Query\MatchAll();

		/*$term1 = new Elastica\Filter\Term();
		$term1->setTerm('City.Name', 'kyiv');

		$term2 = new Elastica\Filter\Term();
		$term2->setTerm('CountryLanguage.IsOfficial', 't');

		$range = new Elastica\Filter\Range('Population', array(
			'from' => '4',
			'to' => '8000000000',
		));
		*/
		$elasticaFilterAnd    = new \Elastica\Filter\BoolAnd();
		//$elasticaFilterAnd->addFilter($range);
		//$elasticaFilterAnd->addFilter($term1);
		//$elasticaFilterAnd->addFilter($term2);
		foreach ($where as $field => $condition) {
			$elasticaCondition = $this->switchCondition($field, $condition);
			if ($elasticaCondition) {
				if ($elasticaCondition instanceof \Elastica\Query\AbstractQuery) {
					$elasticaQueryString = $elasticaCondition;
				} else {
					$elasticaFilterAnd->addFilter($elasticaCondition);
				}
			} else {
				// throw up
			}
		}

		$filteredQuery = new Elastica\Query\Filtered(
			$elasticaQueryString,
			$elasticaFilterAnd
		);

		$elasticaQuery        = new \Elastica\Query();
		//$elasticaQuery->setQuery($elasticaQueryString);
		//$elasticaQuery->setFilter($elasticaFilterAnd);
		$elasticaQuery->setQuery($filteredQuery);

		$elasticaIndex = $this->client->getIndex($this->indexName);//->getType($type);
		$resultSet    = $elasticaIndex->search($elasticaQuery);
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
	 * @return \Elastica\Query\AbstractQuery
	 */
	function switchCondition($field, $condition) {
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
				$res = new Elastica\Filter\Range($field, array(
					'from' => $condition->from,
					'to' => $condition->till,
				));
				break;
			case 'SQLLike':
				$res = new \Elastica\Query\QueryString();
				$res->setDefaultOperator('AND');
				$res->setQuery($condition->string);
				break;
		}
		return $res;
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
