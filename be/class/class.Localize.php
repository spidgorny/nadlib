<?php

class Localize extends Controller {
	/**
	 * @var LocalLang
	 */
	protected $from;

	/**
	 * @var LocalLang
	 */
	protected $to;

	public $title = 'Localize';

	function __construct() {
		parent::__construct();
		$this->from = new LocalLangDB('en');
		$this->from->indicateUntranslated = false;
		$this->to = new LocalLangDB('de');
		$this->to->indicateUntranslated = false;
		$this->url = new URL('?c=Localize');
	}

	function render() {
		$content = '';
		if (($id = $this->request->getTrim('id'))) {
			$this->save($id, $this->request->getTrim('value'));
			$this->index->request->set('ajax', true);
		} else {
			$content .= '<div style="float: right;">'.$this->makeLink('Import missing.txt', array(
				'c' => 'ImportMissing',
			)).'</div>';

			$keys = array_keys($this->from->getMessages());

			$pager = new Pager();
			$pager->setNumberOfRecords(sizeof($keys));
			$keys = array_slice($keys, $pager->startingRecord, $pager->itemsPerPage, true);
			$content .= $pager->renderPageSelectors($this->url);

			$table = array();
			foreach ($keys as $key) {
				$trans = $this->to->M($key);
				$id = $this->to->id($key);
				$table[$key] = array(
					'key' => $key,
					'from' => $this->from->M($key),
					'to' => new HTMLTag('td', array(
						//'rel' => $id,
						'id' => $id ? $id : $key,
						'class' => 'inlineEdit',
					), $trans),
				);
			}

			$s = new slTable($table, 'id="localize" width="100%"', array(
				'key' => 'Key',
				'from' => $this->from->lang,
				'to' => array('name' => $this->to->lang, 'ano_hsc' => true),
			));

			$content .= $s;
			$content .= $pager->renderPageSelectors($this->url);
			$content = $this->encloseIn(__('Localize'), $content);
			Index::getInstance()->addJS('js/jquery.jeditable.mini.js');
			Index::getInstance()->addJS("js/Localize.js");
		}
		return $content;
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
			$this->db->runUpdateQuery('app_interface', array('text' => $save), array('id' => $rel));
		} else {
			//$code = $this->request->getTrim('code');
			$code = $rel;
			$res = $this->db->runSelectQuery('app_interface', array(
				'code' => $code,
				'lang' => $this->to->lang,
			));
			$row = $this->db->fetchAssoc($res);
			if (($rel = $row['id'])) {
				$this->db->runUpdateQuery('app_interface', array('text' => $save), array('id' => $rel));
			} else {
				$this->db->runInsertQuery('app_interface', array(
					'code' => $code,
					'lang' => $this->to->lang,
					'text' => $save,
				));
			}
		}
		//echo $this->db->lastQuery;
		echo htmlspecialchars($save);
	}

}
