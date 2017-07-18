<?php

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
	 * @var
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

	function __construct(array $desc = array(), $prefix = '', $fieldset = '') {
		$this->desc = $desc;
		$this->prefix($prefix);
		$this->request = Request::getInstance();
		if ($this->desc) {
			// todo: does not get correct values OR values at all!
			$this->importValues($this->request->getSubRequestByPath($this->prefix));
			//$this->showForm();	// call manually to have a chance to change method or defaultBR
		}
		if ($fieldset) {
			$this->fieldset($fieldset);
		}
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
			if ($desc instanceof HTMLFormTable) {
				$prefix_1 = $desc->prefix;
				array_shift($prefix_1);
				$subForm = $form->getSubRequestByPath($prefix_1);
				nodebug('subimport', sizeof($form->getAll()), implode(', ', array_keys($form->getAll())),
					$desc->prefix, $prefix_1, sizeof($subForm->getAll()), implode(', ', $subForm->getAll()));
				$desc->importValues($subForm);
				//debug('after', $desc->desc);
			} else if ($desc['type'] instanceof HTMLFormDatePicker) {
				$val = $form->getTrim($key);
				$desc['value'] = $desc['type']->getISODate($val);
				//debug(__METHOD__, $val, $desc['value']);
			} else if ($form->is_set($key)) {
				$desc['value'] = $form->getTrim($key);
			} // else keep default ['value']
		}
	}

	function switchType($fieldName, $fieldValue, array $desc) {
		if (isset($desc['prefix']) && $desc['prefix']) {
			$this->text($desc['prefix']);
		}
		if (!$desc['id']) {
			//$elementID = uniqid('id_');
			$elementID = implode('_', (array)$this->prefix)
				.'_'.implode('_', (array)$fieldName);
			$desc['id'] = $elementID;
		} else {
			$elementID = $desc['id'];
		}
		$type = $desc['type']; /* @var $type Collection */
		if ($type instanceof HTMLFormType) {
			$type->setField($fieldName);
			$type->setForm($this);
			$type->setValue($desc['value']);
			$type->jsParams = $desc['jsParams'] ? $desc['jsParams'] : array();
			$type->desc = $desc;
			$this->stdout .= $type->render();
		} else if ($type instanceof Collection) {
			$type->setField($fieldName);
			$type->setForm($this);
			$type->setValue($desc['value']);
			$type->desc = $desc;
			$this->stdout .= $type->renderHTMLForm();
		} else {
			switch($type) {
				case "text":
				case "string":
					$this->text($fieldValue);
				break;
				case "textarea":
					$this->textarea($fieldName, $fieldValue,
						(is_array($desc['more'])
							? HTMLForm::getAttrHTML($desc['more'])
							: $desc['more']
						).
						($desc['id'] ? ' id="'.$desc['id'].'"' : '').
						($desc['disabled'] ? ' disabled="1"' : '').
						($desc['class'] ? ' class="'.htmlspecialchars($desc['class'], ENT_QUOTES).'"' : '')
					);
				break;
				case "date":
					//t3lib_div::debug(array($fieldName, $fieldValue));
					$this->date($fieldName, $fieldValue, $desc);
				break;
				case "datepopup":
					$this->datepopup($fieldName, $fieldValue);
				break;
				case "money":
					$this->money($fieldName, $fieldValue, $desc);
				break;
				case "select":
				case "selection":
					$options = $this->fetchSelectionOptions($desc);
					$this->selection($fieldName, $options,
						isset($fieldValue) ? $fieldValue : $desc['default'],
						isset($desc['autosubmit']) ? $desc['autosubmit'] : NULL, '',
						isset($desc['multiple']) ? $desc['multiple'] : NULL,
						$desc);
				break;
				case "file":
					$this->file($fieldName, $desc);
				break;
				case "password":
					$this->password($fieldName, $fieldValue, $desc);
				break;
				case "check":
				case "checkbox":
					if ($desc['set0']) {
						$this->hidden($fieldName, 0);
					}
					$this->check($fieldName, 1, $fieldValue, /*$desc['postLabel'], $desc['urlValue'], '', FALSE,*/ $desc['more'].' id="'.$elementID.'"');
				break;
				case "time":
					$this->time($fieldName, $fieldValue, $desc['unlimited']);
				break;
				case "hidden":
				case "hide":
					$this->hidden($fieldName, $fieldValue, ($desc['id'] ? ' id="'.$desc['id'].'"' : ''));
				break;
				case 'hiddenArray':
					$name = is_array($fieldName) ? end($fieldName) : $fieldName;
					$this->formHideArray(array($name => $fieldValue));
				break;
				case 'html':
					$this->text($desc['code']);
				break;
				case 'tree':
					$this->tree($fieldName, $desc['tree'], $fieldValue);
				break;
				case 'popuptree':
					$this->popuptree($fieldName, $desc['value'], $desc['valueName'], $desc);
				break;
				case 'submit':
					$desc['name'] = $desc['name'] ? $desc['name'] : $fieldName;
					//debug($desc);
					$this->submit($desc['value'], $desc);
				break;
				case 'ajaxTreeInput':
					//debug($this->getName($fieldName, '', TRUE));
					$this->ajaxTreeInput($fieldName, $desc['value'], $desc);
				break;
				case 'captcha':
					$this->captcha($fieldName, $fieldValue, $desc);
				break;
				case 'recaptcha':
					$this->recaptcha($desc + array('name' => $this->getName($fieldName, '', TRUE)));
				break;
				case 'recaptchaAjax':
					$this->recaptchaAjax($desc + array('name' => $this->getName($fieldName, '', TRUE)));
				break;
				case 'datatable':
					$this->datatable($fieldName, $fieldValue, $desc, FALSE, $doDiv = TRUE, 'htmlftable');
				break;
				case 'ajaxSingleChoice':
					$this->ajaxSingleChoice($fieldName, $fieldValue, $desc);
				break;
				case 'set':
					$this->set($fieldName, $fieldValue, $desc);
				break;
				case 'checkarray':
					if (!is_array($fieldValue)) {
						debug($fieldName, $fieldValue, $desc);
					}
					$this->checkarray($fieldName, $desc['set'], $fieldValue, $desc);
				break;
				case 'radioset':
					$this->radioset($fieldName, $fieldValue, $desc);
				break;
				case 'radiolist':
					$this->radioArray($fieldName, $desc['options'], $fieldValue, $desc);
				break;
				case 'combo':
					$this->combo($fieldName, $desc);
				break;
				case 'button':
					$this->button($desc['innerHTML'], $desc['more']);
				break;
				case 'fieldset':
					//$this->fieldset($desc['label']);	// it only sets the global fieldset name
					$this->stdout .= '<fieldset><legend>'.htmlspecialchars($desc['label']).'</legend>';
				break;
				case '/fieldset':
					$this->stdout .= '</fieldset>';
				break;
				case 'email':
					$type = 'email';
				//break;	// intentional
				case "input":
				default:
					$type = isset($type) ? $type : 'text';
					//$this->text(htmlspecialchars($desc['more']));
					$this->input($fieldName, $fieldValue,
						(is_array($desc['more'])
							? HTMLForm::getAttrHTML($desc['more'])
							: '') .
						(($desc['more'] && !is_array($desc['more']))
							? $desc['more']
							: '') .
						($desc['id'] ? ' id="'.$desc['id'].'"' : '') .
						($desc['size'] ? ' size="'.$desc['size'].'"' : '') .
	//					($desc['cursor'] ? " id='$elementID'" : "") .
						($desc['readonly'] ? ' readonly="readonly"' : '').
						($desc['disabled'] ? ' disabled="1"' : '').
						($desc['autofocus'] ? ' autofocus' : '')
						, $type, $desc['class']
					);
				break;
			}
		}
		return $elementID;
	}

	function showCell($fieldName, array $desc) {
		//t3lib_div::debug(array($fieldName, $desc));
		$desc['TDmore'] = (isset($desc['TDmore']) && is_array($desc['TDmore']))
			? $desc['TDmore']
			: array();
		if ($desc['newTD']) {
			$this->stdout .= '</tr></table></td>
			<td '.$desc['TDmore'].'><table class="htmlFormTable"><tr>';
		}
		$fieldValue = $desc['value'];
		$type = $desc['type'];

		if (is_object($type) || ($type != 'hidden' && !in_array($type, array('fieldset', '/fieldset')))) {
			if (!$desc['formHide']) {
				if ($desc['br'] || $this->defaultBR) {
				} else {
					$desc['TDmore']['class'] = isset($desc['TDmore']['class']) ? $desc['TDmore']['class'] : '';
					$desc['TDmore']['class'] .= ' tdlabel';
				}
				$this->stdout .= '<td '.$this->getAttrHTML($desc['TDmore']).'>';
				if ($this->withValue) {
					$fieldName[] = 'value';
				}

				$tmp = $this->stdout;
				$elementID = $this->switchType($fieldName, $fieldValue, $desc);
				$newContent = substr($this->stdout, strlen($tmp));
				$this->stdout = $tmp;

				$withBR = ($desc['br'] === NULL && $this->defaultBR) || $desc['br'];
				if (isset($desc['label'])) {
					$label = $desc['label'];
					if (!$withBR) {
						if ($desc['label']) {
							$label .= ':&nbsp;';
							if (!$desc['optional'] &&
								!in_array($type, array('check', 'checkbox'))) {
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
							$label .= ($desc['explanationgif']) ? $desc['explanationgif'] : '';
							$label .= $this->debug ? '<br><font color="gray">'.$this->getName($fieldName, '', true).'</font>' : '';
						}
					}
					$this->stdout .= '<label for="'.$elementID.'">'.$label.'</label>';
					if ($withBR) {
						$this->stdout .= '<br />';	// depends on CSS
					} else {
						$this->stdout .= '</td><td>';
					}
				}
				if (isset($desc['error'])) {
					//debug($fieldName, $desc);
					//debug_pre_print_backtrace();
					$desc['class'] .= ' error';
				}

				if ($desc['wrap'] instanceof Wrap) {
					$newContent = $desc['wrap']->wrap($newContent);
				}

				$this->stdout .= (isset($desc['prepend']) ? $desc['prepend'] : '')
					.$newContent.
					(isset($desc['append']) ? $desc['append'] : '');

				if ($desc['cursor']) {
					$this->stdout .= "<script>
						<!--
							var obj = document.getElementById('{$elementID}');
							if (obj) obj.focus();
						-->
					</script>";
				}
				if ($desc['error']) {
					$this->stdout .= '<div id="errorContainer['.$this->getName($fieldName, '', TRUE).']"
					class="error ui-state-error alert-error alert-danger">';
					$this->stdout .= $desc['error'];
					$this->stdout .= '</div>';
				}
				if ($desc['newTD']) {
					$this->stdout .= '</td>';
				}
			}
		} else {
			$this->switchType($fieldName, $fieldValue, $desc);
		}
	}

	function showRow($fieldName, array $desc2) {
		$stdout = '';
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

	function mainFormStart() {
		$this->stdout .= '<table class="htmlFormDiv"><tr><td>';
	}

	function mainFormEnd() {
		$this->stdout .= "</td></tr></table>";
	}

	/**
	 * @param array $formData	@deprecated - use __construct() instead
	 * @param array $prefix
	 * @param bool $mainForm
	 * @param string $append
	 */
	function showForm(array $formData = NULL, $prefix = array(), $mainForm = TRUE, $append = '') {
		$this->stdout .= $this->getForm($formData ? $formData : $this->desc, $prefix, $mainForm, $append);
	}

	function getForm(array $formData, array $prefix = array(), $mainForm = TRUE, $append = '') {
		if (!is_array($formData)) {
			debug_pre_print_backtrace();
		}
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
		$tableMore = $this->tableMore;
		$tableMore['class'] = (isset($tableMore['class']) ? $tableMore['class'] : '') . " htmlFormTable";
		$this->stdout .= '<table '.$this->getAttrHTML($tableMore).'>';
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
			if ($sType == 'HTMLFormTable') {
				$subForm = $fieldDesc; /** @var $subForm HTMLFormTable */
				$subForm->showForm();
				$this->stdout .= '<tr><td colspan="2">'.$subForm->getBuffer().'</td></tr>';
			} else if (is_array($fieldDesc) && !in_array($sType, array('hidden', 'hiddenArray'))) {
				if (!isset($fieldDesc['horisontal']) || !$fieldDesc['horisontal']) {
					$this->stdout .= "<tr ".$this->getAttrHTML($fieldDesc['TRmore']).">";
				}

				if ($fieldDesc['table']) {
					$this->stdout .= '<td>';
					$this->showForm($fieldDesc, $path, FALSE);
					$this->stdout .= "</td>";
				}
				if ($fieldDesc['dependant']) {
					$fieldDesc['prepend'] = '<fieldset class="expandable"><legend>';
					$fieldDesc['append'] .= '</legend>'.
						$this->getForm($fieldDesc['dependant'], $prefix, FALSE) // $path
					.'</fieldset>';
					$this->showCell($path, $fieldDesc);
				} else if ($fieldDesc['horisontal']) {
					$this->showRow($path, $fieldDesc);
				} else {
					$this->showCell($path, $fieldDesc);
				}

				if (!$fieldDesc['horisontal']) {
					$this->stdout .= "</tr>";
				}
			} else if (in_array($sType, array('hidden', 'hiddenArray'))) { // hidden
				//debug(array($formData, $path, $fieldDesc));
				$this->showCell($path, $fieldDesc);
			}
		}
		$part = $this->stdout;
		$this->stdout = $tmp;
		return $part;
	}

	/**
	 * Deprecated. Used to retrieve name/values pairs from the array with $this->withValues = FALSE.
	 *
	 * @param array		Form description array
	 * @param string	Column name that contains values. Within this class default value is the only that makes sence.
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
	 * @param array $desc
	 * @param array $form Structure of the form.
	 * @internal param \Values $array from $_REQUEST.
	 * @return array    Processed $form.
	 */
	function acquireValues(array $desc, $form = array()) {
		foreach ($desc as $field => $params) {
			if ($params['type'] == 'datepopup')	{
				$date = strtotime($form[$field]);
				debug(__METHOD__, $field, $form[$field], $date);
				if ($date) {
					$form[$field] = $date;
				}
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
	 * @param	array	Structure of the HTMLFormTable
	 * @param	array	Values in one of the supported formats.
	 * @param	boolean	??? what's for?
	 * @return	array	HTMLFormTable structure.
	 * @deprecated in favor of fill()
	 */
	function fillValues(array $desc, array $assoc = NULL, $forceInsert = false) {
		foreach ($assoc as $key => $val) {
			if (is_array($desc[$key]) || $forceInsert) {
				if (is_array($val) && $this->withValue) {
					$desc[$key]['value'] = $val['value'];
				} else {
					$desc[$key]['value'] = $val;
				}

				$sType = is_object($desc[$key]['type'])
					? get_class($desc[$key]['type'])
					: $desc[$key]['type'];
				switch ($sType) {
					case 'date':
						if (is_numeric($desc[$key]['value']) && $desc[$key]['value']) {
							$desc[$key]['value'] = $this->formatDate($desc[$key]['value'], $desc[$key]);
						}
					break;
				}

				if ($desc[$key]['dependant']) {
					$desc[$key]['dependant'] = $this->fillValues($desc[$key]['dependant'], $assoc);
					//t3lib_div::debug($desc[$key]['dependant']);
				}
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
		return $this->desc = $this->fillValues($this->desc, $assoc, $forceInsert);
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

	static function getSingle($fieldName, array $desc) {
		$f = new self();
		$f->switchType($fieldName, $desc['value'], $desc);
		return $f->getBuffer();
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

	/**
	 * Retrieves data from DB
	 * Provide either 'options' assoc array
	 * OR a DB 'table', 'title' column, 'idField' column 'where' and 'order'
	 * @param array $desc
	 * @return array
	 */
	static function fetchSelectionOptions(array $desc) {
		if ($desc['from'] && $desc['title']) {
			//debugster($desc);
			$options = Config::getInstance()->qb->getTableOptions($desc['from'],
				$desc['title'],
				isset($desc['where']) 	? $desc['where'] : array(),
				isset($desc['order']) 	? $desc['order'] : '',
				isset($desc['idField']) ? $desc['idField'] : 'id'
				//$desc['noDeleted']
			);
		} else {
			$options = array();
		}
		if (isset($desc['options'])) {
			$options += $desc['options'];
		}
		if (isset($desc['null'])) {
			$options = array(NULL => "---") + $options;
		}
		//Debug::debug_args($options, $desc['options']);
		return $options;
	}

	function validate() {
		$this->validator = new HTMLFormValidate($this);
        $this->isValid = $this->validator->validate();
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

}
