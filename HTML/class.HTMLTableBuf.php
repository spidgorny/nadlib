<?php

class HTMLTableBuf {

	/**
	 * @var MergedContent
	 */
	public $stdout;

	function __construct() {
		$this->stdout = new MergedContent();
	}

	function table($more = "") {
		$this->stdout['table'] = "<table $more>\n";
	}

	function tablee() {
		$this->stdout['/table'] = "</table>\n";
	}

	function htr($more = "") {
		$this->stdout->addSub('thead', "<tr ".$more.">\n");
	}

	function htre() {
		$this->stdout->addSub('thead', "</tr>\n");
	}

	function tr($more = "") {
		$this->stdout->addSub('tbody', "<tr ".$more.">\n");
	}

	function tre() {
		$this->stdout->addSub('tbody', "</tr>\n");
	}

	function ftr($more = "") {
		$this->stdout->addSub('tfoot', "<tr ".$more.">\n");
	}

	function ftre() {
		$this->stdout->addSub('tfoot', "</tr>\n");
	}

	function th($more = '') {
		$this->stdout->addSub('thead', "<th ".$more.">\n");
	}

	function the() {
		$this->stdout->addSub('thead', "</th>\n");
	}

	function td($more = "") {
		$this->stdout->addSub('tbody', "<td $more>");
	}

	function tde() {
		$this->stdout->addSub('tbody', "</td>\n");
	}

	function thead($text) {
		$this->stdout->addSub('thead', $text);
	}

	function text($text) {
		$this->stdout->addSub('tbody', $text);
	}

	function tfoot($text) {
		$this->stdout->addSub('tfoot', $text);
	}

	function cell($a, array $more = array()) {
		$this->td(HTMLTag::renderAttr($more));
		$this->text($a);
		$this->tde();
	}

	/**
	 * @param array $aCaption	- array of names
	 * @param array $thmore		- more on each column TH
	 * @param string $trmore	- more on the whole row
	 */
	function thes(array $aCaption, $thmore = array(), $trmore = '') {
		$this->htr($trmore);
		foreach($aCaption as $i => $caption) {
			$more = isset($thmore[$i]) ? HTMLTag::renderAttr($thmore[$i]) : '';
			if (is_array($more)) {
				$more = HTMLTag::renderAttr($more);
			}
			$this->th($more);
			$this->thead('thead', $caption);
			$this->the();
		}
		$this->htre();
		//debug($this->stdout);
	}

	function render() {
		print($this->getContent());
	}

	function getContent() {
		return $this->stdout.'';
	}

	function tag(HTMLTag $tag) {
		$this->stdout->addSub('tbody', $tag.'');
	}

	function isDone() {
		return !!$this->stdout['/table'];
	}

}
