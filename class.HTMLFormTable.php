<?php

class HTMLFormTable extends HTMLForm {
	var $withValue = FALSE;
	var $defaultBR = FALSE;
	var $trmore;
	var $tableMore;

	/**
	 *
	 * @var array
	 */
	protected $desc;

	function setDesc(array $desc) {
		$this->desc = $desc;
	}

	function importValues(array $form) {
		$this->desc = $this->fillValues($this->desc, $form);
	}

	function switchType($fieldName, $fieldValue, $desc) {
		if ($desc['prefix']) {
			$this->text($desc['prefix']);
		}
		if (!$desc['id']) {
			$elementID = uniqid('id_');
			$desc['id'] = $elementID;
		}
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
				if ($desc['from'] && $desc['title']) {
					//debugster($desc);
					$options = Config::getInstance()->db->getTableOptions($desc['from'],
						$desc['title'],
						$desc['where'] . $desc['order'],
						$desc['idField'] ? $desc['idField'] : 'id',
						$desc['noDeleted']);
				} else {
					$options = array();
				}
				if ($desc['options']) {
					$options += $desc['options'];
				}
				if ($desc['null']) {
					$options = array(NULL => "---") + $options;
				}
				$this->selection($fieldName, $options,
					$fieldValue ? $fieldValue : $desc['default'],
					$desc['autosubmit'],
					($desc['size'] ? 'size="'.$desc['size'].'"' : '') . $desc['more'],
					$desc['multiple'], $desc);
			break;
			case "file":
				$this->file($fieldName);
			break;
			case "password":
				$this->password($fieldName, $fieldValue);
			break;
			case "check":
			case "checkbox":
				$this->check($fieldName, $fieldValue, /*$desc['postLabel'], $desc['urlValue'], '', FALSE,*/ $desc['more']);
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
				$this->submit($desc['value']);
			break;
			case 'ajaxTreeInput':
				//debug($this->getName($fieldName, '', TRUE));
				$this->ajaxTreeInput($fieldName, $desc['tree']);
			break;
			case 'captcha':
				$this->captcha($fieldName, $fieldValue, $desc);
			break;
			case 'datatable':
				$this->datatable($fieldName, $fieldValue, $desc, FALSE, $doDiv = TRUE, 'htmlftable');
			break;
			case 'ajaxSingleChoice':
				$this->ajaxSingleChoice($fieldName, $fieldValue, $desc);
			break;
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
		if ($desc['append'] && $desc['type'] != 'hidden') {
			$this->text($desc['append']);
		}
		return $elementID;
	}

	function showCell($fieldName, array $desc) {
		//t3lib_div::debug(array($fieldName, $desc));
		if ($desc['newTD']) {
			$this->stdout .= '</tr></table></td>   <td '.$desc['TDmore'].'><table class="htmlFormTable"><tr>';
		}
		$fieldValue = $desc['value'];

		if ($desc['type'] != 'hidden') {
			$this->stdout .= '<td '.$desc['TDmore'].($desc['br'] || $this->defaultBR ? '' : ' class="label"').'>';
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
					$this->stdout .= '<br>';
				} else {
					if ($desc['label']) {
						$this->stdout .= ': '.(!$desc['optional'] ? '<span class="htmlFormTableStar">*</span>' : '');
						$this->stdout .= ($desc['explanationgif']) ? $desc['explanationgif'] : '';
					}
					$this->stdout .= '</td><td>';
				}
				$this->stdout .= '</label>';
			}
			if ($desc['error']) {
				//debug($fieldName, $desc);
				//print '<pre>'.debug_print_backtrace().'</pre>';
				$desc['class'] .= ' error';
			}

			$this->stdout .= $newContent;

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
			//$this->stdout .= '-e: '.$desc['error'].'-';
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

	function showRow($fieldName, $desc) {
		foreach($desc as $fieldName2 => $desc2) {
			if ($fieldName2 != 'horisontal') {
				$stdout .= "<td {$desc['TDmore']}>";
				$path = $fieldName;
				$path[] = $fieldName2;
				$this->showCell($path, $desc2);
				$stdout .= "</td>";
			}
		}
	}

	function mainFormStart() {
		$this->stdout .= '<table class="htmlFormDiv"><tr><td>';
	}

	function mainFormEnd() {
		$this->stdout .= "</td></tr></table>";
	}

	function showForm(array $formData = NULL, $prefix = array(), $mainForm = TRUE, $append = '') {
		$formData = $formData ? $formData : $this->desc;
		if ($this->mainForm) {
			$this->mainFormStart();
		}
		if ($this->fieldset) {
			$this->stdout .= "<fieldset><legend>".$this->fieldset."</legend>";
			$startedFieldset = TRUE;
			$this->fieldset = NULL;
		}
		$this->stdout .= '<table class="htmlFormTable" '.$this->tableMore.'>';
		foreach ($formData as $fieldName => $fieldDesc) {
			$path = is_array($prefix) ? $prefix : ($prefix ? $prefix : NULL);
			$fnp = strpos($fieldName, '[');
			if ($fnp !== FALSE) {
				$path[] = substr($fieldName, 0, $fnp);
				$path[] = substr($fieldName, $fnp+1, -1);
			} else {
				$path[] = $fieldName;
			}

			if (is_array($fieldDesc) && $fieldDesc['type'] != 'hidden') {
				$this->stdout .= "<tr".($fieldDesc['TRclass'] ? ' class="'.$fieldDesc['TRclass'].'"' : '').' '.$fieldDesc['TRmore'].">";

				if ($fieldDesc['table']) {
					$this->stdout .= '<td>';
					$this->showForm($fieldDesc, $path, FALSE);
					$this->stdout .= "</td>";
				} else if ($fieldDesc['horisontal']) {
					$this->showRow($path, $fieldDesc);
				} else {
					$this->showCell($path, $fieldDesc);
				}

				$this->stdout .= "</tr>";
			} else {
				$this->showCell($path, $fieldDesc);
			}
		}
		$this->stdout .= "</table>".$append;
		if ($startedFieldset) {
			$this->stdout .= "</fieldset>";
		}
		if ($this->mainForm) {
			$this->mainFormEnd();
		}
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
	function acquireValues($desc, $form) {
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
	 * @return	array	HTMLFormTable structure.
	 */

	function fillValues($desc, $assoc, $forceInsert = false) {
		if (is_array($assoc)) {
			foreach ($assoc as $key => $val) {
				if (is_array($desc[$key]) || $forceInsert) {
					if (is_array($val)) {
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
				}
			}
		} else {
//			$GLOBALS['error']->runtime('HTMLFormTable::fillValues second parameter not array.');
		}
		return $desc;
	}

	static function getForm(array $desc) {
		$f = new self();
		$f->showForm($desc);
		return $f->getBuffer();
	}

	static function getSingle($fieldName, array $desc) {
		$f = new self();
		$f->switchType($fieldName, $desc['value'], $desc);
		return $f->getBuffer();
	}

}
