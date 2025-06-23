<?php

/**
 * Class ExcelReader - only reads the XML file Excel
 * @see class SimpleXLSX
 */
class ExcelReader
{
	/**
	 * @var mixed[]
	 */
	public $ll;
	protected $excel;
	protected $isCache = TRUE;
	protected $filename = 'cache/';
	protected $xml;

	public function __construct($excelFile, $usePersistance = false)
	{
		$this->excel = $excelFile[0] === '/' ? $excelFile : $excelFile;
		$this->filename .= basename($this->excel) . '.serial';

		// read from excel - SimpleXML can't be serialized
		if ($usePersistance) {
			$this->xml = $this->readPersistant();
		}

		if (!$this->xml) {
			$this->readExcel();
			if ($this->xml && $usePersistance) {
				$this->savePersistant($this->xml);
			}
		}

		$this->ll = $this->getSheet(0);
	}

	public function readPersistant()
	{
		$data = NULL;
		if (file_exists($this->filename) && (filemtime($this->filename) > filemtime($this->excel) && $this->isCache)) {
			$data = file_get_contents($this->filename);
			if ($data) {
				$data = unserialize($data);
			}
		}

		return $data;
	}

	public function readExcel(): void
	{
		if (file_exists($this->excel)) {
			$filedata = file_get_contents($this->excel);
			$filedata = str_replace('xmlns="http://www.w3.org/TR/REC-html40"', '', $filedata);
			$this->xml = simplexml_load_string($filedata);
			$namespaces = $this->xml->getNamespaces(true);
			foreach ($namespaces as $prefix => $ns) {
				$this->xml->registerXPathNamespace($prefix, $ns);
			}
		} else {
			throw new Exception('File ' . $this->excel . ' is not found');
		}
	}

	public function savePersistant($data): void
	{
		//$data = serialize($data);   // Serialization of 'SimpleXMLElement' is not allowed
		$data = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
		file_put_contents($this->filename, $data);
	}

	/**
	 * @return array<int, non-empty-array<(int<min, -2> | int<0, max>), string>>
	 */
	public function getSheet($sheet = 0): array
	{
		$data = [];
		$s = $this->xml->Worksheet[$sheet]->Table;
		if ($this->xml->Worksheet[$sheet]) {
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
					if (intval($attr['Index']) !== 0) {
						$cellIndex = intval($attr['Index']) - 1;
						$data[$key][$cellIndex] = $cellText;
					} else {
						$data[$key][] = $cellText;
					}
				}

				$key++;
			}
		} else {
			//3debug(array_keys($this->xml->Worksheet));
			throw new Exception('There is no sheet ' . $sheet . ' in the file ' . $this->filename . ' generated from ' . $this->excel);
		}

		return $data;
	}

	/**
	 * @return string[]
	 */
	public function getSheets(): array
	{
		$list = [];
		foreach ($this->xml->Worksheet as $sheet) {
			$attr = $sheet->attributes('ss', true);
			$list[] = trim($attr['Name']);
		}

		//d($sheet->asXML());
		return $list;
	}

}
