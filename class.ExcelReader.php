<?php

class ExcelReader {
	protected $excel;
	protected $isCache = TRUE;
	protected $filename = 'cache/';
	public $ll;

	function __construct($excelFile) {
		$this->excel = dirname(__FILE__).'/'.$excelFile;
		$this->filename .= $this->excel.'.serial';

		// read from excel
		$this->ll = $this->readPersistant();
		if (!$this->ll) {
			$this->ll = $this->readExcel();
			if ($this->ll) {
				$this->savePersistant($this->ll);
			}
		}
	}

	function readPersistant() {
		//return false;
		if (file_exists($this->filename)) {
			if (filemtime($this->filename) > filemtime($this->excel) && $this->isCache) {
				$data = file_get_contents($this->filename);
				$data = unserialize($data);
			}
		}
		return $data;
	}

	function savePersistant($data) {
		$data = serialize($data);
		$data = file_put_contents($this->filename, $data);
	}

	function readExcel() {
		$data = array();
		if (file_exists($this->excel)) {
			$filedata = file_get_contents($this->excel);
			$filedata = str_replace('xmlns="http://www.w3.org/TR/REC-html40"', '', $filedata);
			$xml = simplexml_load_string($filedata);
			$namespaces = $xml->getNamespaces(true);
			//debug($namespaces);
			foreach ($namespaces as $prefix => $ns) {
				$xml->registerXPathNamespace($prefix, $ns);
			}
			$s = $xml->Worksheet[0]->Table;
			$key = 0;
			foreach ($s->Row as $row) {
				foreach ($row->Cell as $cell) {
					$cellText = $cell->Data;
					if (!$cellText) {
						$cellText = $cell->asXML();
						$cellText = strip_tags($cellText);
					}
					$cellText = trim($cellText);
					$attr = $cell->attributes('ss', true);
					if (intval($attr['Index'])) {
						$cellIndex = intval($attr['Index'])-1;
					} else {
						$cellIndex = sizeof($data[$key]);
					}
					$data[$key][$cellIndex] = $cellText;
				}
				$key++;
			}
		}
		//t3lib_div::debug($data); exit();
		//t3lib_div::debugRows($data); exit();
		return $data;
	}

}
