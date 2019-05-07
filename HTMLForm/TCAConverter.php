<?php

class TCAConverter
{

	/**
	 * @var string
	 */
	public $table;

	/**
	 * @var tx_ninpbl_pi1
	 */
	public $pi1;

	public $skipFields = array('hidden');

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $t3db;

	function __construct(\TYPO3\CMS\Frontend\Plugin\AbstractPlugin $pi1)
	{
		$this->pi1 = $pi1;
		$this->t3db = $GLOBALS['TYPO3_DB'];
	}

	function convertTCA(array $fields)
	{
		$labels = $this->array_column($fields, 'label');
		$this->pi1->loadLabels($labels);

		$desc = array();
		foreach ($fields as $field => $config) {
			if (!in_array($field, $this->skipFields)) {
				//t3lib_div::debug($config['config']);
				$desc[$field] = $this->convertTCAtoDesc($config['config']);
				$pair = explode(':', $config['label']);
				$llIndex = end($pair);
				$desc[$field]['label'] = $this->pi1->pi_getLL($llIndex, $config['label']);
				$desc[$field]['optional'] = $config['exclude'];
			}
		}
		return $desc;
	}

	function convertTCAtoDesc(array $config)
	{
		$match = array(
			'radio' => 'radioset',
			'text' => 'textarea',
		);
		$desc['type'] = $match[$config['type']] ? $match[$config['type']] : $config['type'];
		$func = 'convertTCA_' . $config['type'];
		$desc = $this->$func($desc, $config);
		return $desc;
	}

	function convertTCA_input(array $desc, array $config)
	{
		$desc['size'] = $config['size'];
		//$desc['type'] = 'text';
		return $desc;
	}

	function convertTCA_select(array $desc, array $config)
	{
		if ($config['foreign_table']) {
			//t3lib_div::debug($config);
			$name = $GLOBALS['TCA'][$config['foreign_table']]['ctrl']['label'];
			//t3lib_div::debug($GLOBALS['TCA'][$config['foreign_table']]['ctrl']);
			$where = '1=1 ' . $this->pi1->cObj->enableFields($config['foreign_table']) . ' ' . $config['foreign_table_where'];
			$where = str_replace('###CURRENT_PID###', $this->pi1->tsfe->id, $where);
			$query = $this->t3db->SELECTquery('uid, ' . $name, $config['foreign_table'], $where); // may contain ONLY ORDER BY!
			//debug($query);
			$res = $this->t3db->sql_query($query);
			$desc['options'] = $this->fetchAll($res);
			//t3lib_div::debug($GLOBALS['TYPO3_DB']->sql_num_rows($res));
			//t3lib_div::debug($desc['options']);
			$desc['options'] = $this->array_column($desc['options'], $name);
			//t3lib_div::debug($desc['options']);
		} else {
			$desc['options'] = $this->convertTYPO3items2options($config['items']);
		}
		$desc['size'] = $config['size'];
		if ($config['maxitems'] > 1) {
			$desc['more'] = 'multiple="multiple"';
			$desc['multiple'] = 1;
		}
		return $desc;
	}

	function convertTCA_group(array $desc, array $config)
	{
		return $desc;
	}

	function convertTCA_text(array $desc, array $config)
	{
		$desc['rows'] = $config['rows'];
		$desc['cols'] = $config['cols'];
		return $desc;
	}

	function convertTCA_radio(array $desc, array $config)
	{
		$desc['options'] = $this->convertTYPO3items2options($config['items']);
		return $desc;
	}

	function convertTCA_check(array $desc, array $config)
	{
		$desc['more'] = 'value="1"'; // "on" is not ok with MySQL integer fields
		return $desc;
	}

	function convertTYPO3items2options(array $items)
	{
		$labels = $this->array_column($items, '0');
		$this->pi1->loadLabels($labels);

		$options = array();
		foreach ($items as $o) {
			$pair = explode(':', $o[0]);
			$options[$o[1]] = $this->pi1->pi_getLL(end($pair), $o[0]);
		}
		return $options;
	}

	/**
	 * IDalized with 'uid'
	 * @param <type> $res
	 * @return array
	 */
	static function fetchAll($res)
	{
		$rows = array();
		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $db */
		$db = $GLOBALS['TYPO3_DB'];
		while (($row = $db->sql_fetch_assoc($res)) !== FALSE) {
			$rows[$row['uid']] = $row;
		}
		return $rows;
	}

	function array_column(array $table, $colName)
	{
		foreach ($table as &$row) {
			$row = $row[$colName];
		}
		return $table;
	}

}
