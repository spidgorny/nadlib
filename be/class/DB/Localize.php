<?php

use spidgorny\nadlib\HTTP\URL;

/**
 * Class Localize
 *
 * Hint: merge two localization tables together:
 * insert into app_interface (code, lang, text)
 * select app_interface_import.code, app_interface_import.lang, app_interface_import.text
 * from app_interface_import
 * left outer join app_interface
 * on (app_interface.code = app_interface_import.code
 * AND app_interface.lang = app_interface.lang)
 * WHERE app_interface.text IS NULL
 */
class Localize extends AppControllerBE
{

	public $title = 'Localize';

	public $table = 'interface';

	public $languages = [
		'en', 'de', 'ru',
	];

	/**
	 * @var URL
	 */
	public $url;

	/**
	 * Cached
	 * @var array
	 */
	public $allKeys = [];

	protected LocalLangDB $from;

	protected LocalLangDB $en;

	protected LocalLangDB $de;

	protected LocalLangDB $ru;

	public function __construct()
	{
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

		$this->url = new URL('?c=' . get_class($this));
	}

	public function render()
	{
		$content[] = $this->performAction($this->detectAction());
		/*$content .= '<div style="float: right;">'.$this->makeLink('Import missing.txt', array(
			'c' => 'ImportMissing',
		)).'</div>';*/

		if (!$this->noRender) {
			$content[] = $this->renderList();
		}

		$content = $this->encloseInFieldset(__('Localize') . ' (' . count($this->allKeys) . ')', $content);
		//$this->index->addJQuery();
		$this->index->addJS('vendor/tuupola/jquery_jeditable/jquery.jeditable.js');
		$this->index->addJS(AutoLoad::getInstance()->nadlibFromDocRoot . "js/Localize.js");
		$this->index->addCSS("nadlib/CSS/PaginationControl.less");
		return $content;
	}

	public function renderList(): array
	{
		$keys = $this->getAllKeys();
		$table = $this->getTranslationTable($keys);

		$pager = new Pager();
		$pager->setNumberOfRecords(count($table));
		$pager->detectCurrentPage();

		$table = array_slice($table, $pager->getStartingRecord(), $pager->getPageSize(), true);
		$content[] = $pager->renderPageSelectors($this->url);

		$s = new slTable($table, 'id="localize" width="100%" class="table _table-striped"', [
			'key' => [
				'name' => 'Key',
				'no_hsc' => true,
			],
			$this->from->lang => $this->from->lang,
			'de' => ['name' => $this->de->lang, 'ano_hsc' => true],
			'ru' => ['name' => $this->ru->lang, 'ano_hsc' => true],
			'page' => [
				'name' => 'Page',
				'no_hsc' => true,
			],
			'del' => [
				'no_hsc' => true,
			]
		]);

		$content[] = $s;
		$content[] = $pager->renderPageSelectors($this->url);
		return $content;
	}

	public function getAllKeys()
	{
		if (!$this->allKeys) {
			$all = $this->from->getMessages();
			$all += $this->de->getMessages();
			$all += $this->ru->getMessages();
			$search = strtolower($this->request->getTrim('search'));
			if (($search !== '' && $search !== '0')) {
				foreach ($all as $key => $trans) {
					if (strpos(strtolower($trans), $search) === false &&
						strpos(strtolower($key), $search) === false
					) {
						unset($all[$key]);
					}
				}
			}

			$keys = array_keys($all);
			sort($keys);
			$this->allKeys = $keys;
		}

		return $this->allKeys;
	}

