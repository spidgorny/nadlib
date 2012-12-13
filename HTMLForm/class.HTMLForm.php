<?php

class HTMLForm {
	var $action = "";
	protected $method = "POST";
	protected $prefix = array();
	var $stdout = "";
	var $enctype = "";
	var $class = "";
	protected $fieldset;
	protected $fieldsetMore = array();
	var $formMore = '';
	var $target = '';
	public $debug = false;
	var $publickey = "6LeuPQwAAAAAADaepRj6kI13tqxU0rPaLUBtQplC";
	var $privatekey = "6LeuPQwAAAAAAAuAnYFIF-ZM9yXnkbssaF0rRtkj";

	function htmlForm($action = '') {
		$this->action = $action;
	}

	function formHideArray($name, array $ar) {
		$ret = '';
		foreach($ar as $k => $a) {
			$a = htmlspecialchars($a, ENT_QUOTES);
			$ret .= "<input type=hidden name=" . $name . ($name?"[":"") . $k . ($name?"]":"") . " value=\"$a\">";
		}
		$this->stdout .= $ret;
		return $ret;
	}

	function action($action) {
		$this->action = $action;
	}

	function method($method) {
		$this->method = $method;
	}

	function text($a) {
		$this->stdout .= $a;
	}

	function prefix($p) {
		if (is_array($p)) {
			$this->prefix = $p;
		} else if ($p) {
			$this->prefix = array($p);
		} else {
			$this->prefix = array();
		}
	}

	function cssClass($c) {
		$this->class = $c;
	}

	function fieldset($name, $more = array()) {
		$this->fieldset = $name;
		$this->fieldsetMore = $more;
	}

	function getFieldset() {
		return $this->fieldset;
	}

	function getName($name, $namePlus = '', $onlyValue = FALSE) {
		$a = '';
		$path = $this->prefix;
		$path = array_merge($path, is_array($name) ? $name : array($name));
		$first = array_shift($path);
		$a .= $first;
		if ($path) {
			$a .= "[".implode("][", $path)."]";
		}
		$a .= $namePlus;
		if (!$onlyValue) {
			$a = ' name="'.$a.'"';
		}
		return $a;
	}

	function input($name, $value = "", $more = '') {
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=\"text\" ".$this->getName($name). " $more value=\"$value\"/>\n";
	}

	function label($for, $text) {
		$this->stdout .= '<label for="'.$for.'">'.$text.'</label>';
	}

	/**
	 *
	 * Table row with $text and input
	 * @param unknown_type $text
	 * @param unknown_type $name
	 * @param unknown_type $value
	 * @param unknown_type $more
	 */
	function tinput($text, $name, $value = "", $more = '') {
		$this->text('<tr><td>'.$text.'</td><td>');
		$this->input($name, $value, $more);
		$this->text('</td></tr>');
	}

	function password($name, $value = "") {
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=\"password\" ".$this->getName($name)." value=\"$value\">\n";
	}

	function hidden($name, $value, $more = "") {
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=hidden ".$this->getName($name). " value=\"$value\" ".$more.">";
	}

	function radio($name, $value, $checked, $more = "") {
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=radio ".$this->getName($name)." value=\"$value\" ".($value==$checked?"checked":"")." $more>";
	}

	function check($name, $value = 1, $checked = false, $more = "") {
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=checkbox ".$this->getName($name)." ".($checked?"checked":"")." value=\"$value\" $more>";
	}

	function checkLabel($name, $value = 1, $checked = false, $more = "", $label = '') {
		$this->stdout .= '<label>';
		$this->check($name, $value, $checked, $more);
		$this->stdout .= ' './*htmlspecialchars*/($label).'</label>';
	}

	function radioLabel($name, $value, $checked, $label = "") {
		$value = htmlspecialchars($value, ENT_QUOTES);
		$id = $this->prefix."_".$name."_".$value;
		$this->stdout .= "<input type=radio ".$this->getName($name)." value=\"$value\" ".($checked ? "checked" : "")." id='".$id."'> ";
		$this->stdout .= "<label for=$id>".htmlspecialchars($label)."</label>";
	}

	function file($name, array $desc = array()) {
		$this->stdout .= "<input type=file ".$this->getName($name)." ".$desc['more'].">";
		$this->enctype = "multipart/form-data";
	}

	/**
	 * @param $name
	 * @param $aOptions
	 * @param $default
	 * @param bool $autoSubmit
	 * @param string $more
	 * @param bool $multiple
	 * @param array $desc
	 * @see renderSelectionOptions
	 */
	function selection($name, $aOptions, $default, $autoSubmit = FALSE, $more = '', $multiple = false, array $desc = array()) {
		$this->stdout .= "<select ".$this->getName($name, $multiple ? '[]' : '');
		if ($autoSubmit) {
			$this->stdout .= " onchange='this.form.submit()' ";
		}
		$this->stdout .= $more . ">\n";
		$this->renderSelectionOptions($aOptions, $default, $desc);
		$this->stdout .= "</select>\n";
	}

