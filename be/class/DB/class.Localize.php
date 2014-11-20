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
	 * @var LocalLangDB
	 */
	protected $from;

	/**
	 * @var LocalLangDB
	 */
	protected $en, $de, $ru;

	public $title = 'Localize';

	public $table = 'interface';

	var $languages = array(
		'en', 'de', 'ru',
	);

	/**
	 * @var URL
	 */
	var $url;

	function __construct() {
		parent::__construct();

		$this->from = new LocalLangDB('en');
		$this->from->indicateUntranslated = false;
		$this->from->saveMissingMessages = false;
		$this->from->init();
		$this->en = $this->from;

		$this->de = new LocalLangDB('de');
		$this->de->indicateUntranslated = false;
		$this->de->saveMissingMessages = false;
		$this->de->init();

		$this->ru = new LocalLangDB('ru');
		$this->ru->indicateUntranslated = false;
		$this->ru->saveMissingMessages = false;
		$this->ru->init();
		$this->url = new URL('?c='.get_class($this));
	}

	function render() {
		$content[] = $this->performAction();
		/*$content .= '<div style="float: right;">'.$this->makeLink('Import missing.txt', array(
			'c' => 'ImportMissing',
		)).'</div>';*/

		$keys = $this->getAllKeys();

		$pager = new Pager();
		$pager->setNumberOfRecords(sizeof($keys));
		$pager->detectCurrentPage();
		$keys = array_slice($keys, $pager->startingRecord, $pager->itemsPerPage, true);
		$content[] = $pager->renderPageSelectors($this->url);

		$table = $this->getTranslationTable($keys);
		$s = new slTable($table, 'id="localize" width="100%" class="table _table-striped"', array(
			'key' => 'Key',
			$this->from->lang => $this->from->lang,
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

		$content[] = $s;
		$content[] = $pager->renderPageSelectors($this->url);
		$content = $this->encloseIn(__('Localize'), $content);
		//$this->index->addJQuery();
		$this->index->addJS('js/vendor/tuupola/jquery_jeditable/jquery.jeditable.js');
		$this->index->addJS("nadlib/js/Localize.js");
		$this->index->addCSS("nadlib/CSS/PaginationControl.less");
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
		sort($keys);
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
			foreach ($this->languages as $lang) {
				$lobj = $this->$lang;
				/** @var $lobj LocalLangDB */
				$dbID = $lobj->id($key);

				$row = $this->db->fetchOneSelectQuery('interface', array('id' => $dbID));
				if (ifsetor($row['deleted'])) {
					$colorCode = 'muted';
				} else {
					$colorCode = ifsetor($this->from->ll[$key]) == ifsetor($lobj->ll[$key])
						? 'red bg-danger'
						: 'green bg-success';
				}

				$table[$key][$lang] = new HTMLTag('td', array(
					'id' => $dbID ? $dbID : json_encode(array($lobj->lang, $key)),
					'lang' => $lobj->lang,
					'class' => 'inlineEdit '.$colorCode,
				),
					//isset($lobj->ll[$key]) ? $lobj->M($key) : '-');
					ifsetor($lobj->ll[$key], ''));

				// Page
				$row = $lobj->getRow($dbID);
				if ($row['page']) {
					$url = new URL($row['page']);
					$colorPage = strpos($url->getPath(), 'nadlib/be') !== false
						? 'be'
						: 'fe';
					$table[$key]['page'] = ifsetor($table[$key]['page']);	// can be multiple
					$table[$key]['page'] .= new HTMLTag('a', array(
							'href' => $row['page'],
							'class' => $colorPage,
						), $url->getParam('c') ?: urldecode(basename($url->getPath()))).' ';
				}

			}
			// Del
			$table[$key]['del'] = new HTMLTag('a', array(
				'href' => new URL('', array(
					'c' => get_class($this),
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
		$content[] = $f;

		$content[] = '<hr />';
		$content[] = $this->getActionButton('Delete Duplicates', 'deleteDuplicates');

		$content[] = '<hr />';
		$content[] = $this->getActionButton('Download JSON', 'downloadJSON', NULL, array(), 'btn btn-info');
		$u = new Uploader(array('json'));
		$f = $u->getUploadForm('file');
		$f->hidden('action', 'importJSON');
		$content[] = $f;

		$content[] = '<hr />';
		$desc = array(
			'code' => array(
				'label' => 'Code',
			));
		foreach ($this->languages as $lang) {
			$desc[$lang] = array(
				'label' => $lang,
			);
		}
		$desc['action'] = array(
			'type' => 'hidden',
			'value' => 'addNew',
		);
		$desc['submit'] = array(
			'type' => 'submit',
			'value' => __('Add new translation'),
		);
		$f = new HTMLFormTable();
		$f->showForm($desc);
		$content[] = $f;

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
		$code = $this->request->getTrimRequired('code');
		$columns = $this->db->getTableColumns($this->table);
		if (ifsetor($columns['deleted'])) {
			$this->db->runUpdateQuery($this->table, array(
				'deleted' => true,
			), array(
				'code' => $code,
			));
		} else {
			$l = $this->config->getLocalLangModel();
			$l->delete(array(
				'code' => $code,
			));
			//debug($l->lastQuery);
		}
		$url = new URL();
		$url->unsetParam('action');
		$url->unsetParam('code');
		//debug($url.'');
		$this->request->redirect($url);
	}

	function downloadJSONAction() {
		$keys = $this->getAllKeys();
		$transTab = $this->getTranslationTable($keys);
		foreach ($transTab as &$row) {
			unset($row['page']);
			unset($row['del']);
			$row['en'] = strip_tags($row['en']);
			$row['de'] = strip_tags($row['de']);
			$row['ru'] = strip_tags($row['ru']);
		}
		$this->request->forceDownload('application/json', $this->index->appName.'-Localization.json');
		echo json_encode($transTab, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		exit();
	}

	function importJSONAction() {
		$content = [];
		$fileData = json_decode(file_get_contents($_FILES['file']['tmp_name']), true);
		//debug(sizeof($fileData), first($fileData));
		foreach ($fileData as $row) {
			$key = $row['key'];
			foreach ($row as $lang => $value) {
				if ($lang == 'key') continue;
				$l = new LocalLangModel();
				$l->table = 'interface';
				$l->findInDB(array(
					'code' => $key,
					'lang' => $lang,
				), '', false);
				if ($l->id) {
					if ($l->getValue() != $value) {
						$content[] = '<p class="text-danger">Import skipped for ['.$key.'/'.$lang.']: "'.$value.'" exists as "'.$l->getValue().'"</p>';
					}
				} else {
					if ($value && $value != 'nothing') {
						$content[] = '<p class="text-info">Importing for [' . $key . '/' . $lang . ']: "' . $value . '"' . BR;
						$l->insert(array(
							'code' => $key,
							'lang' => $lang,
							'text' => $value,
						));
					}
				}
			}
		}
		return $content;
	}

	function addNewAction() {
		$content = array();
		$code = $this->request->getTrimRequired('code');
		foreach ($this->languages as $lang) {
			$text = $this->request->getTrim($lang);
			if ($text) {
				$lm = $this->config->getLocalLangModel();
				$lm->insert(array(
					'code' => $code,
					'lang' => $lang,
					'text' => $text,
				));
				$content[] = '<div class="message">Added '.$text.' ('.$lang.')</div>';
			}
		}
		return $content;
	}

}
