<?php

/**
 * Class HTMLFormTable
 * @see HTMLFormField
 */
class HTMLFormTable extends HTMLForm
{
	/**
	 * If set then each field gets ['value'] appended to it's name
	 * The idea was to merge $desc with $_REQUEST easily, but it makes ugly URL
	 * @var bool
	 */
	public $withValue = false;

	/**
	 * Will render labels above the fields, otherwise on the left
	 * @var bool
	 */
	public $defaultBR = false;

	/**
	 * Additional parameters for <tr>
	 * @var string[]
	 */
	public $trmore;

	/**
	 * Additional parameters for <table>
	 * @var array
	 */
	public $tableMore;

	/**
	 * Shows field names near fields
	 * @var bool
	 */
	public $debug = false;

	/**
	 * The form description table
	 * @var array
	 */
	public $desc;

	public $noStarUseBold;

	/**
	 * @var HTMLFormValidate
	 */
	public $validator;

	/**
	 * Is needed in case validation is made before checking if it's valid.
	 * It's set in HTMLFormTable::validate();
	 *
	 * @var bool
	 */
	public $isValid = false;

	/**
	 * @var HTMLForm
	 */
	protected $mainForm;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * HTMLFormTable constructor.
	 * @param array $prefix
	 * @param string $fieldset
	 * @see HTMLFormField
	 */
	public function __construct(array $desc = [], $prefix = [], $fieldset = '', $id = null)
	{
		parent::__construct('', $id);
		$this->setDesc($desc);
		$this->prefix($prefix);
		$this->request = Request::getInstance();
		if ($this->desc) {
			// todo: does not get correct values OR values at all!
			if ($this->request->is_set(first($this->prefix))) {
				$form = $this->request->getSubRequestByPath($this->prefix);
			} else {
				$form = $this->request;
			}

			$this->importValues($form);
			//$this->showForm();	// call manually to have a chance to change method or defaultBR
		}

		if ($fieldset) {
			$this->fieldset($fieldset);
		}

		$this->tableMore['class'] = 'htmlFormTable';
	}

	/**
	 * Makes sure each element is an array
	 */
	public function setDesc(array $desc): void
	{
		$this->desc = $desc;
		foreach ($this->desc as &$sub) {
			if (is_string($sub)) {
				$sub = ['label' => $sub];
			}
		}
	}

	/**
	 * fillValues() is looping over the existing values
	 * This function is looping over desc
	 * @param Request $form - Request instead of an array because of the trim() function only?
	 */
	public function importValues(Request $form): void
	{
		//$this->desc = $this->fillValues($this->desc, $form);
		foreach ($this->desc as $key => &$desc) {
			// can be HTMLFormTable (@see HTMLFormValidate)
			$type = is_array($desc)
				? ifsetor($desc['type'])
				: (is_object($desc) ? get_class($desc) : 'text');
			if ($desc instanceof HTMLFormTable) {
				$prefix_1 = $desc->prefix;
				array_shift($prefix_1);
				$subForm = $form->getSubRequestByPath($prefix_1);
				nodebug('subimport', count($form->getAll()), implode(', ', array_keys($form->getAll())),
					$desc->prefix, $prefix_1, count($subForm->getAll()), implode(', ', $subForm->getAll()));
				$desc->importValues($subForm);
				//debug('after', $desc->desc);
			} elseif ($type instanceof HTMLFormDatePicker) {
				/** @var HTMLFormDatePicker $type */
				$val = $form->getTrim($key);
				if ($val !== '' && $val !== '0') {
					$desc['value'] = $type->getISODate($val);
					//debug(__METHOD__, $val, $desc['value']);
				}
			} elseif ($form->is_set($key)) {
				$desc['value'] = is_array($form->get($key)) ? $form->getArray($key) : $form->getTrim($key);
			} // else keep default ['value']
		}
	}

	public static function getQuickForm(array $desc)
	{
		$f = new self($desc);
		$f->showForm();
		return $f->getBuffer();
	}

	/**
	 * @param bool $mainForm
	 * @return $this
	 */
	public function showForm(array $prefix = [], $mainForm = true, string $append = ''): static
	{
		$this->tableMore['class'] = collect(trimExplode(' ', $this->tableMore['class'] ?? ''))->add($this->defaultBR ? 'defaultBR' : '')->join(' ');

		// can't do this as btnSubmit should not be in the form
//		$this->prefix = $prefix;  // HTMLFormInput reads from the form


		$this->stdout .= $this->s($this->getForm($this->desc, $prefix, $mainForm, $append));
		return $this;
	}

