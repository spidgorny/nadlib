<?php

define("DATE_FORMAT", "d.m.Y");
class HTMLForm {
	var $action = "";
	var $method = "POST";
	var $prefix = "";
	var $stdout = "";
	var $enctype = "";
	var $class = "";
	var $fieldset;
	var $formMore = '';
	var $target = '';

	function htmlForm($action = '') {
		$this->action = $action;
	}

	function formHideArray($name, $ar) {
		if (is_array($ar)) {
			foreach($ar as $k => $a) {
				$a = htmlspecialchars($a, ENT_QUOTES);
				$ret .= "<input type=hidden name=" . $name . ($name?"[":"") . $k . ($name?"]":"") . " value=\"$a\">";
			}
		}
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
		$this->prefix = $p;
	}

	function cssClass($c) {
		$this->class = $c;
	}

	function fieldset($name) {
		$this->fieldset = $name;
	}

/*	function getName($name) {
		if ($this->prefix) {
			$a .= " name={$this->prefix}[$name] ";
		} else {
			$a .= " name=$name ";
		}
		if ($this->class) {
			$a .= " class={$this->class} ";
		} else {
			$a .= "";
		}
		return $a;
	}
*/
	function getName($name, $namePlus = '', $onlyValue = FALSE) {
		if ($this->prefix) {
			if (is_array($name)) {
				$a .= "{$this->prefix}[".implode("][", $name)."]";
			} else {
				$a .= $this->prefix.'['.$name.']'.$namePlus;
			}
		} else {
			if (is_array($name)) {
				reset($name);
				$a .= "".current($name);
			} else {
				$a .= "$name";
			}
		}
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

	function check($name, $checked, $more = "") {
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=checkbox ".$this->getName($name)." ".($checked?"checked":"")." $more>";
	}

	function radioLabel($name, $value, $checked, $label = "") {
		$value = htmlspecialchars($value, ENT_QUOTES);
		$id = $this->prefix."_".$name."_".$value;
		$this->stdout .= "<input type=radio ".$this->getName($name)." value=\"$value\" ".($value==$checked?"checked":"")." id='".$id."'> ";
		$this->stdout .= "<label for=$id>$label</label>";
	}

	function file($name, $more = '') {
		$this->stdout .= "<input type=file ".$this->getName($name).' '.$more.">";
		$this->enctype = "multipart/form-data";
	}

	function selection($name, $aOptions, $default, $autoSubmit = FALSE, $more = '') {
		$this->stdout .= "<select ".$this->getName($name);
		if ($autoSubmit)
			$this->stdout .= " onchange='this.form.submit()' ";
		$this->stdout .= $more . ">\n";
		foreach($aOptions as $value => $option) {
			$this->stdout .= "<option value=\"$value\"";
			if ((is_array($default) && in_array($value, $default)) || (!is_array($default) && $default == $value)) {
				$this->stdout .= " selected";
			}
			$this->stdout .= ">$option</option>\n";
		}
		$this->stdout .= "</select>\n";
	}

	function date($name, $value) {
		if (!$value) {
			$value = date(DATE_FORMAT);
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
		$this->stdout .= "<textarea ".$this->getName($name)." {$more}>$value</textarea>";
	}

	function submit($value = NULL, $more = "", array $params = array()) {
		//debug($more);
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=\"submit\" class=\"submit {$params['class']}\" " . ($value?'value="'.$value.'"':"") . " $more />\n";
	}

	function button($value = NULL) {
		//debug($more);
		//$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<button $more>$value</button>\n";
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
		$a = "<form action=\"{$this->action}\" method=\"{$this->method}\" " . 
			($this->enctype?" enctype=\"".$this->enctype.'"':"") . 
			$this->formMore . 
			($this->target ? ' target="'.$this->target.'" ' : '').
		">\n";
		if ($this->fieldset) {
			$a .= "<fieldset><legend>".$this->fieldset."</legend>";
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
		$c .= $this->getFormTag().$this->stdout.$this->getFormEnd();
		return $c;
	}

	function getBuffer() {
		return $this->stdout;
	}

	function render() {
		print($this->getContent());
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

	function __toString() {
		return $this->getContent();
	}
	
	function formColorSelector($name, $default) {
		$colors = explode(",", "#FFFFFF,#CCCCCC,#999999,#990099,#993300,#009900,#000099,#FF0000,#999900,#00FF00,#0000FF,#FF00FF,#FF9933,#FFFF00,#00FFFF");
		println("<select name=$name id=$name style='width: auto'>");
		foreach($colors as $color) {
			println("<option style='background-color: $color' value='$color' " . ($color == $default ? "selected" : "") . ">Color</option>");

		}
		println("</select>");
	}
	
}

