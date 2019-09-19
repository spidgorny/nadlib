<?php

class TYPO3Content extends OODBase
{
	public $table = 'tt_content';
	public $idField = 'uid';
	public $titleColumn = 'header';

	function __toString()
	{
		return '<div id="content_' . $this->id . '">' .
			'<h2>' . $this->data[$this->titleColumn] . '</h2>' .
			$this->data['bodytext'] .
			'</div>';
	}

	function insert(array $data)
	{
		$data['tstamp'] = time();
		$data['crdate'] = time();
		return parent::insert($data);
	}

	function update(array $data)
	{
		$data['tstamp'] = time();
		return parent::update($data);
	}

}
