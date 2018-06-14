<?php

class HTMLFormInline extends HTMLFormTable
{

	function s($content)
	{
		return MergedContent::mergeStringArrayRecursive($content);
	}

	function mainFormStart()
	{
		$this->stdout .= '';
	}

	function mainFormEnd()
	{
		$this->stdout .= '';
	}

	/**
	 * Remove <table>
	 * @param array $formData
	 * @param array $prefix
	 * @param bool $mainForm
	 * @param string $append
	 * @return string
	 */
	function getForm(array $formData, array $prefix = array(), $mainForm = TRUE, $append = '')
	{
		if (!is_array($formData)) {
			debug_pre_print_backtrace();
		}
		$startedFieldset = FALSE;
		$tmp = $this->stdout;
		$this->stdout = '';

		if ($this->mainForm) {
			$this->mainFormStart();
		}
		if ($this->fieldset) {
			$this->stdout .= "<fieldset " . $this->getAttrHTML($this->fieldsetMore) . ">
				<legend>" . $this->fieldset . "</legend>";
			$startedFieldset = TRUE;
			$this->fieldset = NULL;
		}
		$this->stdout .= $this->s($this->renderFormRows($formData, $prefix));
		$this->stdout .= $append;
		if ($startedFieldset) {
			$this->stdout .= "</fieldset>";
		}
		if ($this->mainForm) {
			$this->mainFormEnd();
		}

		$part = $this->stdout;
		$this->stdout = $tmp;
		return $part;
	}

	function renderFormRows(array $formData, array $prefix = array())
	{
		$content = [];
		foreach ($formData as $fieldName => $fieldDesc) {
			$content[] = $this->showTR($prefix, $fieldDesc, array_merge($prefix, [$fieldName]));
		}
		return $content;
	}

	public function showTR(array $prefix, $fieldDesc, $path)
	{
		$content[] = '<div class="form-group">'.PHP_EOL;
		$content[] = $this->showCell($path, $fieldDesc);
		$content[] = '</div>'.PHP_EOL;
		return $content;
	}

	function showCell($fieldName, array $desc)
	{
		$fieldValue = isset($desc['value']) ? $desc['value'] : NULL;
		$fieldObj = $this->switchType($fieldName, $fieldValue, $desc);
		$content[] = $fieldObj->getContent();
		if (ifsetor($desc['label'])) {
			$content = [
				'<label>'.PHP_EOL.
				'<span>'.$desc['label'].'</span>', PHP_EOL,
				$content,
				'</label>',
				PHP_EOL
			];
		}
		return $content;
	}

}