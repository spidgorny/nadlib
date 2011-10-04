<?php

class HTMLTableBuf {
	var $stdout = "";

	function table($more = "") {
		$this->stdout .= "<table $more>\n";
	}

	function tablee() {
		$this->stdout .= "</table>\n";
	}

	function tr($more = "") {
		$this->stdout .= "<tr ".$more.">\n";
	}

	function tre() {
		$this->stdout .= "</tr>\n";
	}

	function th($more = '') {
		$this->stdout .= "<th ".$more.">\n";
	}

	function the() {
		$this->stdout .= "</th>\n";
	}

	function td($more = "") {
		$this->stdout .= "<td $more>";
	}

	function tde() {
		$this->stdout .= "</td>\n";
	}

	function cell($a, $width = NULL, $more = '') {
		if ($width) {
			$this->td('width="'.$width.'" '.$more);
		} else {
			$this->td($more);
		}
		$this->stdout .= $a;
		$this->tde();
	}

	function thes($aCaption, $thmore = array(), $trmore = '') {
		$this->stdout .= '<thead>';
		$this->tr($trmore);
			foreach($aCaption as $i => $caption) {
				$this->th($thmore[$i]);
					$this->stdout .= $caption;
				$this->the();
			}
		$this->tre();
		$this->stdout .= '</thead>';
	}

	function render() {
		print($this->getContent());
	}

	function getContent() {
		return $this->stdout;
	}

	function tag(HTMLTag $tag) {
		$this->stdout .= $tag.'';
}

}
