<?php

/**
 * Will read SQL file, parse it and compare with current DB proposing
 * ALTER TABLE statements
 */

define('TAB', "\t");
define('CR', "\r");
define('LF', "\n");
require_once('../vendor/typo3/class.t3lib_div.php');
require_once('../vendor/typo3/class.t3lib_sqlparser.php');
require_once('../vendor/typo3/class.t3lib_install_sql.php');
require_once('../vendor/typo3/class.t3lib_db.php');
require_once('../vendor/typo3/class.t3lib_utility_math.php');

class AlterDB extends AppControllerBE {

	/**
	 * @var t3lib_install_Sql
	 */
	protected $installerSQL;

	protected $update_statements = array();

	protected $file;

	function __construct() {
		parent::__construct();
		if (!$this->user || !$this->user->can('Admin')) {
			//throw new AccessDeniedException('Access Denied to '.__CLASS__);
			// access controlled by AlterDB::$public which is false
		}
		$this->file = $this->request->getTrim('file');
		$this->linkVars['file'] = $this->file;
	}

	function wrongApproach() {
		$query = "CREATE TABLE app_appointment (
  id int(11) NOT NULL auto_increment,
  ctime timestamp NOT NULL default CURRENT_TIMESTAMP,
  mtime timestamp NOT NULL default '2009-06-14 00:00:00',
  id_service int(11) NOT NULL,
  from datetime NOT NULL,
  till datetime NOT NULL,
  cancelled tinyint(1) NOT NULL default '0',
  id_client int(11) default NULL,
  id_user int(11) default NULL,
  comment text NOT NULL,
  PRIMARY KEY  (id),
  KEY id_client (id_client),
  KEY id_service (id_service),
  KEY id_user (id_user)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 AUTO_INCREMENT=31 ;
";
		/*$query = "CREATE TABLE tx_ehoi_ship (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumtext,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	name tinytext,
	image text,
	desctiption text,

	PRIMARY KEY (uid),
	KEY parent (pid)
);";*/
		$query = $this->getQueryFrom('some file.sql');
		$SQLparser = new t3lib_sqlparser();
		$parsedQuery = $SQLparser->parseSQL($query);
		//debug($parsedQuery);
		//debug(substr($query, 0, 1000));
	}

	function render() {
		$content = '';
		$content .= $this->getFileChoice();

		if ($this->file) {
			$query = $this->getQueryFrom($this->file);
			$diff = $this->getDiff($query);
			$update_statements = $this->installerSQL->getUpdateSuggestions($diff);
			//$content .= getDebug($update_statements);

			$this->update_statements = $update_statements;
			$this->performAction();

			$content .= $this->showDifferences($diff, $update_statements);
			//$content .= getDebug($diff);

			//$this->installerSql->performUpdateQueries($update_statements['add'],
		}
		return $content;
	}

	function getFileChoice() {
		$menu = array();
		foreach (new RecursiveDirectoryIterator('../../sql/') as $file) { /** @var $file SplFileInfo */
			//debug($file);
			if ($file->getFilename() != '.' && $file->getFilename() != '..') {
				$menu[$file->getPathname()] = $file->getFilename();
			}
		}
		foreach ($menu as $key => &$name) {
			$name = new HTMLTag('a', array(
				'href' => new URL('', array(
					'c' => __CLASS__,
					'file' => $key,
				))
			), $name);
		}
		$content = '<ul><li>'.implode('</li><li>', $menu).'</li></ul>';
		return $content;
	}

	function getQueryFrom($file) {
		$query = file_get_contents($file);
		$query = str_replace('`', '', $query);
		$query = preg_replace('/^--.*$/m', '', $query);
		$query = preg_replace('/^SET.*$/m', '', $query);
		$query = preg_replace('/^DROP.*$/m', '', $query);
		$query = preg_replace('/CONSTRAINT.*$/m', '', $query);
		return $query;
	}

