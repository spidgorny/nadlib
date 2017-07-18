<?php

class slTableValue {

	/**
	 * @var mixed
	 */
	var $value = NULL;

	/**
	 * @var array
	 */
	var $desc = array(
//		'hsc' => TRUE,
	);

	/**
	 * @var MySQL|dbLayer
	 */
	var $db;

	//public $SLTABLE_IMG_CHECK = '<img src="img/check.png">';
	public $SLTABLE_IMG_CHECK = '☑';
	//public $SLTABLE_IMG_CROSS = '<img src="img/uncheck.png">';
	public $SLTABLE_IMG_CROSS = '☐';

	function __construct($value, array $desc = array()) {
		if ($value instanceof slTableValue) {
			$value = $value->value;
			//debugster(array($value, $value->desc, '+', $desc, '=', (array)$value->desc + $desc));
			$desc = (array)$value->desc + $desc; // overwriting in a correct way
		}
		$this->value = $value;
		$this->desc += (array)$desc;
		if (class_exists('Config')) {
			$this->db = Config::getInstance()->db;
		}
	}

/*	function render() {
		$value = $this->value;
		if (is_array($value)) {
			$value = t3lib_utility_Debug::viewArray($value);
		} else {
			if ($this->desc['hsc']) {
				$value = htmlspecialchars($value);
			}
		}
		return $value.' ('.$this->desc['type'].')';
	}
*/

	function render($col = NULL, array $row = array()) {
		$content = $this->getCell($col, $this->value, $this->desc, $row);
		return $content;
	}

	function __toString() {
		return $this->render();
	}

	function getCell($col, $val, $k, array $row) {
		$type = isset($k['type']) ? $k['type'] : NULL;
		if (is_object($type)) {
			$type = get_class($type);
		}
		switch ($type) {
			case "select":
			case 'ajaxSingleChoice':
			case "selection":
				//debug($k + array('val' => $val));
				if ($val) {
					$what = $k['title'] ? $k['title'] : $col;
					$id = $k['idField'] ? $k['idField'] : 'id';
					if (!isset($k['options'])) {
						if ($k['set']) {
							$list = trimExplode(',', $val);
							$out = array();
							foreach ($list as $val) {
								$out[] = $this->db->sqlFind($what, $k['from'], $id." = '".$val."'", FALSE);
							}
							$out = implode(', ', $out);
						} else if ($k['from']) {
							$options = $this->db->fetchSelectQuery($k['from'], array($id => $val), '', $k['from'].'.*, '.$what);
							//debug($options, $k); exit();
							$whatAs = trimExplode('AS', $what);
							$whatAs = $whatAs[1] ?: $what;
							$options = ArrayPlus::create($options)
								->IDalize($id, true)
								->column($whatAs)
								->getData();
							$out = $options[$val];
						}
					} else {
						$options = $k['options'];
						$out = $options[$val];
					}
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
			case "sqltime":
				if ($val) {
					$val = strtotime(substr($val, 0, 16)); // cut milliseconds
					$out = date($k['format'], $val);
				} else {
					$out = '';
				}
			break;
			case "sqldate":
				if ($val) {
					$val = new Date($val);
					$out = $val->format($k['format']);	// hours will not work
				} else {
					$out = '';
				}
			break;
			case "file":
				$out = new HTMLTag('a', array(
					'href' => $GLOBALS['uploadURL'].$val,
				), $val);
			break;
			case "money":
				$out = number_format($val, 2, '.', '') . "&nbsp;&euro;";
			break;
			case "delete":
				$out = new HTMLTag('a', array(
					'href' => "?perform[do]=delete&perform[table]={$this->ID}&perform[id]=".$row['id'],
				), "Del");
			break;
			case "datatable":
				//$out .= t3lib_utility_Debug::viewArray(array('col' => $col, 'val' => $val, 'desc' => $k));
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
				if ($k['tf']) {
					$val = $val == 't';
				}
				if ($val) {
					$img = $this->SLTABLE_IMG_CHECK;
				} else {
					$img = $this->SLTABLE_IMG_CROSS;
				}
				if ($row[$col.'.link']) {
					$out = new HTMLTag('a', array(
						'href' => $row[$col.'.link'],
					), $img);
				} else {
					$out = $img;
				}
			break;
			case "bool":
			case "boolean":
				if (intval($val)) {
					$out = $k['true'] ?: $this->SLTABLE_IMG_CHECK;
				} else {
					$out = $k['false'] ?: $this->SLTABLE_IMG_CROSS;
				}
				//$out .= t3lib_utility_Debug::viewArray(array('val' => $val, 'k' => $k, 'out' => $out));
			break;
			case "excel":
				$out = str_replace(',', '.', $val); // from excel?
				$out = number_format($out, 2, ',', '.');
			break;
			case 'check':
				$out = '<div style="align: center;">
					<input class="check" type="checkbox" disabled="" '.($val ? 'checked' : '').' />
				</div>';
			break;
			case "percent":
				$out = number_format($val*100, 2, '.', '').'&nbsp;%';
			break;
			case "bar":
				if (!is_null($val)) {
					$pb = new ProgressBar();
					if (isset($k['css'])) {
						$out = $pb->getImage($val*100, $k['css']);
					} else {
						$out = $pb->getImage($val*100);
					}
				}
			break;
			case "callback":
				$out = call_user_func($k['callback'], $val, $k, $row);
			break;
			case "instance":
				$obj = is_object($k['class']) ? $k['class'] : new $k['class']($val);
				$out = $obj.'';
			break;
			case "singleLink":
				$out = new HTMLTag('a', array(
					'href' => new URL($k['link'].$row[$k['idField']]),
				), $val);
			break;
			case 'HTMLFormDatePicker':
				//$val = strtotime($val);
				//$out = date($k['type']->format, $val);
				if ($val) {
					$val = new Date($val);
					$out = $val->format($k['type']->format);
				}
			break;
			case "textarea":
				$val = nl2br($val);
			//break; // FALL DOWN!
			default:
				if ($val instanceof slTableValue) {
					$out = $val->render();
				} else {
					//t3lib_div::debug($k);
					if (isset($k['hsc']) && $k['hsc']) {
						$val = htmlspecialchars($val);
					}
					if (isset($k['nl2br']) && $k['nl2br']) {
						$val = nl2br($val);
						$k['no_hsc'] = true; 	// for below
					}
					if (is_object($val)) {
						if (method_exists($val, 'getName')) {
							$val = $val->getName();
						}
					}
					if ($k['no_hsc']) {
						$out = $val;
					} else if ($val instanceof htmlString) {
						$out = $val.'';
					} else if ($val instanceof HTMLTag) {
						$out = $val.'';
					} else if ($val instanceof HTMLDate) {
						$out = $val.'';
					} elseif (is_array($val)) {
						if (is_assoc($val)) {
							$out = json_encode($val, JSON_PRETTY_PRINT);
						} else {
							$out = '['.implode(', ', $val).']';
						}
						$out = htmlspecialchars($out);
					} else {
						$out = htmlspecialchars($val);
					}
				}
			break;
		}
		if ($k['wrap']) {
			$wrap = $k['wrap'] instanceof Wrap ? $k['wrap'] : new Wrap($k['wrap']);
			$out = $wrap->wrap($out);
		}
		if ($k['link']) {
			$out = '<a href="'.$k['link'].'">'.$out.'</a>';
		}
		if (isset($k['round']) && $out) {
			$out = number_format($out, $k['round'], '.', '');
		}
		return $out;
	}

}