	/**
	 * @return non-empty-array[]
	 */
	public function getTranslationTable(array $keys): array
	{
		$table = [];
		foreach ($keys as $key) {
			$table[$key] = [
				'key' => '<a href="?c=' . get_class($this) . '&action=editOne&key=' . urlencode($key) . '">' . $key . '</a>',
				/*					'from' => new HTMLTag('td', array(
										'id' => $this->from->id($key),
										'lang' => $this->from->lang,
										'class' => 'inlineEdit',
									), $this->from->M($key)),
				*/];
			foreach ($this->languages as $lang) {
				/** @var LocalLangDB $lobj */
				$lobj = $this->$lang;
				$dbID = $lobj->id($key);

				$row = $this->db->fetchOneSelectQuery($this->table, ['id' => $dbID]);
				if (ifsetor($row['deleted'])) {
					$colorCode = 'muted';
				} else {
					$colorCode = ifsetor($this->from->ll[$key]) == ifsetor($lobj->ll[$key])
						? 'red bg-danger'
						: 'green bg-success';
				}

				$table[$key][$lang] = new HTMLTag('td', [
					'id' => $dbID ?: json_encode([$lobj->lang, $key]),
					'lang' => $lobj->lang,
					'class' => 'inlineEdit ' . $colorCode,
				],
					//isset($lobj->ll[$key]) ? $lobj->M($key) : '-');
					ifsetor($lobj->ll[$key], ''));

				// Page
				$row = $lobj->getRow($dbID);
				if ($row['page']) {
					$url = new URL($row['page']);
					$colorPage = strpos($url->getPath(), 'nadlib/be') !== false
						? 'be'
						: 'fe';
					$table[$key]['page'] = ifsetor($table[$key]['page']);    // can be multiple
					$table[$key]['page'] .= new HTMLTag('a', [
							'href' => $row['page'],
							'class' => $colorPage,
						], $url->getParam('c') ?: urldecode(basename($url->getPath()))) . ' ';
				}

			}

			// Del
			$table[$key]['del'] = new HTMLTag('a', [
				'href' => new URL('', [
					'c' => get_class($this),
					'action' => 'deleteRow',
					'code' => $key,
				])
			], '&times;', true);
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

	public function saveAction(): void
	{
		$id = $this->request->getTrim('id');
		if ($id) {
			$row = $this->save($id, $this->request->getTrim('value'));
			$this->request->set('ajax', true);
			echo htmlspecialchars($row['text']);
		}

		exit();
	}

	/**
	 * @param int|string $rel - can be int: ID of the already translated element
	 *                - can be string: Code of the original English element
	 * @param mixed $save
	 * @return array
	 * @throws DatabaseException
	 */
	public function save($rel, $save)
	{
		//$save = $this->request->getTrim('save');
		//$rel = $this->request->getInt('rel');
		if (is_numeric($rel)) {
			$this->db->runUpdateQuery($this->table, ['text' => $save], ['id' => $rel]);
			$row = $this->db->fetchOneSelectQuery($this->table, ['id' => $rel]);
		} else {
			//$code = $this->request->getTrim('code');
			[$lang, $code] = json_decode($rel, true, 512, JSON_THROW_ON_ERROR);
			$res = $this->db->runSelectQuery($this->table, [
				'code' => $code,
				'lang' => $lang,
			]);
			$row = $this->db->fetchAssoc($res);
			if (($rel = $row['id'])) {
				$this->db->runUpdateQuery($this->table, ['text' => $save], ['id' => $rel]);
			} else {
				$res = $this->db->runInsertQuery($this->table, [
					'code' => $code,
					'lang' => $lang,
					'text' => $save,
				]);
				$id = $this->db->lastInsertID($res);
				$row = $this->db->fetchOneSelectQuery($this->table, ['id' => $id]);
			}
		}

		//echo $this->db->lastQuery;
		return ['text' => $save] + (is_array($row) ? $row : []);
	}

	public function sidebar()
	{
		$f = new HTMLForm();
		$f->method('GET');
		$f->hidden('c', get_class($this));
		$f->stdout .= $f->input('search', $this->request->getTrim('search'), [], 'text', "span2");
		$f->stdout .= $f->submit('Search');
		$content[] = $f;

		$content[] = $this->encloseInAA($this->getUntranslatedCheckbox(), 'Untranslated');

		$content[] = '<hr />';
		$content[] = $this->getActionButton('Delete Duplicates', 'deleteDuplicates');

		$content[] = '<hr />';
		$content[] = $this->getActionButton('Download JSON', 'downloadJSON', null, [], 'btn btn-info');
		$content[] = $this->getActionButton('Save JSON', 'saveJSON', null, [], 'btn btn-info');

		$u = new Uploader(['json']);
		$f = $u->getUploadForm('file');
		$f->hidden('action', 'importJSON');
		$content[] = $f;

		$content[] = '<hr />';
		$desc = [
			'code' => [
				'label' => 'Code',
			]];
		foreach ($this->languages as $lang) {
			$desc[$lang] = [
				'label' => $lang,
			];
		}

		$desc['action'] = [
			'type' => 'hidden',
			'value' => 'addNew',
		];
		$desc['submit'] = [
			'type' => 'submit',
			'value' => __('Add new translation'),
		];
		$f = new HTMLFormTable($desc);
		$f->showForm();
		$content[] = $f;

		return $content;
	}

	/**
	 * @return list<non-falsy-string>
	 */
	public function getUntranslatedCheckbox(): array
	{
		$content = [];
		foreach ($this->languages as $lang) {
			$checked = ifsetor($_COOKIE['untranslated'][$lang]) ? 'checked="checked"' : '';
			$content[] = '<label>
				<input type="checkbox" class="setCookie" name="untranslated[' . $lang . ']" value="1" ' . $checked . '/>
				' . $lang . '
			</label><br />';
		}

		return $content;
	}

	public function deleteDuplicatesAction(): void
	{
		$rows = $this->db->fetchSelectQuery($this->table, [
			'lang' => 'en',
		], 'ORDER BY code, id');
		$prevCode = null;
		foreach ($rows as $row) {
			if ($prevCode == $row['code']) {
				echo 'Del: ', $row['code'], ' (id: ', $row['id'], ')<br />', "\n";
				$this->db->runDeleteQuery($this->table, [
					'id' => $row['id'],
				]);
			}

			$prevCode = $row['code'];
		}
	}

	public function deleteRowAction(): void
	{
		$code = $this->request->getString('code');
		$columns = $this->db->getTableColumns($this->table);
		if (ifsetor($columns['deleted'])) {
			$this->db->runUpdateQuery($this->table, [
				'deleted' => true,
			], [
				'code' => $code,
			]);
		} else {
			$this->from->delete($code);
			//debug($l->lastQuery);
		}

		$url = new URL();
		$url->unsetParam('action');
		$url->unsetParam('code');
		//debug($url.'');
		$this->request->redirect($url);
	}

	/**
	 * @throws JsonException
	 */
	public function downloadJSONAction(): void
	{
		$keys = $this->getAllKeys();
		$transTab = $this->getTranslationTable($keys);
		foreach ($transTab as &$row) {
			unset($row['page']);
			unset($row['del']);
			$row['key'] = strip_tags($row['key']);
			$row['en'] = strip_tags($row['en']);
			$row['de'] = strip_tags($row['de']);
			$row['ru'] = strip_tags($row['ru']);
		}

		$this->request->forceDownload('application/json', 'Localization.json');
		echo json_encode($transTab, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		exit();
	}

	public function saveJSONAction(): void
	{
		$keys = $this->getAllKeys();
		$transTab = $this->getTranslationTable($keys);
		foreach ($transTab as &$row) {
			unset($row['page']);
			unset($row['del']);
			$row['key'] = strip_tags($row['key']);
			$row['en'] = strip_tags($row['en']);
			$row['de'] = strip_tags($row['de']);
			$row['ru'] = strip_tags($row['ru']);
		}

		file_put_contents('sql/localize.json', json_encode($transTab, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	}

	/**
	 * @return list<non-falsy-string>
	 */
	public function importJSONAction(): array
	{
		$content = [];
		$fileData = json_decode(file_get_contents($_FILES['file']['tmp_name']), true);
		//debug(sizeof($fileData), first($fileData));
		foreach ($fileData as $row) {
			$key = $row['key'];
			foreach ($row as $lang => $value) {
				if ($lang == 'key') {
					continue;
				}

				$l = new LocalLangModel(null, $this->db);
				$l->table = $this->table;
				$l->findInDB([
					'code' => $key,
					'lang' => $lang,
				], '', false);
				if ($l->id) {
					if ($l->getValue() != $value) {
						$content[] = '<p class="text-danger">Import skipped for [' . $key . '/' . $lang . ']: "' . $value . '" exists as "' . $l->getValue() . '"</p>';
					}
				} elseif ($value && $value != 'nothing') {
					$content[] = '<p class="text-info">Importing for [' . $key . '/' . $lang . ']: "' . $value . '"' . BR;
					$l->insert([
						'code' => $key,
						'lang' => $lang,
						'text' => $value,
					]);
				}
			}
		}

		return $content;
	}

	/**
	 * @return list<non-falsy-string>
	 */
	public function addNewAction(): array
	{
		$content = [];
		$code = $this->request->getTrimRequired('code');
		foreach ($this->languages as $lang) {
			$text = $this->request->getTrim($lang);
			if ($text) {
				$this->from->insert([
					'code' => $code,
					'lang' => $lang,
					'text' => $text,
				]);
				$content[] = '<div class="message">Added ' . $text . ' (' . $lang . ')</div>';
			}
		}

		return $content;
	}

	public function untranslatedAction(): void
	{
		// nothing, used in the filter
	}

	/**
	 * @throws JsonException
	 */
	public function editOneAction()
	{
		$content = [];
		$key = $this->request->getTrimRequired('key');
		foreach ($this->languages as $lang) {
			$content[] = '<h2>' . $lang . '</h2>';
			/** @var LocalLangDB $langObj */
			$langObj = $this->$lang;
			$trans = ifsetor($langObj->ll[$key]);    // not T() because we don't need to replace %1
			$lines = count(explode("\n", $trans));
			$id = $langObj->id($key);
			if (!$id) {
				$id = [$lang, $key];    // @see $this->save()
				$id = json_encode($id, JSON_THROW_ON_ERROR);
			}

			$f = new HTMLForm();
			$f->action('?c=' . get_class($this));
			$f->hidden('action', 'saveOne');
			$f->stdout .= $f->input('id', $id);
			$f->stdout .= $f->textarea('value', $trans, [
				'style' => 'width: 100%; height: ' . (1 + $lines) . 'em',
			]);
			$f->stdout .= $f->submit(__('Save'));
			$content[] = $f->getContent();
			if (contains($trans, "\n")) {
				$content[] = '<div class="well">' . View::markdown($trans) . '</div>';
			}
		}

		$this->noRender = true;
		return $this->encloseInAA($content, $this->title = $key);
	}

	public function saveOneAction(): void
	{
		$id = $this->request->getTrim('id');
		if ($id) {
			$row = $this->save($id, $this->request->getString('value'));    // HTML tags allowed
			$key = $row['code'];
			$this->request->redirect(self::href([
				'action' => 'editOne',
				'key' => $key
			]));
		}
	}

}
