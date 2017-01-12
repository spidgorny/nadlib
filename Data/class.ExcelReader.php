<?php

/**
 * Class ExcelReader - only reads the XML file Excel
 * @see class SimpleXLSX
 */
class ExcelReader {
	protected $excel;
	protected $isCache = TRUE;
	protected $filename = 'cache/';
	protected $xml;
	public $ll;

	function __construct($excelFile) {
		$this->excel = $excelFile{0} == '/' ? $excelFile : dirname(__FILE__).'../'.$excelFile;
		$this->filename .= basename($this->excel).'.serial';

		// read from excel - SimpleXML can't be serialized
		//$this->xml = $this->readPersistant();
		if (!$this->xml) {
			$this->readExcel();
			$this->savePersistant($this->xml);
		}
		$this->ll = $this->getSheet(0);
	}

	function readPersistant() {
		$data = [];
		if (file_exists($this->filename)) {
			if (filemtime($this->filename) > filemtime($this->excel) && $this->isCache) {
				$data = file_get_contents($this->filename);
				if ($data) {
					$data = unserialize($data);
				}
			}
		}
		return $data;
	}

	function savePersistant($data) {
		$data = serialize($data);
		file_put_contents($this->filename, $data);
	}

	function readExcel() {
		$data = array();
		if (file_exists($this->excel)) {
			$filedata = file_get_contents($this->excel);
			$filedata = str_replace('xmlns="http://www.w3.org/TR/REC-html40"', '', $filedata);
			$this->xml = simplexml_load_string($filedata);
			$namespaces = $this->xml->getNamespaces(true);
			//debug($namespaces);
			foreach ($namespaces as $prefix => $ns) {
				$this->xml->registerXPathNamespace($prefix, $ns);
			}
		}
	}

	function getSheets() {
		$list = array();
		foreach ($this->xml->Worksheet as $sheet) {
			$attr = $sheet->attributes('ss', true);
			$list[] = trim($attr['Name']);
		}
		//d($sheet->asXML());
		return $list;
	}

	function getSheet($sheet = 0) {
		$data = array();
		$s = $this->xml->Worksheet[$sheet]->Table;
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
					$data[$key][$cellIndex] = $cellText;
				} else {
					$data[$key][] = $cellText;
				}
			}
			$key++;
		}
		//t3lib_div::debug($data); exit();
		//t3lib_utility_Debug::debugRows($data); exit();
		return $data;
	}

}