	public function getForm(array $formData, array $prefix = [], $mainForm = true, string $append = ''): string|array
	{
//		llog('getForm', $prefix);
		$startedFieldset = false;

		if ($this->mainForm) {
			$content[] = $this->mainFormStart();
		}

		if ($this->fieldset) {
			$content[] = "<fieldset " . self::getAttrHTML($this->fieldsetMore) . ">
				<legend>" . $this->fieldset . "</legend>";
			$startedFieldset = true;
			$this->fieldset = null;
		}

		$content[] = '<table ' . HTMLForm::getAttrHTML($this->tableMore) . '><tbody>' . PHP_EOL;
		$content[] = $this->renderFormRows($formData, $prefix);
		$content[] = PHP_EOL . "</tbody></table>" . $append;
		if ($startedFieldset) {
			$content[] = "</fieldset>";
		}

		if ($this->mainForm) {
			$content[] = $this->mainFormEnd();
		}

		return MergedContent::mergeStringArrayRecursive($content);
	}

	public function mainFormStart(): string
	{
		return '<table class="htmlFormDiv"><tr><td>' . "\n";
	}

	public function renderFormRows(array $formData, array $prefix = []): string|array
	{
		$content = [];
		foreach ($formData as $fieldName => $fieldDesc) {
			$path = $prefix;
			$fnp = strpos($fieldName, '[');
			if ($fnp !== false) {
				$path[] = substr($fieldName, 0, $fnp);
				$path[] = substr($fieldName, $fnp + 1, -1);
			} else {
				$path[] = $fieldName;
			}

			//debug($fieldName, $fieldDesc);
			$sType = is_object($fieldDesc)
				? get_class($fieldDesc)
				: ($fieldDesc['type'] ?? '');
			// avoid __toString on collection
			// it needs to run twice: one checking for the whole desc and other for desc[type]
			$sType = is_object($sType)
				? get_class($sType)
				: $sType;
//			llog('renderFormRows', [
//				'sType' => $sType,
//				'is_array' => is_array($fieldDesc),
//				'HTMLFormFieldInterface' => $fieldDesc instanceof HTMLFormFieldInterface,
//			]);
			if ($sType === __CLASS__) {
				/** @var HTMLFormTable $subForm */
				$subForm = $fieldDesc;
				$content[] = $subForm->showForm();
				$content[] = '<tr><td colspan="2">' .
					$subForm->getBuffer() .
					'</td></tr>' . "\n";
			} elseif ($fieldDesc instanceof HTMLFormFieldInterface || is_array($fieldDesc)) {
				if (in_array($sType, ['hidden', 'hiddenArray'])) {
					// hidden are shown without table cells
					//debug(array($formData, $path, $fieldDesc));
					$content[] = $this->showCell($path, $fieldDesc);
				} else {
					//debug($prefix, $fieldDesc, $path);
					$content[] = $this->showTR($path, $fieldDesc);
				}
			} else {
				pre_print_r([
					'fieldName' => $fieldName,
					'fieldDesc' => $fieldDesc,
				]);
				pre_print_r($formData);
				throw new InvalidArgumentException(__METHOD__ . '#' . __LINE__ . ' has wrong parameter');
			}
		}

		return MergedContent::mergeStringArrayRecursive($content);
	}

