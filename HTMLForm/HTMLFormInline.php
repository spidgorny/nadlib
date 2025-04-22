<?php

class HTMLFormInline extends HTMLFormTable
{

	public function s($content): string
	{
		return MergedContent::mergeStringArrayRecursive($content);
	}

	public function e($content): string
	{
		return htmlspecialchars($this->s($content));
	}

	public function mainFormStart(): void
	{
		$this->stdout .= '';
	}

	public function mainFormEnd(): void
	{
		$this->stdout .= '';
	}

	/**
     * Remove <table>
     * @param bool $mainForm
     * @param string $append
     */
    public function getForm(array $formData, array $prefix = [], $mainForm = true, $append = ''): string
	{
		$startedFieldset = false;
		$tmp = $this->stdout;
		$this->stdout = '';

		if ($this->mainForm) {
			$this->mainFormStart();
		}

		if ($this->fieldset) {
			$this->stdout .= "<fieldset " . $this->getAttrHTML($this->fieldsetMore) . ">
				<legend>" . $this->fieldset . "</legend>";
			$startedFieldset = true;
			$this->fieldset = null;
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

	/**
     * @return mixed[]
     */
    public function renderFormRows(array $formData, array $prefix = []): array
	{
		$content = [];
		foreach ($formData as $fieldName => $fieldDesc) {
			$content[] = $this->showTR(array_merge($prefix, [$fieldName]), $fieldDesc);
		}

		return $content;
	}

	public function showTR(array $prefix, array|HTMLFormFieldInterface $fieldDesc): array
	{
		$wrapElement = $fieldDesc['type'] !== 'html';
		if ($wrapElement) {
			$content[] = '<div class="form-group">' . PHP_EOL;
		}

		$content[] = $this->showCell($prefix, $fieldDesc);
		if ($wrapElement) {
			$content[] = '</div>' . PHP_EOL;
		}

		return $content;
	}

	public function showCell(array $fieldName, array|HTMLFormFieldInterface $desc): array
	{
		$fieldValue = $desc['value'] ?? null;
		$fieldObj = $this->switchType($fieldName, $fieldValue, $desc);
		$content[] = $fieldObj->getContent();
		if (ifsetor($desc['label'])) {
			$content = [
				'<label>' . PHP_EOL .
				'<span>' . $this->e($desc['label']) . '</span>', PHP_EOL,
				$content,
				'</label>',
				PHP_EOL
			];
			if (ifsetor($desc['error'])) {
				$content[] = '<div class="invalid-feedback d-block">';
				$content[] = $this->e($desc['error']);
				$content[] = '</div>';
			}
		}

		return $content;
	}

	public function input($name, $value = "", array $more = [], string $type = 'text', string $extraClass = ''): void
	{
		$extraClass = $extraClass ?: 'form-control';
		parent::input($name, $value, $more, $type, $extraClass);
	}

	public function getCreateTable(string $table): string
	{
		$typeMap = [
			'checkbox' => 'boolean',
			'date' => 'date',
			'radioset' => 'varchar',
		];
		$fields = [];
		foreach ($this->desc as $field => $desc) {
			if (is_int($field)) {
				continue;
			}

			$type = ifsetor($desc['type']);
			$sqlType = ifsetor($typeMap[$type], 'varchar');
			$fields[] = $field . ' ' . $sqlType;
		}

		return 'CREATE TABLE ' . $table . ' (' . implode(',' . PHP_EOL, $fields) . ')';
	}

}
