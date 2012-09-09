<?php

class HTMLFormTable extends HTMLForm {
	var $withValue = FALSE;
	var $defaultBR = FALSE;
	var $trmore;
	var $tableMore;
	public $debug = false;
	/**
	 *
	 * @var array
	 */
	protected $desc;

	protected $mainForm;

	function __construct(array $desc = array(), $prefix = '') {
		$this->desc = $desc;
		$this->prefix($prefix);
		if ($this->desc) {
			$r = new Request();
			$this->desc = $this->fillValues($this->desc, $r->getArray($this->prefix[0]));
			//$this->showForm();	// call manually to have a chance to change method or defaultBR
		}
	}

	function setDesc(array $desc) {
		$this->desc = $desc;
	}

	function importValues(array $form) {
		$this->desc = $this->fillValues($this->desc, $form);
	}

	function switchType($fieldName, $fieldValue, $desc) {
		if (isset($desc['prefix']) && $desc['prefix']) {
			$this->text($desc['prefix']);
		}
		if (!$desc['id']) {
			$elementID = uniqid('id_');
			$desc['id'] = $elementID;
		}
		if ($desc['type'] instanceof HTMLFormType) {
			$desc['type']->setForm($this);
			$desc['type']->setValue($desc['value']);
			$this->stdout .= $desc['type']->render();
		} else {
			switch($desc['type']) {
				case "text":
				case "string":
					$this->text($fieldValue);
				break;
				case "textarea":
					$this->textarea($fieldName, $fieldValue, $desc['more'].($desc['id'] ? ' id="'.$desc['id'].'"' : ''));
				break;
				case "date":
					//t3lib_div::debug(array($fieldName, $fieldValue));
					$this->date($fieldName, $fieldValue, $desc);
				break;
				case "datepopup":
					$this->datepopup($fieldName, $fieldValue);
				break;
				case "money":
					$this->money($fieldName, $fieldValue);
				break;
				case "select":
				case "selection":
					$options = $this->getSelectionOptions($desc);
					$this->selection($fieldName, $options,
						isset($fieldValue) ? $fieldValue : $desc['default'],
						isset($desc['autosubmit']) ? $desc['autosubmit'] : NULL,
						(isset($desc['size']) ? 'size="'.$desc['size'].'"' : '') .
						(isset($desc['more']) ? $desc['more'] : ''),
						isset($desc['multiple']) ? $desc['multiple'] : NULL,
						$desc);
				break;
				case "file":
					$this->file($fieldName, $desc);
				break;
				case "password":
					$this->password($fieldName, $fieldValue);
				break;
				case "check":
				case "checkbox":
					if ($desc['set0']) {
						$this->hidden($fieldName, 0);
					}
					$this->check($fieldName, 1, $fieldValue, /*$desc['postLabel'], $desc['urlValue'], '', FALSE,*/ $desc['more']);
				break;
				case "time":
					$this->time($fieldName, $fieldValue, $desc['unlimited']);
				break;
				case "hidden":
				case "hide":
					$this->hidden($fieldName, $fieldValue, ($desc['id'] ? ' id="'.$desc['id'].'"' : ''));
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
					$this->submit($desc['value'], $desc['more'], $desc);
				break;
				case 'ajaxTreeInput':
					//debug($this->getName($fieldName, '', TRUE));
					$this->ajaxTreeInput($fieldName, $desc['tree']);
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
				case 'radioset':
					$this->radioset($fieldName, $fieldValue, $desc);
				break;
				case 'combo':
					$this->combo($fieldName, $desc);
				case 'button':
					$this->button($desc['innerHTML'], $desc['more']);
				break;
				case "input":
				default:
					//$this->text(htmlspecialchars($desc['more']));
					$this->input($fieldName, $fieldValue,
						($desc['more'] ? $desc['more'] : '') .
						($desc['id'] ? ' id="'.$desc['id'].'"' : '') .
						($desc['size'] ? ' size="'.$desc['size'].'"' : '') .
	//					($desc['cursor'] ? " id='$elementID'" : "") .
						($desc['readonly'] ? ' readonly="readonly"' : ''),
						$desc['class']
					);
				break;
			}
		}
/*
 *		Is dealt with in showCell already
 *		if ($desc['append'] && $desc['type'] != 'hidden') {
			$this->text($desc['append']);
		}
*/		return $elementID;
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

		if ($desc['type'] != 'hidden') {
			if ($desc['br'] || $this->defaultBR) {
			} else {
				$desc['TDmore']['class'] = isset($desc['TDmore']['class']) ? $desc['TDmore']['class'] : '';
				$desc['TDmore']['class'] .= ' label';
			}
			$this->stdout .= '<td '.$this->getAttrHTML($desc['TDmore']).'>';
			if ($this->withValue) {
				$fieldName[] = 'value';
			}


			$tmp = $this->stdout;
			$elementID = $this->switchType($fieldName, $fieldValue, $desc);
			$newContent = substr($this->stdout, strlen($tmp));
			$this->stdout = $tmp;


			if (isset($desc['label'])) {
				$this->stdout .= '<label for="'.$elementID.'">'.$desc['label'];
				if (($desc['br'] === NULL && $this->defaultBR) || $desc['br']) {
					$this->stdout .= '<br />';
				} else {
					if ($desc['label']) {
						$this->stdout .= ':&nbsp;'.(!$desc['optional'] && $desc['type'] != 'check' ? '<span class="htmlFormTableStar">*</span>' : '');
						$this->stdout .= ($desc['explanationgif']) ? $desc['explanationgif'] : '';
						$this->stdout .= $this->debug ? '<br><font color="gray">'.$this->getName($fieldName, '', true).'</font>' : '';
					}
					$this->stdout .= '</td><td>';
				}
				$this->stdout .= '</label>';
			}
			if (isset($desc['error'])) {
				//debug($fieldName, $desc);
				//print '<pre>'.debug_print_backtrace().'</pre>';
				$desc['class'] .= ' error';
			}

			$this->stdout .= (isset($desc['prepend']) ? $desc['prepend'] : '')
				.$newContent.
				(isset($desc['append']) ? $desc['append'] : '');

			if ($desc['cursor']) {
				$this->stdout .= "<script>
	<!--
		isOpera = navigator.userAgent.indexOf('Opera') != -1;
		var obj;
		if (isOpera) {
			obj = document.all.{$elementID};
		} else {
			obj = document.getElementById('{$elementID}');
		}
		obj.focus();
	-->
	</script>";
			}
			if ($desc['error']) {
				$this->stdout .= '<div id="errorContainer['.$this->getName($fieldName, '', TRUE).']" class="error">';
				$this->stdout .= $desc['error'];
				$this->stdout .= '</div>';
			}
			$this->stdout .= '</td>';
		} else {
			$elementID = $this->switchType($fieldName, $fieldValue, $desc);
		}
	}