	public function showCell(array $fieldName, array|HTMLFormFieldInterface $desc): string|array
	{
		$content = '';
//		llog('showCell', $fieldName);
		$desc['TDmore'] = (isset($desc['TDmore']) && is_array($desc['TDmore']))
			? $desc['TDmore']
			: [];
		if (isset($desc['newTD'])) {
			$content .= '</tr></table></td>
			<td ' . implode(' ', $desc['TDmore']) . '>
			<table ' . HTMLForm::getAttrHTML($this->tableMore) . '><tr>' . "\n";
		}

		$fieldValue = $desc['value'] ?? $desc['default'] ?? null;
		$type = $desc['type'] ?? null;

		if (!is_object($type) && ($type === 'hidden' || in_array($type, ['fieldset', '/fieldset']))) {
			$field = $this->switchType($fieldName, $fieldValue, $desc);
			$content .= $field->getContent();
			return $content;
		}

		if (!empty($desc['formHide'])) {
			return [];
		}

		if (empty($desc['br']) && !$this->defaultBR) {
			ifsetor($desc['TDmore'], ['class' => '']);    //set
			$desc['TDmore']['class'] = ifsetor($desc['TDmore']['class'], '');
			$desc['TDmore']['class'] = $desc['TDmore']['class'] ?? '';
			$desc['TDmore']['class'] .= ' tdlabel';
		}

		$content .= '<td ' . self::getAttrHTML($desc['TDmore'] + [
					'class' => 'showCell'
				]) . '>';
		if ($this->withValue) {
			$fieldName[] = 'value';
		}

		$fieldObj = $this->switchType($fieldName, $fieldValue, $desc);
		//			<<== HERE content generated
		$newContent = $fieldObj->getContent();
//				$newContent = '['.$newContent.']';

		// checkboxes are shown in front of the label
		if ($type === 'checkbox') {
			$fieldObj['label'] = $newContent . ' ' . $fieldObj['label'];
			$newContent = '';
		}

		$content .= $this->showLabel($fieldObj, $fieldName);

		if (isset($desc['error'])) {
			//debug($fieldName, $desc);
			//debug_pre_print_backtrace();
			$desc['class'] = ifsetor($desc['class']) . ' error';
		}

		if (ifsetor($desc['wrap'])) {
			if (!$desc['wrap'] instanceof Wrap) {
				$desc['wrap'] = new Wrap($desc['wrap']);
			}

			$newContent = $desc['wrap']->wrap($newContent);
		}

		$content .= ($desc['prepend'] ?? '')
			. $newContent .                                                //			<<== USED content here
			($desc['append'] ?? '');

		$content .= ifsetor($desc['afterLabel']);

		if (ifsetor($desc['error'])) {
			$content .= '<div id="errorContainer[' . $this->getName($fieldName, '', true) . ']"
			class="error ui-state-error alert-error alert-danger">';
			$content .= $desc['error'];
			$content .= '</div>';
		}

		if (ifsetor($desc['newTD'])) {
			$content .= '</td>';
		}
		return $content;
	}

	/**
	 * @param mixed $fieldValue
	 * @param array|HTMLFormFieldInterface $descIn
	 */
	public function switchType(array $fieldName, $fieldValue, $descIn)
	{
		if ($descIn instanceof HTMLFormFieldInterface) {
			$field = $descIn;
			$field->setField($fieldName);
			//debug($field);
		} else {
			//debug($fieldName, $descIn);
			$field = new HTMLFormField($descIn, $fieldName);
		}

		$field['value'] = $fieldValue;

		$field->setForm($this);    // don't clone, because we may want to influence the original form
		return $field;
	}

	public function showLabel(HTMLFormField $desc, $fieldName): string
	{
		//debug($desc->getArray());
		$elementID = $desc->elementID;
		$withBR = (ifsetor($desc['br']) === null && $this->defaultBR) || $desc['br'];
		if (!isset($desc['label'])) {
			return '';
		}

		$label = $desc['label'];
		if (!$withBR) {
			if (!$desc->isCheckbox()) {
				$label .= $label ? ':&nbsp;' : '';  // don't append to "submit"
			}

			if ($desc->isObligatory()) {
				if ($this->noStarUseBold) {
					$label = '<b title="Obligatory">' . $label . '</b>';
				} else {
					$label .= '<span class="htmlFormTableStar">*</span>';
				}
			} elseif ($this->noStarUseBold) {
				$label = '<span title="Optional">' . $label . '</span>';
			}

			$label .= ifsetor($desc['explanationgif']);
			$label .= $this->debug
				? '<br><font color="gray">' . $this->getName($fieldName, '', true) . '</font>'
				: '';
		}

		$content = '';
		$content .= ifsetor($desc['beforeLabel']);
		//debug($label);
		assert(is_string($label) || $label instanceof HtmlString || $label instanceof HTMLTag);
		$content .= '<label for="' . $elementID . '" class="' . ($desc['labelClass'] ?? '') . '">' . $label . '</label>';
		if (!$withBR) {
			$content .= '</td><td>';
		}
		return $content;
	}

