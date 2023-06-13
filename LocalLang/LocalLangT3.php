<?php

class LocalLangT3 extends LocalLang {

	/**
	 * @var string
	 */
	var $LLkey = 'default';

	/**
	 * To avoid readLLfile() on the same file
	 * @var array
	 */
	var $loaded = [];

	function saveMissingMessage($text) {
	}

	/**
	 * @param $label - LLL:EXT:nin_pbl/locallang_db.xml:tx_ninpbl_project.id_company
	 */
	function loadLabel($label) {
		if ($label) {
			$file = explode(':', $label);
			$file = $file[1].':'.$file[2];
			if (!$this->loaded[$file]) {
				$tempLOCAL_LANG = t3lib_div::readLLfile($file, $this->LLkey);
				foreach ($tempLOCAL_LANG[$this->LLkey] as $key => $single) {
					$this->ll[$key] = $single[0]['source'];
				}
				([$label,
					$file,
					//$tempLOCAL_LANG,
					$this->ll
				]);
				$this->loaded[$file] = 1;
			}
		}
	}

}
