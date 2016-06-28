<?php

/**
 * Class HTMLFormTable
 * @see HTMLFormField
 */
class HTMLFormTable extends HTMLForm {
	/**
	 * If set then each field gets ['value'] appended to it's name
	 * The idea was to merge $desc with $_REQUEST easily, but it makes ugly URL
	 * @var bool
	 */
	var $withValue = FALSE;

	/**
	 * Will render labels above the fields, otherwise on the left
	 * @var bool
	 */
	var $defaultBR = FALSE;

	/**
	 * Additional parameters for <tr>
	 * @var
	 */
	var $trmore;

	/**
	 * Additional parameters for <table>
	 * @var array
	 */
	var $tableMore;

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

	/**
	 * @var
	 */
	protected $mainForm;

	/**
	 * @var Request
	 */
	protected $request;

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

	function __construct(array $desc = array(), $prefix = array(), $fieldset = '') {
		parent::__construct();
		$this->desc = $desc;
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

	function setDesc(array $desc) {
		$this->desc = $desc;
	}

	/**
	 * fillValues() is looping over the existing values
	 * This function is looping over desc
	 * @param Request $form - Request instead of an array because of the trim() function only?
	 * @return void
	 */
	function importValues(Request $form) {
		//$this->desc = $this->fillValues($this->desc, $form);
		foreach ($this->desc as $key => &$desc) {
			// can be HTMLFormTable (@see HTMLFormValidate)
			$type = is_array($desc) ? ifsetor($desc['type'])
					: (is_object($desc) ? get_class($desc) : 'text');
			if ($desc instanceof HTMLFormTable) {
				$prefix_1 = $desc->prefix;
				array_shift($prefix_1);
				$subForm = $form->getSubRequestByPath($prefix_1);
				nodebug('subimport', sizeof($form->getAll()), implode(', ', array_keys($form->getAll())),
					$desc->prefix, $prefix_1, sizeof($subForm->getAll()), implode(', ', $subForm->getAll()));
				$desc->importValues($subForm);
				//debug('after', $desc->desc);
			} elseif ($type instanceof HTMLFormDatePicker) {
				/** @var HTMLFormDatePicker $type */
				$val = $form->getTrim($key);
				if ($val) {
					$desc['value'] = $type->getISODate($val);
					//debug(__METHOD__, $val, $desc['value']);
				}
			} elseif ($form->is_set($key)) {
				if (is_array($form->get($key))) {
					$desc['value'] = $form->getArray($key);
				} else {
					$desc['value'] = $form->getTrim($key);
				}
			} // else keep default ['value']
		}
	}

	function showLabel(HTMLFormField $desc, $fieldName) {
//		debug($desc->getArray());
		$elementID = $desc->elementID;
		$withBR = (ifsetor($desc['br']) === NULL && $this->defaultBR) || $desc['br'];
		if (isset($desc['label'])) {
			$label = $desc['label'];
			if (!$withBR) {
				if (!$desc->isCheckbox()) {
					$label .= $label ? ':&nbsp;' : '';  // don't append to "submit"
				}
				if ($desc->isObligatory()) {
					if ($this->noStarUseBold) {
						$label = '<b title="Obligatory">'.$label.'</b>';
					} else {
						$label .= '<span class="htmlFormTableStar">*</span>';
					}
				} else {
					if ($this->noStarUseBold) {
						$label = '<span title="Optional">'.$label.'</span>';
					}
				}
				$label .= ifsetor($desc['explanationgif']);
				$label .= $this->debug
					? '<br><font color="gray">'.$this->getName($fieldName, '', true).'</font>'
					: '';
			}
			$this->stdout .= ifsetor($desc['beforeLabel']);
			$this->stdout .= '<label for="'.$elementID.'">'.$label.'</label>';
			if (!$withBR) {
				$this->stdout .= '</td><td>';
			}
		}
	}

	function mainFormStart() {
		$this->stdout .= '<table class="htmlFormDiv"><tr><td>';
	}

	function mainFormEnd() {
		$this->stdout .= "</td></tr></table>";
	}

	/**
	 * @param array $formData @deprecated - use __construct() instead
	 * @param array $prefix
	 * @param bool $mainForm
	 * @param string $append
	 * @return $this
	 */
	function showForm(array $formData = NULL, $prefix = array(), $mainForm = TRUE, $append = '') {
//		echo json_encode(array_keys($this->desc)), BR;
		$this->tableMore['class'] .= $this->defaultBR ? ' defaultBR' : '';
		$this->stdout .= $this->getForm($formData ? $formData : $this->desc, $prefix, $mainForm, $append);
		return $this;
	}

	function getForm(array $formData, array $prefix = array(), $mainForm = TRUE, $append = '') {
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
			$this->stdout .= "<fieldset ".$this->getAttrHTML($this->fieldsetMore).">
				<legend>".$this->fieldset."</legend>";
			$startedFieldset = TRUE;
			$this->fieldset = NULL;
		}
		$this->stdout .= '<table '.HTMLForm::getAttrHTML($this->tableMore).'>';
		$this->stdout .= $this->renderFormRows($formData, $prefix);
		$this->stdout .= "</table>".$append;
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

	function renderFormRows(array $formData, array $prefix = array()) {
//		echo json_encode(array_keys($formData)), BR;
		$tmp = $this->stdout; 
		$this->stdout = '';
		foreach ($formData as $fieldName => $fieldDesc) {
			$path = is_array($prefix) ? $prefix : ($prefix ? $prefix : NULL);
			$fnp = strpos($fieldName, '[');
			if ($fnp !== FALSE) {
				$path[] = substr($fieldName, 0, $fnp);
				$path[] = substr($fieldName, $fnp+1, -1);
			} else {
				$path[] = $fieldName;
			}
			//debug($fieldName, $fieldDesc);
			$sType = is_object($fieldDesc)
				? get_class($fieldDesc)
				: (isset($fieldDesc['type']) ? $fieldDesc['type'] : '');
			// avoid __toString on collection
			// it needs to run twice: one checking for the whole desc and other for desc[type]
			$sType = is_object($sType)
				? get_class($sType)
				: $sType;
			//pre_print_r([$sType, is_array($fieldDesc)]);
			if ($sType == 'HTMLFormTable') {
				/** @var $subForm HTMLFormTable */
				$subForm = $fieldDesc;
				$subForm->showForm();
				$this->stdout .= '<tr><td colspan="2">'.
					$subForm->getBuffer().
				'</td></tr>';
			} elseif ($fieldDesc instanceof HTMLFormTypeInterface) {
				// this is not so good idea because we miss all the surrounding
				// information about the 'label', cell, formatting
				// even unrelated to the rendering of the form field itself
				$this->stdout .= '<tr class="'.$sType.'">';
				$copy = clone $this;
				$copy->stdout = '';
				$fieldDesc->setField($fieldName);
				$fieldDesc->setForm($copy);
				//$fieldDesc->setValue();	// value is inside the object
				$this->stdout .= $fieldDesc->render();
				$this->stdout .= '</tr>';
			} elseif (is_array($fieldDesc)
				|| $fieldDesc instanceof HTMLFormFieldInterface) {
				if (in_array($sType, array('hidden', 'hiddenArray'))) {
					// hidden are shown without table cells
					//debug(array($formData, $path, $fieldDesc));
					$this->showCell($path, $fieldDesc);
				} else {
					$this->showTR($prefix, $fieldDesc, $path);
				}
			} else {
				debug($fieldName);
				debug($fieldDesc);
				die(__METHOD__.'#'.__LINE__);
			}
		}
		$part = $this->stdout;
		$this->stdout = $tmp;
		return $part;
	}

	/**
	 * @param array $prefix
	 * @param       $fieldDesc
	 * @param       $path
	 */
	public function showTR(array $prefix, $fieldDesc, $path) {
		//debug($fieldDesc);
		if (!isset($fieldDesc['horisontal']) || !$fieldDesc['horisontal']) {
			$this->stdout .= "<tr " . $this->getAttrHTML(isset($fieldDesc['TRmore']) ? $fieldDesc['TRmore'] : NULL) . ">";
		}

		if (isset($fieldDesc['table'])) {
			$this->stdout .= '<td>';
			$this->showForm($fieldDesc, $path, FALSE);
			$this->stdout .= "</td>";
		}
		if (isset($fieldDesc['dependant'])) {
			$fieldDesc['prepend'] = '<fieldset class="expandable"><legend>';
			$fieldDesc['append'] .= '</legend>' .
				$this->getForm($fieldDesc['dependant'], $prefix, FALSE) // $path
				. '</fieldset>';
			$this->showCell($path, $fieldDesc);
		} elseif (isset($fieldDesc['horisontal'])) {
			$this->showRow($path, $fieldDesc);
		} else {
			$this->showCell($path, $fieldDesc);
		}

		if (!ifsetor($fieldDesc['horisontal'])) {
			$this->stdout .= "</tr>";
		}
	}

	function showRow($fieldName, array $desc2) {
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
	 * @param string $fieldName
	 * @param array|HTMLFormFieldInterface $desc
	 */
	function showCell($fieldName, $desc) {
//		echo __METHOD__, ' ', json_encode($fieldName), BR;
		//debug(array($fieldName, $desc));
		$desc['TDmore'] = (isset($desc['TDmore']) && is_array($desc['TDmore']))
			? $desc['TDmore']
			: array();
		if (isset($desc['newTD'])) {
			$this->stdout .= '</tr></table></td>
			<td '.$desc['TDmore'].'><table '.HTMLForm::getAttrHTML($this->tableMore).'><tr>';
		}
		$fieldValue = isset($desc['value']) ? $desc['value'] : NULL;
		$type = isset($desc['type']) ? $desc['type'] : NULL;

		if (is_object($type) || ($type != 'hidden' && !in_array($type, array('fieldset', '/fieldset')))) {
			if (empty($desc['formHide'])) {
				if (!empty($desc['br']) || $this->defaultBR) {
				} else {
					$desc['TDmore']['class'] = isset($desc['TDmore']['class']) ? $desc['TDmore']['class'] : '';
					$desc['TDmore']['class'] .= ' tdlabel';
				}
				$this->stdout .= '<td '.$this->getAttrHTML($desc['TDmore']).'>';
				if ($this->withValue) {
					$fieldName[] = 'value';
				}

				$fieldObj = $this->switchType($fieldName, $fieldValue, $desc);
				//			<<== HERE content generated
				$newContent = $fieldObj->getContent();
//				$newContent = '['.$newContent.']';

				// checkboxes are shown in front of the label
				if ($type == 'checkbox') {
					//$this->stdout .= $newContent;
					$fieldObj['label'] = $newContent . ' '
						. $fieldObj['label'];
					$newContent = '';
				}
				$this->showLabel($fieldObj, $fieldName);

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

				$this->stdout .= (isset($desc['prepend']) ? $desc['prepend'] : '')
					.$newContent.												//			<<== USED content here
					(isset($desc['append']) ? $desc['append'] : '');

				$this->stdout .= ifsetor($desc['afterLabel']);

				if (ifsetor($desc['error'])) {
					$this->stdout .= '<div id="errorContainer['.$this->getName($fieldName, '', TRUE).']"
					class="error ui-state-error alert-error alert-danger">';
					$this->stdout .= $desc['error'];
					$this->stdout .= '</div>';
				}
				if (ifsetor($desc['newTD'])) {
					$this->stdout .= '</td>';
				}
			}
		} else {
			$field = $this->switchType($fieldName, $fieldValue, $desc);
			$this->stdout .= $field->getContent();
		}
	}

	/**
	 * @param       						$fieldName
	 * @param       						$fieldValue
	 * @param array|HTMLFormFieldInterface 	$descIn
	 * @return HTMLFormField
	 */
	function switchType($fieldName, $fieldValue, $descIn) {
//		debug($descIn);
		if (!($descIn instanceof HTMLFormFieldInterface)) {
			$field = new HTMLFormField($descIn, $fieldName);
		} else {
			$field = $descIn;
			$field->setField($fieldName);
		}
		$field['value'] = $fieldValue;

		$field->form = $this;	// don't clone, because we may want to influence the original form
		$tmp = $this->stdout;
		$field->form->stdout = '';
		$field->render();
		$this->stdout = $tmp;
		return $field;
	}

	/**
	 * Deprecated. Used to retrieve name/values pairs from the array with $this->withValues = FALSE.
	 *
	 * @param array		$arr Form description array
	 * @param string	$col Column name that contains values. Within this class default value is the only that makes sense.
	 * @return array	1D array with name/values
	 * @deprecated
	 */
	function getValues(array $arr = NULL, $col = 'value') {
		$arr = $arr ? $arr : $this->desc;
		$res = array();
		if (is_array($arr)) {
			foreach ($arr as $key => $ar) {
				if (is_array($ar) && !$ar['disabled']) {
					if ($ar['type'] instanceof HTMLFormDatePicker) {
						$res[$key] = $ar['type']->getISODate($ar[$col]);
					} else {
						$res[$key] = $ar[$col];
					}
				}
			}
		}
		unset($res['xsrf']);	// is not a form value
		return $res;
	}

	/**
	 * Returns the $form parameter with minimal modifications only for the special data types like time in seconds.
	 *
	 * @param array $desc array from $_REQUEST.
	 * @param array $form Structure of the form.
	 * @return array    Processed $form.
	 */
	function acquireValues(array $desc, $form = array()) {
		foreach ($desc as $field => $params) {
			$type = ifsetor($params['type']);
			if ($type == 'datepopup')	{
				$date = strtotime($form[$field]);
				debug(__METHOD__, $field, $form[$field], $date);
				if ($date) {
					$form[$field] = $date;
				}
			} elseif (in_array($type, array('check', 'checkbox'))) {
				$form[$field] = strtolower($form[$field]) == 'on'
					|| ($form[$field] ? true : false);
			}
		}
		return $form;
	}

	/**
	 * Fills the $desc array with values from $assoc.
	 * Understands $assoc in both single-array way $assoc['key'] = $value
	 * and as $assoc['key']['value'] = $value.
	 * Non-static due to $this->withValue and $this->formatDate
	 *
	 * @param	array	$desc - Structure of the HTMLFormTable
	 * @param	array	$assoc - Values in one of the supported formats.
	 * @param	boolean	??? what's for?
	 * @return	array	HTMLFormTable structure.
	 * @deprecated in favor of fill()
	 */
	function fillValues(array $desc, array $assoc = NULL, $forceInsert = false) {
		foreach ($assoc as $key => $val) {
			//$descKey = ifsetor($desc[$key]);		// CREATES $key => NULL INDEXES

			$descKey = isset($desc[$key]) ? $desc[$key] : NULL;
			if (!$descKey) continue;

			// calc $val
			if (is_array($val) && $this->withValue) {
				$desc[$key]['value'] = $val['value'];
			} else {
				$desc[$key]['value'] = $val;
			}

			/** @var HTMLFormType|HTMLFormDatePicker $type */
			$type = ifsetor($descKey['type']);
			$sType = is_object($type)
				? get_class($type)
				: $type;
			switch ($sType) {
				case 'date':
					if (is_numeric(ifsetor($descKey['value'])) && $descKey['value']) {
						$desc[$key]['value'] = $this->formatDate($descKey['value'], $descKey);
					}
					break;
			}

			// set $val
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

	/**
	 * @param array $assoc
	 * @param bool $forceInsert
	 * @return array
	 */
	function fill(array $assoc, $forceInsert = false) {
		$this->desc = $this->fillValues($this->desc, $assoc, $forceInsert);
		return $this->debug;
	}

	/**
	 * Correct function to use outside if the desc is assigned already
	 * @param array $assoc
	 */
	function fillDesc(array $assoc) {
		$this->desc = $this->fillValues($this->desc, $assoc);
	}

	static function getQuickForm(array $desc) {
		$f = new self();
		$f->showForm($desc);
		return $f->getBuffer();
	}

	function getSingle($fieldName, array $desc) {
		$field = $this->switchType($fieldName, ifsetor($desc['value']), $desc);
		return $field->getContent();
	}

	function repostRequest(Request $r, array $prefixes = array()) {
		//debug($r);
		foreach ($r->getAll() as $key => $val) {
			if (is_array($val)) {
				$this->repostRequest(new Request($val), array_merge($prefixes, array($key)));
			} else {
				$copy = $prefixes;
				array_shift($copy);
				//debug($copy);
				$key = current($prefixes).(
					$copy
					? '['.implode('][', $copy).']'
					: ''
				).
					($prefixes ? '[' : '').
					$key.
					($prefixes ? ']' : '');
				$this->hidden($key, $val);
			}
		}
	}

	function validate() {
		$this->validator = new HTMLFormValidate($this);
        $this->isValid = $this->validator->validate();
		$this->desc = $this->validator->getDesc();
		return $this->isValid;
	}

	/**
	 * Commented as it makes double output
	 * @return string
	 */
	function __toString() {
		//$this->showForm();
		return parent::__toString();
	}

	/**
	 * Use validate() to validate.
	 * @param $class	- unique identifier of the form on the site
	 * which allows several forms to be submitted in a different order
	 * @param bool $check
	 */
	public function xsrf($class, $check = false) {
		$this->class = $class;
		if (!$check) {
			if (function_exists('openssl_random_pseudo_bytes')) {
				$token = bin2hex(openssl_random_pseudo_bytes(16));
			} else {
				$token = uniqid(php_uname('n'), true);
			}
			$this->desc['xsrf'] = array(
				'type' => 'hidden',
				'value' => $token,
			);
			$_SESSION[__CLASS__]['xsrf'][$class] = $token;
		} else {	// Check
			$this->desc['xsrf'] = array(
				'value' => '',	// use fill($this->request->getAll()) to fill in and validate()
			);
		}
	}

	public function clearValues() {
		foreach ($this->desc as &$row) {
			if (isset($row['value'])) {
				unset($row['value']);
			}
			// should not be elseif
			if ($row['type'] instanceof HTMLFormType) {
				$row['type']->setValue(NULL);
			}
		}
	}

	static function sliceFromTill(array $desc, $from, $till = NULL) {
		$desc2 = array();
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

	public function undo() {
		$this->stdout = '';
	}

	public function setAllOptional() {
		foreach ($this->desc as &$desc) {
			$desc['optional'] = true;
		}
	}

}