	/**
	 * @param array $aOptions
	 * @param $default
	 * @param array $desc
	 * 		boolean '===' - compare value and default strictly
	 * 		string 'classAsValuePrefix' - will prefix value with the value of this param with space replaced with _
	 */
	function renderSelectionOptions(array $aOptions, $default, array $desc) {
		foreach ($aOptions as $value => $option) {
			if ($desc['===']) {
				$selected = $default === $value;
			} else {
				if ((is_array($default) && in_array($value, $default)) || (!is_array($default) && $default == $value)) {
					$selected = true;
				} else {
					$selected = false;
				}
			}
			if ($option instanceof HTMLTag) {
				$this->stdout .= $option;
			} else if ($option instanceof Recursive) {
				$this->stdout .= '<optgroup label="'.$option.'">';
				$this->renderSelectionOptions($option->getChildren(), $default, $desc);
				$this->stdout .= '</optgroup>';
			} else {
				$this->stdout .= "<option value=\"$value\"";
				if ($selected) {
					$this->stdout .= " selected";
				}
				if (isset($desc['classAsValuePrefix'])) {
					$this->stdout .= ' class="'.$desc['classAsValuePrefix'].str_replace(' ', '_', $value).'"';
				}
				$this->stdout .= ">$option</option>\n";
			}
		}
	}

	function date($name, $value) {
		if (!$value) {
			$value = date('d.m.Y');
		}
		$this->input($name, $value);
	}

	function money($name, $value) {
		if (!$value) {
			$value = "0.00";
		}
		$this->input($name, $value);
		$this->text("&euro;");
	}

	function textarea($name, $value = NULL, $more = '') {
		$this->stdout .= "<textarea ".$this->getName($name)." {$more}>".htmlspecialchars($value)."</textarea>";
	}

	function submit($value = NULL, $more = "", array $params = array()) {
		$params['class'] = $params['class'] ? $params['class'] : 'submit btn';
		$value = htmlspecialchars(strip_tags($value), ENT_QUOTES);
		$this->stdout .= "<input type=\"submit\" class=\"{$params['class']}\" " . ($value?'value="'.$value.'"':"") . " $more />\n";
	}

	function button($innerHTML = NULL, $more = '') {
		$this->stdout .= "<button $more>$innerHTML</button>\n";
	}

