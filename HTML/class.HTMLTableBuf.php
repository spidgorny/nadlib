<?php

/**
 * @property  table
 * @property  thead
 * @property  tbody
 */
class HTMLTableBuf extends MergedContent {

	function __construct() {
		parent::__construct(array(
			'table' => '',
			'thead' => '',
			'tbody' => '',
		));
	}

	function table($more = "") {
		$this['table'] = "<table $more>\n";
	}

	function tablee() {
		$this['/table'] = "</table>\n";
	}

	function htr($more = "") {
		$this->addSub('thead', "<tr".rtrim(' '.$more).">\n");
	}

	function htre() {
		$this->addSub('thead', "</tr>\n");
	}

	function tr($more = "") {
		$this->addSub('tbody', "<tr".rtrim(' '.$more).">\n");
	}

	function tre() {
		$this->addSub('tbody', "</tr>\n");
	}

	function ftr($more = "") {
		$this->addSub('tfoot', "<tr ".$more.">\n");
	}

	function ftre() {
		$this->addSub('tfoot', "</tr>\n");
	}

	function th($more = '') {
		$this->addSub('thead', "<th".rtrim(' '.$more).">\n");
	}

	function the() {
		$this->addSub('thead', "</th>\n");
	}

	function td($more = "") {
		$this->addSub('tbody', "<td".rtrim(' '.$more).">");
	}

	function tde() {
		$this->addSub('tbody', "</td>\n");
	}

	function addTHead($text) {
		$this->addSub('thead', $text);
	}

	function text($text) {
		$this->addSub('tbody', $text);
	}

	function tfoot($text) {
		$this->addSub('tfoot', $text);
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
			$this->thead[] .= '<th' . rtrim(' '.$more). '>' . $caption . '</th>';
		}
		$this->htre();
		//debug($this);
	}

	function render() {
		print($this->getContent());
	}

	function getContent() {
		return $this.'';
	}

	function tag(HTMLTag $tag) {
		$this->addSub('tbody', $tag.'');
	}

	function isDone() {
		return !!$this['/table'];
	}

}