	function getDiff($query) {
		$config = Config::getInstance();

		$GLOBALS['TYPO3_DB'] = $t3db = new t3lib_DB();
		$t3db->connectDB($config->db_server, $config->db_user, $config->db_password, $config->db_database);
		//debug($t3db);
		define('TYPO3_db', $config->db_database);

		$this->installerSQL = new t3lib_install_Sql();

		$FDfile = $this->installerSQL->getFieldDefinitions_fileContent($query);
		$FDfile = $this->filterDifferencesFile($FDfile);
		//$content .= getDebug($FDfile);

		$FDdb = $this->installerSQL->getFieldDefinitions_database();
		$FDdb = $this->filterDifferencesDB($FDdb);

		$diff = $this->installerSQL->getDatabaseExtra($FDfile, $FDdb);
		return $diff;
	}

	function filterDifferencesFile(array $FDfile) {
		foreach ($FDfile as $table => &$desc) {
			foreach ($desc['fields'] as $field => &$type) {
				$type = str_replace('AUTO_INCREMENT', 'auto_increment', $type);
				$type = str_replace('default NULL', '', $type);
				$type = str_replace('NOT NULL', '', $type);
				$type = str_replace('  ', ' ', $type);
				$type = trim($type);
			}
		}
		return $FDfile;
	}

	function filterDifferencesDB(array $FDdb) {
		foreach ($FDdb as $table => &$desc) {
			$info = $this->db->getTableColumns($table);
			foreach ($desc['fields'] as $field => &$type) {

				// it doesn't include info about NULL/NOT NULL
				$infoField = $info[$field];
				//$type .= $infoField['Null'] == 'NO' ? ' NOT NULL' : ' NULL';

				$type = str_replace('default NULL', '', $type);
				$type = str_replace("'CURRENT_TIMESTAMP'", 'CURRENT_TIMESTAMP', $type);
				$type = str_replace("on update", 'ON UPDATE', $type);
				$type = str_replace('  ', ' ', $type);
				$type = trim($type);
			}
		}
		return $FDdb;
	}

	function showDifferences(array $diff, array $update_statements) {
		$content = '';
		//debug($diff['extra'], $update_statements['add']);
		foreach ($diff['diff'] as $table => $desc) {
			$list = array();
			foreach ($desc['fields'] as $field => $type) {
				$current = $diff['diff_currentValues'][$table]['fields'][$field];
				if ($type != $current) {
					//debug($type, $current); exit();
					$list[] = array(
					'field' => $field,
					'file' => $type,
					'current' => $current,
					'sql' => $sql = $this->findStringWith($update_statements['change'], array($table, $field)),
					'do' => $this->makeRelLink('CHANGE', array(
						'action' => 'do',
						'file' => $this->file,
						'key' => 'change',
						'query' => md5($sql),
					)),
					);
				}
			}
			$content .= $this->showTable($list, $table);
		}
		foreach ($diff['extra'] as $table => $desc) {
			$list = array();
			if (is_array($desc['fields'])) foreach ($desc['fields'] as $field => $type) {
				$list[] = array(
					'field' => $field,
					'file' => $type,
					'sql' => $sql = $this->findStringWith($update_statements['add'], array($table, $field)),
					'do' => $this->makeRelLink('ADD', array(
						'action' => 'do',
						'file' => $this->file,
						'key' => 'add',
						'query' => md5($sql),
					)),
				);
			}
			$content .= $this->showTable($list, $table);
		}
		//debug($update_statements['add']);
		return $content;
	}

	function showTable(array $list, $table) {
		if ($list) {
			$s = new slTable($list, 'class="table"', array(
				'field' => 'field',
				'file' => 'file',
				'current' => 'current',
				'sql' => 'sql',
				'do' => array(
					'name' => 'do',
					'no_hsc' => true,
				),
			));
			$content = $this->encloseInAA($s, $table, 'h2');
		}
		return $content;
	}

	function findStringWith(array $options, array $with) {
		foreach ($options as $el) {
			$false = false;
			foreach ($with as $search) {
				if (strpos($el, $search) === FALSE) {
					$false = true;
					continue;
				}
			}
			if (!$false) {
				return $el;
			}
		}
	}

	function doAction() {
		$md5 = $this->request->getTrim('query');
		$key = $this->request->getTrim('key');
		$query = $this->update_statements[$key][$md5];
		//debug($md5, $query);
		if ($query) {
			$this->db->perform($query);
			$this->request->redirect($this->makeRelURL());
		}
	}

}
