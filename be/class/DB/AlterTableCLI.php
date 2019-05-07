<?php

class AlterTableCLI extends AlterTableHYBH
{

	function __construct()
	{
		if (!Request::isCLI()) {
			die(__CLASS__ . ' can only be called by admin');
		}

		//Request::getInstance()->set('id', 1);	// Slawa
		//$fake = new FakeLogin();
		//$fake->loginAction();
		$user = new User(1);
		$user->saveLogin();

		Config::getInstance()->user = $user;

		parent::__construct();
	}

	function render()
	{
		echo 'DB Class: ', gettype2($this->db), BR;
		echo 'DB Scheme: ', $this->db->getScheme(), BR;
		$action = $this->request->getTrim('action');
		$action = $action ?: ifsetor($_SERVER['argv'][2]);
		echo 'Action: ', $action, BR;
		return $this->performAction();
	}

	function saveAction()
	{
		echo 'File: ', $this->jsonFile, BR;
		echo 'Size: ', filesize($this->jsonFile), BR;
		$this->saveStructAction();
		clearstatcache();
		echo 'Size: ', filesize($this->jsonFile), BR;
	}

	function listAction()
	{
		/** @var UL $ul */
		$ul = $this->listFiles()[0];
		$ul->cli();
	}

	function tryAction($filename)
	{
		//$filename = str_replace('sql\\', '', $filename);
		echo 'File: ', $filename, BR;
		$this->jsonFile = $filename;

		//$_SERVER['argv'] = [];
		//$content = parent::render();
		//$content = $this->s($content);
		//echo $content;

		if ($this->jsonFile && is_readable($this->jsonFile)) {
			$struct = file_get_contents($this->jsonFile);
			$struct = json_decode($struct, true);
			if (!$struct) {
				throw new Exception('JSON file reading error');
			}

			$this->setHandler();
			echo 'Handler: ', get_class($this->handler), BR;
			$local = $this->getDBStruct();
			foreach ($struct as $table => $desc) {
				echo BR, '*** Table: ' . $table . ' ***' . BR;
				if (isset($local[$table])) {
					$indexCompare = $this->compareTables($table, $desc['columns'], $local[$table]['columns']);
				} else {
					$createQuery = $this->handler->getCreateQuery($table, $desc['columns']);
					$indexCompare = [array(
						'action' => new HTMLTag('td', array(
							'colspan' => 10,
							'class' => 'sql',
						), $this->click($table, $createQuery)
						),
					)];
				}
				$this->filterChanges($table, $indexCompare);
			}
		} else {
			echo 'Choose file on the left', BR;
			$this->listAction();
		}
	}

	private function filterChanges($table, array $indexCompare)
	{
		$content = [];
		foreach ($indexCompare as $row) {
			if (ifsetor($row['same']) != 'same') {
				$sql = $row['action']->content->content;
				echo $sql, str_endsWith($sql, ';') ? '' : ';';
				if (ifsetor($row['fromDB']) . '') {
					echo ' /* ', $row['fromDB'], ' */';
				}
				echo BR;
			}
		}
		return $content;
	}

}