	function image($value = NULL, $more = "", $desc = array()) {
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=image ".$this->getName('submit')." src=".$desc['src']." class='submitbutton' " . ($value?"value=\"$value\"":"") . " $more>\n";
	}

	function reset($value = NULL, $more = "") {
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=reset class=submit " . ($value?"value=\"$value\"":"") . " $more>\n";
	}

	function getFormTag() {
		$a = "<form
			action=\"{$this->action}\"
			method=\"{$this->method}\" " .
			($this->enctype?" enctype=\"".$this->enctype.'"':"") .
			$this->formMore .
			($this->target ? ' target="'.$this->target.'" ' : '').
		">\n";
		if ($this->fieldset) {
			$a .= "<fieldset ".$this->getAttrHTML($this->fieldsetMore)."><legend>".$this->fieldset."</legend>";
			$a .= ($this->fieldsetMore);
		}
		return $a;
	}

	function getFormEnd() {
		$a = "</form>\n";
		if ($this->fieldset) {
			$a .= "</fieldset>";
		}
		return $a;
	}

	function getContent() {
		$c = $this->getFormTag().$this->stdout.$this->getFormEnd();
		return $c;
	}

	function getBuffer() {
		return $this->stdout;
	}

	function render() {
		print($this->getContent());
	}

	function combo($fieldName, array $desc) {
		if ($desc['table']) {
			// TODO: replace with SQLBuilder->getTableOptions()
			$options = $GLOBALS['db']->fetchAll('SELECT DISTINCT '.$desc['title'].' AS value FROM '.$desc['table'].' WHERE NOT hidden AND NOT deleted');
			$options = $GLOBALS['db']->IDalize($options, 'value', 'value');
		} else {
			$options = $desc['options'];
		}
		Index::getInstance()->addJQuery();
		$this->selection($fieldName, $options, $desc['value'], FALSE, 'onchange="$(this).nextAll(\'input\').val($(this).val());"');
		$this->input($fieldName, $desc['value']);
	}

	/**
	 * A set of checkboxes. The value is COMMA SEPARATED!
	 *
	 * @param unknown_type $name
	 * @param array/string $value - CSV or array
	 * @param unknown_type $desc
	 */
	function set($name, $value = array(), array $desc) {
		if ($value) {
			if (!is_array($value)) {
				$value = explode(',', $value);
			}
		} else {
			$value = array();
		}
		$newName = array_merge($name, array(''));
		$tmp = $this->class;
		$this->class = 'submit';
		$between = $desc['between'] ? $desc['between'] : ', ';
		foreach ((array)$desc['options'] as $key => $val) {
			$this->text('<nobr>');
			$this->check($newName, $key, in_array($key, $value), 'id="lang_'.$key.'"');
			$this->text('&nbsp;<label for="lang_'.$key.'">'.$val.'</label></nobr>');
			if ($val != end($desc['options'])) {
				$this->text($between);
			}
		}
		$this->class = $tmp;
	}

	/**
	 * A set of radio.
	 *
	 * @param unknown_type $name
	 * @param unknown_type $value
	 * @param unknown_type $desc
	 */
	function radioset($name, $value, array $desc) {
		foreach ($desc['options'] as $key => $val) {
			//debug($name, intval($value), intval($key));
			$this->radioLabel($name, $key, intval($value) == intval($key), $val, $desc['more']);
			$this->text('<br />');
		}
	}

	/**
	 * Makes TWO input fields. Keys: from, till. Value must be assiciative array too.
	 */
	function interval($name, $value, $more = '') {
		$name1 = array($name, 'from');
		$value1 = $value['from'];
		$value1 = htmlspecialchars($value1, ENT_QUOTES);
		$this->stdout .= "von: <input type=text ".$this->getName($name1). " $more value=\"".$value1."\" size='10'>\n";
		$name2 = array($name, 'till');
		$value2 = $value['till'];
		$value2 = htmlspecialchars($value2, ENT_QUOTES);
		$this->stdout .= "bis: <input type=text ".$this->getName($name2). " $more value=\"".$value2."\" size='10'>\n";
	}

	function checkarray($name, $options, $selected) {
		if ($GLOBALS['prof']) $GLOBALS['prof']->startTimer(__METHOD__);
		$selected = array_keys($selected);
		$this->stdout .= '<div style="width: 350px; height: 700px; overflow: auto; border: solid 1px silver;">';
		foreach ($options as $value => $row) {
			$checked = (!is_array($selected) && $selected == $value) ||
				(is_array($selected) && in_array($value, $selected));
			$this->stdout .= '<div class="checkline_'.($checked ? 'active' : 'normal').'">';
			$this->checkbox($name, $checked, '<span title="id='.$value.'">'.(is_array($row) ? implode(', ', $row) : $row).'</span>', $value, '[]');
			$this->stdout .= '</div>';
		}
		$this->stdout .= '</div>';
		if ($GLOBALS['prof']) $GLOBALS['prof']->stopTimer(__METHOD__);
	}

	function radioArray($name, $options, $selected) {
		if ($GLOBALS['prof']) $GLOBALS['prof']->startTimer(__METHOD__);
		$this->stdout .= '<div style="width: 350px; max-height: 700px; overflow: auto; border: solid 1px silver;">';
		foreach ($options as $value => $row) {
			$checked = (!is_array($selected) && $selected == $value) ||
				(is_array($selected) && in_array($value, $selected));
			$this->stdout .= '<div class="checkline_'.($checked ? 'active' : 'normal').'">';
			$this->radioLabel($name, $value, $checked, '<span title="id='.$value.'">'.(is_array($row) ? implode(', ', $row) : $row).'</span>');
			$this->stdout .= '</div>';
		}
		$this->stdout .= '</div>';
		if ($GLOBALS['prof']) $GLOBALS['prof']->stopTimer(__METHOD__);
	}

	function __toString() {
		return $this->getContent();
	}

	function getAttrHTML(array $attr = NULL) {
		//debug_pre_print_backtrace();
		$part = array();
		if ($attr) foreach ($attr as $key => $val) {
			$part[] = $key.'="'.htmlspecialchars($val).'"';
		}
		$html = implode(' ', $part);
		return $html;
	}

	function formColorSelector($name, $default) {
		$colors = explode(",", "#FFFFFF,#CCCCCC,#999999,#990099,#993300,#009900,#000099,#FF0000,#999900,#00FF00,#0000FF,#FF00FF,#FF9933,#FFFF00,#00FFFF");
		println("<select name=$name id=$name style='width: auto'>");
		foreach($colors as $color) {
			println("<option style='background-color: $color' value='$color' " . ($color == $default ? "selected" : "") . ">Color</option>");
		}
		println("</select>");
	}

	function recaptcha(array $desc = array()) {
		require_once('lib/recaptcha-php-1.10/recaptchalib.php');
		$content = recaptcha_get_html($this->publickey, $desc['error']);
		$this->stdout .= $content;
		return $content;
	}

	/**
	 * Make sure to implemente in form onSubmit() something like
	 * $(\'input[name="recaptcha_challenge_field"]\').val(Recaptcha.get_challenge());
	 * $(\'input[name="recaptcha_response_field"]\').val(Recaptcha.get_response());
	 *
	 * @param array $desc
	 * @return unknown
	 */
	function recaptchaAjax(array $desc) {
		$content = '<script type="text/javascript" src="http://api.recaptcha.net/js/recaptcha_ajax.js?error='.htmlspecialchars($desc['captcha-error']).'"></script>
		<div id="recaptcha_div"></div>
 		<script>
 			Recaptcha.create("'.$this->publickey.'", "recaptcha_div");
 		</script>
 		<input type="hidden" name="'.$desc['name'].'">
 		<!--input type="hidden" name="recaptcha_challenge_field"-->
 		<!--input type="hidden" name="recaptcha_response_field"-->';
		$this->stdout .= $content;
		return $content;
	}

}
