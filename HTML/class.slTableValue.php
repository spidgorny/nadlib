<?php

class slTableValue {
	var $value = NULL;
	var $desc = array(
//		'hsc' => TRUE,
	);

	function __construct($value, $desc = array()) {
		if ($value instanceof slTableValue) {
			$value = $value->value;
			//debugster(array($value, $value->desc, '+', $desc, '=', (array)$value->desc + $desc));
			$desc = (array)$value->desc + $desc; // overwriting in a correct way
		}
		$this->value = $value;
		$this->desc += (array)$desc;
	}

/*	function render() {
		$value = $this->value;
		if (is_array($value)) {
			$value = t3lib_div::view_array($value);
		} else {
			if ($this->desc['hsc']) {
				$value = htmlspecialchars($value);
			}
		}
		return $value.' ('.$this->desc['type'].')';
	}
*/

	function render() {
		$content = $this->getCell('?getCell?', $this->value, $this->desc);
		return $content;
	}

	function getCell($col, $val, $k) { //print_r($k);
		switch ($k['type']) {
			case "select":
			case 'ajaxSingleChoice':
			case "selection":
				//debug($k + array('val' => $val));
				if ($val) {
					$what = $k['title'] ? $k['title'] : $col;
					$id = $k['idField'] ? $k['idField'] : 'id';
					if ($k['set']) {
						//debug($val);
						$list = explode(',', $val);
					} else {
						$list = array($val);
					}
					$out = array();
					foreach ($list as $val) {
						$out[] = $GLOBALS['dbLayer']->sqlFind($what, $k['from'], $id." = '".$val."'", FALSE);
					}
					$out = implode(', ', $out);
				} else {
					$out = "";
				}
			break;
			case "date":
				if ($val) {
					$out = date($k['format'] ? $k['format'] : 'Y-m-d H:i:s', $val);
				} else {
					$out = '';
				}
			break;
			case "sqldate":
				if ($val) {
					$val = strtotime($val);
					$out = date($k['format'], $val);
				} else {
					$out = '';
				}
			break;
			case "file":
				$out = ahref($val, $GLOBALS['uploadURL'].$val, FALSE);
			break;
			case "money":
				$out = number_format($val, 2, '.', '') . "&nbsp;&euro;";
			break;
			case "delete":
				$out = ahref("Del", "?perform[do]=delete&perform[table]={$this->ID}&perform[id]=".$row['id'], FALSE);
			break;
			case "datatable":
				//$out .= t3lib_div::view_array(array('col' => $col, 'val' => $val, 'desc' => $k));
				$out = $k['prefix'];
				$f = $this->caller->makeInstance('HTMLForm');
				$f->prefix($this->prefixId);
				$out .= $f->datatable($col, $val, $k, $details = TRUE, $doDiv = TRUE, 'sltable', $data = 'test');
				$out .= $k['append'];
			break;
			case 'link':
				$out = '<a href="'.$val.'" target="'.$k['target'].'">'.($k['text'] ? $k['text'] : $val).'</a>';
			break;
			case 'image':
				$out = '<img src="'.$k['prefix'].$val.'" />';
			break;
			case "checkbox":
				if ($val) {
					$img = SLTABLE_IMG_CHECK;
				} else {
					$img = SLTABLE_IMG_CROSS;
				}
				if ($k['link']) {
					$out = '<a href="'.$k['link'].'">'.$img.'</a>';
				} else {
					$out = $img;
				}
			break;
			case 'check':
				$out = '<div style="align: center;">
					<input class="check" type="checkbox" disabled="" '.($val ? 'checked' : '').' />
				</div>';
			break;
			case "percent":
				$out = number_format($val*100, 2, '.', '').'&nbsp;%';
			break;
			case "textarea":
				$val = nl2br($val);
			//break; // FALL DOWN!
			default:
				//if (intval($val) == 129) {debugster($val);}
				if ($val instanceof slTableValue) {
					$out = $val->render();
				} else {
					//$out = mq_stripslashes($val);
					$out = $val;
					if (!$k['no_hsc']) {
						$out = htmlspecialchars($out);
					}
				}
			break;
		}
		if ($k['wrap']) {
			$out = str_replace('|', $out, $k['wrap']);
		}
		debug($k);
		if ($k['link']) {
			$out = '<a href="'.$k['link'].'">'.$out.'</a>';
		}
		if (isset($k['round']) && $out) {
			$out = number_format($out, $k['round'], '.', '');
		}
		return $out;
	}

}