	function showRow($fieldName, array $desc2) {
		$stdout = '';
		//foreach ($desc as $fieldName2 => $desc2) {
			if ($fieldName2 != 'horisontal') {
				$stdout .= "<td {$desc['TDmore']}>";
				$path = $fieldName;
				//$path[] = $fieldName2;
				$this->showCell($path, $desc2);
				$stdout .= "</td>";
			}
		//}
	}

	function mainFormStart() {
		$this->stdout .= '<table class="htmlFormDiv"><tr><td>';
	}

	function mainFormEnd() {
		$this->stdout .= "</td></tr></table>";
	}

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
			$this->stdout .= "<fieldset ".$this->getAttrHTML($this->fieldsetMore)."><legend>".$this->fieldset."</legend>";
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
			//debug($path);

			if (is_array($fieldDesc) && $fieldDesc['type'] != 'hidden') {
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
			} else {
				//t3lib_div::debug(array($path, $fieldDesc));
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
	 */
	function getValues($arr, $col = 'value') {
		$res = array();
		if (is_array($arr)) {
			foreach ($arr as $key => $ar) {
				if (is_array($ar)) {
					$res[$key] = $ar[$col];
				}
			}
		}
		return $res;
	}

	/**
	 * Returns the $form parameter with minimal modifications only for the special data types like time in seconds.
	 *
	 * @param array		Structure of the form.
	 * @param array		Values from $_REQUEST.
	 * @return array	Processed $form.
	 */
	function acquireValues($desc, $form = array()) {
		foreach ($desc as $field => $params) {
			if ($params['type'] == 'datepopup')	{
				$date = strtotime($form[$field]);
				if ($date) {
					$form[$field] = $date;
				}
			}
		}
		return $form;
	}

	/**
	 * Fills the $desc array with values from $assoc.
	 * Understands $assoc in both single-array way $assoc['key'] = $value and as $assoc['key']['value'] = $value.
	 *
	 * @param	array	Structure of the HTMLFormTable
	 * @param	array	Values in one of the supported formats.
	 * @param	boolean	??? what's for?
	 * @return	array	HTMLFormTable structure.
	 */

	function fillValues(array $desc, array $assoc, $forceInsert = false) {
		foreach ($assoc as $key => $val) {
			if (is_array($desc[$key]) || $forceInsert) {
				if (is_array($val) && $this->withValue) {
					$desc[$key]['value'] = $val['value'];
				} else {
					$desc[$key]['value'] = $val;
				}

				switch ($desc[$key]['type']) {
					case 'date':
						if (is_numeric($desc[$key]['value']) && $desc[$key]['value']) {
							$desc[$key]['value'] = $this->formatDate($desc[$key]['value'], $desc[$key]);
						}
					break;
				}

				if ($desc[$key]['dependant']) {
					$desc[$key]['dependant'] = HTMLFormTable::fillValues($desc[$key]['dependant'], $assoc);
					//t3lib_div::debug($desc[$key]['dependant']);
				}
			}
		}
		return $desc;
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

	function getSelectionOptions(array $desc) {
		if ($desc['from'] && $desc['title']) {
			//debugster($desc);
			$options = Config::getInstance()->db->getTableOptions($desc['from'],
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
		return $options;
	}

}
