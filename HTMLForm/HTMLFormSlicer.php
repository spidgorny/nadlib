<?php

class HTMLFormSlicer
{
	public $slices;

	public function __construct(array $slices)
	{
		$this->slices = $slices;
	}

	static function sliceFromTill(array $desc, $from, $till)
	{
		$desc2 = [];
		foreach ($desc as $key => $val) {
			if ($key == $from) {
				$copy = true;
			}
			if ($copy) {
				$desc2[$key] = $val;
			}
			if ($key == $till) {
				break;
			}
		}
		return $desc2;
	}

	function distributeDescIntoSlices(array $desc)
	{
		foreach ($this->slices as &$slice) {
			$part = $this->sliceFromTill($desc, $slice['from'], $slice['till']);
			$slice['desc'] = $part;
		}
	}

	/**
	 * Works only after distributeDescIntoSlices
	 *
	 * @param array $values
	 */
	function fillValues(array $values)
	{
		$f = new HTMLFormTable();
		foreach ($this->slices as &$slice) {
			$slice['desc'] = $f->fillValues($slice['desc'], $values);
		}
	}

	/**
	 * Works only after distributeDescIntoSlices
	 *
	 * @return bool
	 */
	function validate()
	{
		$result = true;
		foreach ($this->slices as &$slice) {
			//debug($slice['name'] . ' ('.$slice['from'].'-'.$slice['till'].': '.sizeof($slice['desc']).')');
			$f2 = new HTMLFormTable($slice['desc']);
			$v = new HTMLFormValidate($f2);
			$result = $v->validate() && $result;    // recursive inside // this order to force execution
			$slice['desc'] = $v->getDesc();
		}
		//debug($this->slices);
		return $result;
	}

	/**
	 * Works only after distributeDescIntoSlices
	 *
	 * @param HTMLFormTable $f
	 */
	function showSlices(HTMLFormTable $f)
	{
		foreach ($this->slices as $slice) {
			//$part = $this->sliceFromTill($this->desc, $slice['from'], $slice['till']);
			$part = $slice['desc'];
			$f->fieldset($slice['name'], $slice['fieldsetParams']);
			$f->showForm($part);
		}
		//debug($slice);
	}

	function getErrorItems()
	{
		$content = '';
		foreach ($this->slices as $slice) {
			$content .= $this->getErrorItemsFromDesc($slice['desc']);
		}
		return $content;
	}

	protected function getErrorItemsFromDesc(array $descList)
	{
		$content = '';
		foreach ($descList as $key => $desc) {
			if ($desc['error']) {
				//$content .= '<li>'.($desc['label'] ? $desc['label'] : $key) . ': '. $desc['error'].'</li>';
				$content .= '<li>' . $desc['error'] . '</li>';
			}
			if ($desc['dependant']) {
				$content .= $this->getErrorItemsFromDesc($desc['dependant']);
			}
		}
		return $content;
	}

}