	/**
	 * @param array $prefix
	 * @param array|HTMLFormTypeInterface $fieldDesc
	 * @return string
	 */
	public function showTR(array $prefix, array|HTMLFormFieldInterface $fieldDesc): string|array
	{
		if (!isset($fieldDesc['horizontal']) || !$fieldDesc['horizontal']) {
			$content[] = "<tr " . self::getAttrHTML($fieldDesc['TRmore'] ?? null) . ">";
		}

		if (isset($fieldDesc['table'])) {
			$content[] = '<td class="table">';
			$f2 = new HTMLFormTable($fieldDesc);
			$f2->showForm($prefix, false);
			$content[] = $f2->stdout;
			$content[] = "</td>";
		}

		if (isset($fieldDesc['dependant'])) {
			$fieldDesc['prepend'] = '<fieldset class="expandable"><legend>';
			$fieldDesc['append'] .= '</legend>' .
				$this->getForm($fieldDesc['dependant'], $prefix, false) // $path
				. '</fieldset>';
			$content[] = $this->showCell($prefix, $fieldDesc);
		} else {
			$content[] = $this->showCell($prefix, $fieldDesc);
		}

		$content[] = "</tr>\n";
		return implode('', $content);
	}

	public function mainFormEnd(): string
	{
		return "</td></tr></table>\n";
	}

	/**
	 * @return mixed[]
	 */
	public static function sliceFromTill(array $desc, $from, $till = null): array
	{
		$desc2 = [];
		$copy = false;
		foreach ($desc as $key => $val) {
			if (!$copy) {
				$copy = $key === $from;
			}

			if ($copy) {
				$desc2[$key] = $val;
			}

			if ($key === $till) {
				break;
			}
		}

		return $desc2;
	}

	public function showRow($fieldName, array $desc2): void
	{
		//foreach ($desc as $fieldName2 => $desc2) {
		//if ($fieldName2 != 'horisontal') {
		$this->mainFormStart();
		$path = $fieldName;
		//$path[] = $fieldName2;
		$this->showCell($path, $desc2);
		$this->mainFormEnd();
		//}
		//}
	}

	/**
	 * Deprecated. Used to retrieve name/values pairs from the array with $this->withValues = FALSE.
	 *
	 * @param array $arr Form description array
	 * @param string $col Column name that contains values. Within this class default value is the only that makes sense.
	 * @return array    1D array with name/values
	 * @deprecated
	 */
	public function getValues(?array $arr = null, $col = 'value'): array
	{
		$arr = $arr ?: $this->desc;
		$res = [];
		if (is_array($arr)) {
			foreach ($arr as $key => $ar) {
				if (is_array($ar) && !ifsetor($ar['disabled'])) {
					$res[$key] = ifsetor($ar['type']) instanceof HTMLFormDatePicker ? $ar['type']->getISODate($ar[$col]) : $ar[$col];
				}
			}
		}

		unset($res['xsrf']);    // is not a form value
		return $res;
	}

	/**
	 * Returns the $form parameter with minimal modifications only for the special data types like time in seconds.
	 *
	 * @param array $desc array from $_REQUEST.
	 * @param array $form Structure of the form.
	 * @return array    Processed $form.
	 */
	public function acquireValues(array $desc, array $form = []): array
	{
		foreach ($desc as $field => $params) {
			$type = ifsetor($params['type']);
			if ($type === 'datepopup') {
				$date = strtotime($form[$field]);
				debug(__METHOD__, $field, $form[$field], $date);
				if ($date) {
					$form[$field] = $date;
				}
			} elseif (in_array($type, ['check', 'checkbox'])) {
				$form[$field] = strtolower($form[$field]) === 'on'
					|| $form[$field];
			}
		}

		return $form;
	}

	/**
	 * @param bool $forceInsert
	 * @return array
	 */
	public function fill(array $assoc, $forceInsert = false)
	{
		$this->desc = $this->fillValues($this->desc, $assoc, $forceInsert);
		return $this->desc;
	}

