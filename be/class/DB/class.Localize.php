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

		if (!$this->noRender) {
			$content[] = $this->renderList();
		}
		$content = $this->encloseIn(__('Localize'), $content);
		//$this->index->addJQuery();
		$this->index->addJS('vendor/tuupola/jquery_jeditable/jquery.jeditable.js');
		$this->index->addJS(AutoLoad::getInstance()->nadlibFromDocRoot."js/Localize.js");
		$this->index->addCSS("nadlib/CSS/PaginationControl.less");
		return $content;
	}

	function renderList() {
		$keys = $this->getAllKeys();
		$table = $this->getTranslationTable($keys);

		$pager = new Pager();
		$pager->setNumberOfRecords(sizeof($table));
		$pager->detectCurrentPage();
		$table = array_slice($table, $pager->startingRecord, $pager->itemsPerPage, true);
		$content[] = $pager->renderPageSelectors($this->url);

		$s = new slTable($table, 'id="localize" width="100%" class="table _table-striped"', array(
			'key'             => array(
				'name'   => 'Key',
				'no_hsc' => true,
			),
			$this->from->lang => $this->from->lang,
			'de'              => array('name' => $this->de->lang, 'ano_hsc' => true),
			'ru'              => array('name' => $this->ru->lang, 'ano_hsc' => true),
			'page'            => array(
				'name'   => 'Page',
				'no_hsc' => true,
			),
			'del'             => array(
				'no_hsc' => true,
			)
		));

		$content[] = $s;
		$content[] = $pager->renderPageSelectors($this->url);
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
				'key' => '<a href="?c='.get_class($this).'&action=editOne&key='.urlencode($key).'">'.$key.'</a>',
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

				$row = $this->db->fetchOneSelectQuery($this->table, array('id' => $dbID));
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
		if (ifsetor($_COOKIE['untranslated'])) {
			foreach ($table as $i => $row) {
				foreach ($this->languages as $lang) {
					/** @var HTMLTag $ru */
					$ru = $row[$lang];
					if (ifsetor($_COOKIE['untranslated'][$lang]) && $ru->getContent()) {
						unset($table[$i]);
					}
				}
			}
		}
		return $table;
	}

	function saveAction() {
		$id = $this->request->getTrim('id');
		if ($id) {
			$row = $this->save($id, $this->request->getTrim('value'));
			$this->index->request->set('ajax', true);
			echo htmlspecialchars($row['text']);
		}
		exit();
	}

	/**
	 * @param $rel - can be int: ID of the already translated element
	 *                - can be string: Code of the original English element
	 * @param $save
	 * @return array
	 */
	function save($rel, $save) {
		//$save = $this->request->getTrim('save');
		//$rel = $this->request->getInt('rel');
		if (is_numeric($rel)) {
			$this->db->runUpdateQuery($this->table, array('text' => $save), array('id' => $rel));
			$row = $this->db->fetchOneSelectQuery($this->table, array('id' => $rel));
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
				$res = $this->db->runInsertQuery($this->table, array(
					'code' => $code,
					'lang' => $lang,
					'text' => $save,
				));
				$id = $this->db->lastInsertID($res);
				$row = $this->db->fetchOneSelectQuery($this->table, array('id' => $id));
			}
		}
		//echo $this->db->lastQuery;
		return array('text' => $save) + (is_array($row) ? $row : array());
	}

	function sidebar() {
		$f = new HTMLForm();
		$f->method('GET');
		$f->hidden('c', get_class($this));
		$f->input('search', $this->request->getTrim('search'), '', 'text', "span2");
		$f->submit('Search');
		$content[] = $f;

		$content[] = $this->encloseInAA($this->getUntranslatedCheckbox(), 'Untranslated');

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

	function getUntranslatedCheckbox() {
		foreach ($this->languages as $lang) {
			$checked = ifsetor($_COOKIE['untranslated'][$lang]) ? 'checked="checked"' : '';
			$content[] = '<label>
				<input type="checkbox" class="setCookie" name="untranslated[' . $lang . ']" value="1" '.$checked.'/>
				'.$lang.'
			</label><br />';
		}
		return $content;
	}

	function deleteDuplicatesAction() {
		$rows = $this->db->fetchSelectQuery($this->table, array(
			'lang' => 'en',
		), 'ORDER BY code, id');
		$prevCode = NULL;
		foreach ($rows as $row) {
			if ($prevCode == $row['code']) {
				echo 'Del: ', $row['code'], ' (id: ', $row['id'], ')<br />', "\n";
				$this->db->runDeleteQuery($this->table, array(
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
				$l->table = $this->table;
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

	function untranslatedAction() {
		// nothing, used in the filter
	}

	function editOneAction() {
		$key = $this->request->getTrimRequired('key');
		foreach ($this->languages as $lang) {
			$content[] = '<h2>'.$lang.'</h2>';
			/** @var LocalLangDB $langObj */
			$langObj = $this->$lang;
			$trans = $langObj->ll[$key];	// not T() because we don't need to replace %1
			$lines = sizeof(explode("\n", $trans));
			$id = $langObj->id($key);
			if (!$id) {
				$id = array($lang, $key);	// @see $this->save()
				$id = json_encode($id);
			}
			$f = new HTMLForm();
			$f->action('?c='.get_class($this));
			$f->hidden('action', 'saveOne');
			$f->input('id', $id);
			$f->textarea('value', $trans, array(
				'style' => 'width: 100%; height: '.(1+$lines).'em',
			));
			$f->submit(__('Save'));
			$content[] = $f->getContent();
			if (contains($trans, "\n")) {
				$content[] = '<div class="well">' . View::markdown($trans) . '</div>';
			}
		}
		$this->noRender = true;
		$content = $this->encloseInAA($content, $this->title = $key);
		return $content;
	}

	function saveOneAction() {
		$id = $this->request->getTrim('id');
		if ($id) {
			$row = $this->save($id, $this->request->getString('value'));	// HTML tags allowed
			$key = $row['code'];
			$this->request->redirect('?c='.get_class($this).'&action=editOne&key='.urlencode($key));
		}
	}

}