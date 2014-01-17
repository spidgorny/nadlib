<?php

/**
 * Class Localize
 *
 * Hint: merge two localization tables together:
 * insert into app_interface (code, lang, text)
select app_interface_import.code, app_interface_import.lang, app_interface_import.text
from app_interface_import
left outer join app_interface
on (app_interface.code = app_interface_import.code
AND app_interface.lang = app_interface.lang)
WHERE app_interface.text IS NULL
 */

class Localize extends AppControllerBE {
	/**
	 * @var LocalLang
	 */
	protected $from;

	/**
	 * @var LocalLang
	 */
	protected $de, $ru;

	public $title = 'Localize';

	public $table = 'interface';

	function __construct() {
		parent::__construct();
		$this->from = new LocalLangDB('en');
		$this->from->indicateUntranslated = false;
		$this->from->init();
		$this->de = new LocalLangDB('de');
		$this->de->indicateUntranslated = false;
		$this->de->init();
		$this->ru = new LocalLangDB('ru');
		$this->ru->indicateUntranslated = false;
		$this->ru->init();
		$this->url = new URL('?c=Localize');
	}

	function render() {
		$content = $this->performAction();
		/*$content .= '<div style="float: right;">'.$this->makeLink('Import missing.txt', array(
			'c' => 'ImportMissing',
		)).'</div>';*/

		$keys = $this->getAllKeys();

		$pager = new Pager();
		$pager->setNumberOfRecords(sizeof($keys));
		$pager->detectCurrentPage();
		$keys = array_slice($keys, $pager->startingRecord, $pager->itemsPerPage, true);
		$content .= $pager->renderPageSelectors($this->url);

		$table = $this->getTranslationTable($keys);
		$s = new slTable($table, 'id="localize" width="100%" class="table table-striped"', array(
			'key' => 'Key',
			'from' => $this->from->lang,
			'de' => array('name' => $this->de->lang, 'ano_hsc' => true),
			'ru' => array('name' => $this->ru->lang, 'ano_hsc' => true),
			'page' => array(
				'name' => 'Page',
				'no_hsc' => true,
			),
			'del' => array(
				'no_hsc' => true,
			)
		));

		$content .= $s;
		$content .= $pager->renderPageSelectors($this->url);
		$content = $this->encloseIn(__('Localize'), $content);
		//$this->index->addJQuery();
		$this->index->addJS('../js/jquery.jeditable.mini.js');
		$this->index->addJS("js/Localize.js");
		return $content;
	}

	function getAllKeys() {
		$all = $this->from->getMessages();
		$all += $this->de->getMessages();
		$all += $this->ru->getMessages();
		if (($search = strtolower($this->request->getTrim('search')))) {
			foreach ($all as $key => $trans) {
				if (strpos(strtolower($trans), $search) === FALSE &&
					strpos(strtolower($key)  , $search) === FALSE) {
					unset($all[$key]);
				}
			}
		}
		$keys = array_keys($all);
		return $keys;
	}

	function getTranslationTable(array $keys) {
		$table = array();
		foreach ($keys as $key) {
			$table[$key] = array(
				'key' => $key,
				/*					'from' => new HTMLTag('td', array(
										'id' => $this->from->id($key),
										'lang' => $this->from->lang,
										'class' => 'inlineEdit',
									), $this->from->M($key)),
				*/				);
			foreach (array('from', 'de', 'ru') as $lang) {
				$lobj = $this->$lang;
				/** @var $lobj LocalLangDB */
				$dbID = $lobj->id($key);

				$row = $this->db->fetchOneSelectQuery('interface', array('id' => $dbID));
				if ($row['deleted']) {
					$colorCode = 'muted';
				} else {
					$colorCode = $this->from->M($key) == $lobj->M($key)
						? 'red'
						: 'green';
				}

				$table[$key][$lang] = new HTMLTag('td', array(
						'id' => $dbID ? $dbID : json_encode(array($lobj->lang, $key)),
					'lang' => $lobj->lang,
					'class' => 'inlineEdit '.$colorCode,
				), isset($lobj->lang[$key]) ? $lobj->M($key) : '-');

				// Page
				$row = $lobj->getRow($dbID);
				if ($row['page']) {
					$url = new URL($row['page']);
					$colorPage = strpos($url->getPath(), 'nadlib/be') !== false
						? 'be'
						: 'fe';
					$table[$key]['page'] .= new HTMLTag('a', array(
							'href' => $row['page'],
							'class' => $colorPage,
						), $url->getParam('c') ?: basename($url->getPath())).' ';
				}

			}
			// Del
			$table[$key]['del'] .= new HTMLTag('a', array(
				'href' => new URL('', array(
					'c' => 'Localize',
					'action' => 'deleteRow',
					'code' => $key,
				))
			), '&times;', true);
		}
		return $table;
	}

	function saveAction() {
		$id = $this->request->getTrim('id');
		if ($id) {
			$this->save($id, $this->request->getTrim('value'));
			$this->index->request->set('ajax', true);
		}
		exit();
	}

	/**
	 * @param $rel	- can be int: ID of the already translated element
	 * 				- can be string: Code of the original English element
	 * @param $save
	 */
	function save($rel, $save) {
		//$save = $this->request->getTrim('save');
		//$rel = $this->request->getInt('rel');
		if (is_numeric($rel)) {
			$this->db->runUpdateQuery($this->table, array('text' => $save), array('id' => $rel));
		} else {
			//$code = $this->request->getTrim('code');
			list($lang, $code) = json_decode($rel, 1);
			$res = $this->db->runSelectQuery($this->table, array(
				'code' => $code,
				'lang' => $lang,
			));
			$row = $this->db->fetchAssoc($res);
			if (($rel = $row['id'])) {
				$this->db->runUpdateQuery($this->table, array('text' => $save), array('id' => $rel));
			} else {
				$this->db->runInsertQuery($this->table, array(
					'code' => $code,
					'lang' => $lang,
					'text' => $save,
				));
			}
		}
		//echo $this->db->lastQuery;
		echo htmlspecialchars($save);
	}

	function sidebar() {
		$f = new HTMLForm();
		$f->method('GET');
		$f->hidden('c', get_class($this));
		$f->input('search', $this->request->getTrim('search'), '', 'text', "span2");
		$f->submit('Search');
		$content = $f;

		$content .= $this->getActionButton('Delete Duplicates', 'deleteDuplicates');

		return $content;
	}

	function deleteDuplicatesAction() {
		$rows = $this->db->fetchSelectQuery('interface', array(
			'lang' => 'en',
		), 'ORDER BY code, id');
		$prevCode = NULL;
		foreach ($rows as $row) {
			if ($prevCode == $row['code']) {
				echo 'Del: ', $row['code'], ' (id: ', $row['id'], ')<br />', "\n";
				$this->db->runDeleteQuery('interface', array(
					'id' => $row['id'],
				));
			}
			$prevCode = $row['code'];
		}
	}

	function deleteRowAction() {
		//debug($_REQUEST);
		$this->db->runUpdateQuery('interface', array(
			'deleted' => true,
		), array(
			'code' => $this->request->getTrimRequired('code'),
		));
	}

}