	/**
	 * Fills the $desc array with values from $assoc.
	 * Understands $assoc in both single-array way $assoc['key'] = $value
	 * and as $assoc['key']['value'] = $value.
	 * Non-static due to $this->withValue and $this->formatDate
	 *
	 * @param array $desc - Structure of the HTMLFormTable
	 * @param array $assoc - Values in one of the supported formats.
	 * @param bool $forceInsert
	 * @return    array    HTMLFormTable structure.
	 */
	protected function fillValues(array $desc, ?array $assoc = null, $forceInsert = false): array
	{
		foreach ($assoc as $key => $val) {
			//$descKey = ifsetor($desc[$key]);		// CREATES $key => NULL INDEXES

			$descKey = $desc[$key] ?? null;
			if (!$descKey) {
				continue;
			}

			// convert to array
			$descKey = is_array($descKey) ? $descKey : ['name' => $descKey];

			// calc $val
			if ($desc[$key] instanceof HTMLFormFieldInterface) {
				$desc[$key]->setValue($this->withValue ? $val['value'] : $val);
			} else {
				$desc[$key]['value'] = $this->withValue ? $val['value'] : $val;
			}

			/** @var HTMLFormType|HTMLFormDatePicker $type */
			$type = ifsetor($descKey['type']);
			$sType = is_object($type)
				? get_class($type)
				: $type;
			if ($sType === 'date' && (is_numeric(ifsetor($descKey['value'])) && $descKey['value'])) {
				$desc[$key]['value'] = $this->formatDate($descKey['value'], $descKey);
			}

			// set $val
			// this code is never executed due to 'continue' above
			if (is_array($descKey) || $forceInsert) {
				//debug($key, gettype2($sType), is_object($type));
				if (is_object($type)) {
					$type->setValue($val);
				}

				if (ifsetor($descKey['dependant'])) {
					$desc[$key]['dependant'] = $this->fillValues($descKey['dependant'], $assoc);
					//t3lib_div::debug($desc[$key]['dependant']);
				}
			} elseif ($descKey instanceof HTMLFormField) {
				//debug($key, gettype2($sType), is_object($type));
				$descKey->setValue($val);
			}
		}

		return $desc;
	}

	public function formatDate($timestamp, $key): string
	{
		return date('Y-m-d H:i:s', $timestamp);
	}

	/**
	 * Correct function to use outside if the desc is assigned already
	 */
	public function fillDesc(array $assoc): static
	{
		$this->desc = $this->fillValues($this->desc, $assoc);
		return $this;
	}

	public function getSingle(array|string $fieldName, array $desc)
	{
		$field = $this->switchType(is_array($fieldName) ? $fieldName : [$fieldName], ifsetor($desc['value'], $desc['default'] ?? ''), $desc);
		return $field->getContent();
	}

	public function repostRequest(Request $r, array $prefixes = []): void
	{
		//debug($r);
		foreach ($r->getAll() as $key => $val) {
			if (is_array($val)) {
				$this->repostRequest(new Request($val), array_merge($prefixes, [$key]));
			} else {
				$copy = $prefixes;
				array_shift($copy);
				//debug($copy);
				$key = current($prefixes) . (
					$copy !== []
						? '[' . implode('][', $copy) . ']'
						: ''
					) .
					($prefixes !== [] ? '[' : '') .
					$key .
					($prefixes !== [] ? ']' : '');
				$this->hidden($key, $val);
			}
		}
	}

	public function validate()
	{
		$this->validator = new HTMLFormValidate($this);
		$this->isValid = $this->validator->validate();
		$this->desc = $this->validator->getDesc();
		return $this->isValid;
	}

	/**
	 * Use validate() to validate.
	 * @param $class - unique identifier of the form on the site
	 * which allows several forms to be submitted in a different order
	 * @param bool $check
	 */
	public function xsrf($class, $check = false): void
	{
		$this->class = $class;
		if (!$check) {
			if (function_exists('openssl_random_pseudo_bytes')) {
				$token = bin2hex(openssl_random_pseudo_bytes(16));
			} else {
				$token = uniqid(php_uname('n'), true);
			}

			$this->desc['xsrf'] = [
				'type' => 'hidden',
				'value' => $token,
			];
			$_SESSION[__CLASS__]['xsrf'][$class] = $token;
		} else {    // Check
			$this->desc['xsrf'] = [
				'value' => '',    // use fill($this->request->getAll()) to fill in and validate()
			];
		}
	}

	public function clearValues(): void
	{
		foreach ($this->desc as &$row) {
			if (isset($row['value'])) {
				unset($row['value']);
			}

			// should not be elseif
			if ($row['type'] instanceof HTMLFormType) {
				$row['type']->setValue(null);
			}
		}
	}

	public function undo(): void
	{
		$this->stdout = '';
	}

	public function setAllOptional(): void
	{
		foreach ($this->desc as &$desc) {
			$desc['optional'] = true;
		}
	}

	/**
	 * Make sure only fields in the $desc are saved into the DB
	 *
	 */
	public function filterData(array $userData): array
	{
		$data = [];
		foreach ($this->desc as $field => $_) {
			$data[$field] = ifsetor($userData[$field]);
		}

		return $data;
	}

}
