<?php

class HTMLForm
{
	protected $action = "";
	protected $method = "POST";
	protected $prefix = array();
	var $stdout = "";
	var $enctype = "";
	var $target = "";

	/**
	 * Deprecated use for maybe XSS class in some form fields.
	 * Now it's the class name (or just a unique identifier of the form) to be used
	 * with XSRF protection.
	 * @var string
	 */
	var $class = "";

	protected $fieldset;
	protected $fieldsetMore = array();
	var $formMore = '';
	public $debug = false;
	var $publickey = "6LeuPQwAAAAAADaepRj6kI13tqxU0rPaLUBtQplC";
	var $privatekey = "6LeuPQwAAAAAAAuAnYFIF-ZM9yXnkbssaF0rRtkj";

	public function constructor($action = '')
	{
		$this->action = $action;
	}

	function formHideArray(array $ar)
	{
		foreach ($ar as $k => $a) {
			if (is_array($a)) {
				$this->prefix[] = $k;
				$this->formHideArray($a);
				array_pop($this->prefix);
			} else {
				//$ret .= "<input type=hidden name=" . $name . ($name?"[":"") . $k . ($name?"]":"") . " value='$a'>";
				$this->hidden($k, $a);
			}
		}
	}

	function action($action)
	{
		$this->action = $action;
	}

	function method($method)
	{
		$this->method = $method;
	}

	function target($target)
	{
		$this->target = $target;
	}

	function text($a)
	{
		$this->stdout .= $a;
	}

	function prefix($p)
	{
		if (is_array($p)) {
			$this->prefix = $p;
		} else if ($p) {
			$this->prefix = array($p);
		} else {
			$this->prefix = array();
		}
	}

	function fieldset($name, $more = array())
	{
		$this->fieldset = $name;
		$this->fieldsetMore = $more;
	}

	function getFieldset()
	{
		return $this->fieldset;
	}

	function getName($name, $namePlus = '', $onlyValue = FALSE)
	{
		$a = '';
		$path = $this->prefix;
		$path = array_merge($path, is_array($name) ? $name : array($name));
		$first = array_shift($path);
		$a .= $first;
		if ($path) {
			$a .= "[" . implode("][", $path) . "]";
		}
		$a .= $namePlus;
		if (!$onlyValue) {
			$a = ' name="' . $a . '"';
		}
		//debug($this->prefix, $name, $a);
		return $a;
	}

	/**
	 * @param $type
	 * @param $name
	 * @param null $value
	 * @param string/array $more - may be array
	 * @param string $extraClass
	 * @param string $namePlus
	 * @return string
	 */
	function getInput($type, $name, $value = NULL, $more = NULL, $extraClass = '', $namePlus = '')
	{
		$a = '';
		$a .= '<input type="' . $type . '" class="' . $type . ' ' . $extraClass . '"';
		$a .= $this->getName($name, $namePlus);
		if ($value || $value === 0) {
			$value = htmlspecialchars($value, ENT_QUOTES);
			$a .= ' value="' . $value . '"';
		}
		if ($more) {
			$a .= " " . (is_array($more) ? $this->getAttrHTML($more) : $more);
		}
		$a .= ">\n";
		return $a;
	}

	/**
	 * @param $name
	 * @param string $value
	 * @param string/array $more - may be array
	 * @param string $type
	 * @param string $extraClass
	 */
	function input($name, $value = "", $more = '', $type = 'text', $extraClass = '')
	{
		//$value = htmlspecialchars($value, ENT_QUOTES);
		//$this->stdout .= '<input type="'.$type.'" '.$this->getName($name).' '.$more.' value="'.$value.'" />'."\n";
		$this->stdout .= $this->getInput($type, $name, $value, $more, $extraClass);
	}

	function label($for, $text)
	{
		$this->stdout .= '<label for="' . $for . '">' . $text . '</label>';
	}

	/**
	 *
	 * Table row with $text and input
	 * @param string $text
	 * @param string $name
	 * @param string $value
	 * @param string $more
	 */
	function tinput($text, $name, $value = "", $more = '')
	{
		$this->text('<tr><td>' . $text . '</td><td>');
		$this->input($name, $value, $more);
		$this->text('</td></tr>');
	}

	function password($name, $value = "", array $desc = array())
	{
		//$value = htmlspecialchars($value, ENT_QUOTES);
		//$this->stdout .= "<input type=\"password\" ".$this->getName($name)." value=\"$value\">\n";
		$this->stdout .= $this->getInput("password", $name, $value, '', $desc['class']);
	}

	function hidden($name, $value, $more = "")
	{
		//$value = htmlspecialchars($value, ENT_QUOTES);
		//$this->stdout .= "<input type=hidden ".$this->getName($name). " value=\"$value\" ".$more.">";
		$this->stdout .= $this->getInput("hidden", $name, $value, $more);
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param strting $checked - must be value
	 * @param string $more
	 */
	function radio($name, $value, $checked, $more = "")
	{
		//$value = htmlspecialchars($value, ENT_QUOTES);
		//$this->stdout .= "<input type=radio ".$this->getName($name)." value=\"$value\" ".($value==$checked?"checked":"")." $more>";
		$this->stdout .= $this->getInput("radio", $name, $value, ($value == $checked ? "checked" : "") . ' ' . $more);
	}

	/**
	 * @param $name
	 * @param $value
	 * @param boolean $checked
	 * @param string $label
	 * @param string $more
	 */
	function radioLabel($name, $value, $checked, $label = "", $more = '')
	{
		$value = htmlspecialchars($value, ENT_QUOTES);
		$aName = is_array($name) ? $name : array();
		$id = implode('_', array_merge($this->prefix, $aName)) . "_" . $value;
		$this->stdout .= '<label class="radio" for="' . $id . '">
		<input
			type="radio"
			' . $this->getName($name) . '
			value="' . htmlspecialchars($value, ENT_QUOTES) . '" ' .
			($checked ? "checked" : "") . '
			id="' . $id . '"
			' . $more . '> ';
		$this->stdout .= $this->hsc($label) . "</label>";
	}

	function check($name, $value = 1, $checked = false, $more = "", $autoSubmit = false)
	{
		//$value = htmlspecialchars($value, ENT_QUOTES);
		//$this->stdout .= "<input type=checkbox ".$this->getName($name)." ".($checked?"checked":"")." value=\"$value\" $more>";
		$this->stdout .= $this->getInput("checkbox", $name, $value,
			($checked ? 'checked="checked"' : "") . ' ' .
			($autoSubmit ? "onchange=this.form.submit()" : '') . ' ' .
			(is_array($more) ? $this->getAttrHTML($more) : $more)
		);
	}

	function checkLabel($name, $value = 1, $checked = false, $more = "", $autoSubmit = false, $label = '')
	{
		$this->stdout .= '<label>';
		$this->check($name, $value, $checked, $more, $autoSubmit);
		$this->stdout .= ' ' ./*htmlspecialchars*/
			($label) . '</label>';
	}

	function hsc($label)
	{
		if ($label instanceof htmlString) {
			return $label;
		} else {
			return htmlspecialchars($label, ENT_QUOTES);
		}
	}

	function file($name, array $desc = array())
	{
		//$this->stdout .= "<input type=file ".$this->getName($name)." ".$desc['more'].">";
		$this->stdout .= $this->getInput("file", $name, '', $desc['more'], $desc['class']);
		$this->method = 'POST';
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
	function selection($name, array $aOptions, $default, $autoSubmit = FALSE, $more = '', $multiple = false, array $desc = array())
	{
		$this->stdout .= "<select " . $this->getName($name, $multiple ? '[]' : '');
		if ($autoSubmit) {
			$this->stdout .= " onchange='this.form.submit()' ";
		}
		if ($multiple) {
			$this->stdout .= ' multiple="1"';
		}
		$more .=
			(isset($desc['size']) ? ' size="' . $desc['size'] . '"' : '') .
			(isset($desc['id']) ? ' id="' . $desc['id'] . '"' : '') .
			(isset($desc['more']) ? $desc['more'] : '');
		$this->stdout .= $more . ">\n";
		$this->stdout .= $this->getSelectionOptions($aOptions, $default, $desc);
		$this->stdout .= "</select>\n";
	}

	/**
	 * @param array $aOptions
	 * @param $default
	 * @param array $desc
	 *        boolean '===' - compare value and default strictly (BUG: integer looking string keys will be treated as integer)
	 *        string 'classAsValuePrefix' - will prefix value with the value of this param with space replaced with _
	 * @return string
	 */
	function getSelectionOptions(array $aOptions, $default, array $desc = array())
	{
		//Debug::debug_args($aOptions);
		$content = '';
		foreach ($aOptions as $value => $option) {
			/** PHP feature gettype($value) is integer even if it's string in an array!!! */
			if ($desc['===']) {
				$selected = $default === $value;
				if (sizeof($aOptions) == 14) {
					debug(array(
						'default' => $default,
						'value' => $value,
						'selected' => $selected,
					));
				}
			} else {
				//debug($default, $value);
				if ((is_array($default) && in_array($value, $default))
					|| (!is_array($default) && $default == $value)) {
					$selected = true;
				} else {
					$selected = false;
				}
			}
			if ($option instanceof HTMLTag) {
				$content .= $option;
			} else if ($option instanceof Recursive) {
				$content .= '<optgroup label="' . $option . '">';
				$content .= $this->getSelectionOptions($option->getChildren(), $default, $desc);
				$content .= '</optgroup>';
			} else {
				$content .= "<option value=\"$value\"";
				if ($selected) {
					$content .= " selected";
				}
				if (isset($desc['classAsValuePrefix'])) {
					$content .= ' class="' . $desc['classAsValuePrefix'] . str_replace(' ', '_', $value) . '"';
				}
				if (isset($desc['useTitle']) && $desc['useTitle'] == true) {
					$content .= ' title="' . strip_tags($option) . '"';
				}
				$content .= ">$option</option>\n";
			}
		}
		return $content;
	}

	/**
	 * Default value is no longer "today"
	 * @param $name
	 * @param $value
	 * @param array $desc
	 */
	function date($name, $value, array $desc = array())
	{
		$format = $desc['format'] ?: 'd.m.Y';
		if (is_numeric($value)) {
			$value = date($format, $value);
		} elseif (!$value) {
			//$value = date('d.m.Y');
		}
		$this->input($name, $value,
			(isset($desc['id']) ? ' id="' . $desc['id'] . '"' : '') .
			(isset($desc['more']) ? $desc['more'] : '')
		);
	}

	function datepopup($name, $value = NULL, $type = "input", $activator = NULL, $id = NULL, $params = array())
	{
		$id = $id ? $id : uniqid('datepopup');
		$fullname = $this->getName($name, '', TRUE);
		$GLOBALS['HTMLHEADER']['datepopup'] = '
	<script type="text/javascript" src="lib/jscalendar-1.0/calendar.js"></script>
	<script type="text/javascript" src="lib/jscalendar-1.0/lang/calendar-en.js"></script>
	<script type="text/javascript" src="lib/jscalendar-1.0/calendar-setup.js"></script>
	<link rel="stylesheet" type="text/css" media="all" href="lib/jscalendar-1.0/skins/aqua/theme.css" />';
		$this->stdout .= '
	<input type="' . $type . '" name="' . $fullname . '" id="id_field_' . $id . '" value="' . ($value ? date('Y-m-d', $value) : '') . '" />
	' . ($activator ? $activator : '<button type="button" id="id_button_' . $id . '" style="width: auto">...</button>') . '
	<script type="text/javascript">
		var setobj = {
	        inputField     :    "id_field_' . $id . '",     // id of the input field
	        ifFormat       :    "%Y-%m-%d",       		// format of the input field
	        showsTime      :    false,            		// will display a time selector
	        button         :    "id_button_' . $id . '",   	// trigger for the calendar (button ID)
	        singleClick    :    false,           		// double-click mode
	    ';
		if ($params) {
			foreach ($params as $key => $val) {
				$this->stdout .= $key . ':' . $val . ',';
			}
		}
		$this->stdout .= '
	        step           :    1                		// show all years in drop-down boxes (instead of every other year as default)
	    };
	    var cal_' . $id . ' = Calendar.setup(setobj);
	</script>
';
	}

	function money($name, $value, array $desc)
	{
		if (!$value) {
			$value = "0.00";
		}
		$this->input($name, $value, $desc['more']);
		$this->text("&euro;");
	}

	function textarea($name, $value = NULL, $more = '')
	{
		$more = is_array($more) ? HTMLForm::getAttrHTML($more) : $more;
		$this->stdout .= "<textarea " . $this->getName($name) . " {$more}>" .
			htmlspecialchars($value) .
			"</textarea>";
	}

	/**
	 * Changelog: second $more parameter was removed, please user $params instead
	 * @param null $value
	 * @param array $params
	 */
	function submit($value = NULL, array $params = array())
	{
		$params['class'] = $params['class'] ? $params['class'] : 'submit btn';
		$params['name'] = $params['name'] ? $params['name'] : 'submit';
		//$value = htmlspecialchars(strip_tags($value), ENT_QUOTES);
		//$this->stdout .= "<input type=\"submit\" ".$this->getAttrHTML($params)." ".($value?'value="'.$value.'"':"") . " $more />\n";
		$this->stdout .= $this->getInput("submit", $params['name'], $value, $this->getAttrHTML($params), $params['class']);
	}

	function button($innerHTML = NULL, $more = '')
	{
		$this->stdout .= "<button $more>$innerHTML</button>\n";
	}

	function image($value = NULL, $more = "", $desc = array())
	{
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=image " . $this->getName('submit') . " src=" . $desc['src'] . " class='submitbutton' " . ($value ? "value=\"$value\"" : "") . " $more>\n";
	}

	function reset($value = NULL, $more = "")
	{
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=reset class=submit " . ($value ? "value=\"$value\"" : "") . " $more>\n";
	}

	function getFormTag()
	{
		$a = "<form
			action=\"{$this->action}\"
			method=\"{$this->method}\" " .
			($this->enctype ? " enctype=\"" . $this->enctype . '"' : "") .
			$this->formMore .
			($this->target ? ' target="' . $this->target . '" ' : '') .
			">\n";
		if ($this->fieldset) {
			$a .= "<fieldset " . $this->getAttrHTML($this->fieldsetMore) . "><legend>" . $this->fieldset . "</legend>";
			$a .= is_array($this->fieldsetMore) ? implode(' ', $this->fieldsetMore) : $this->fieldsetMore;
		}
		return $a;
	}

	function getFormEnd()
	{
		$a = "</form>\n";
		if ($this->fieldset) {
			$a .= "</fieldset>";
		}
		return $a;
	}

	function getContent()
	{
		$c = $this->getFormTag() . $this->stdout . $this->getFormEnd();
		return $c;
	}

	function getBuffer()
	{
		return $this->stdout;
	}

	function render()
	{
		print($this->getContent());
	}

	function combo($fieldName, array $desc)
	{
		if ($desc['table']) {
			// TODO: replace with SQLBuilder->getTableOptions()
			$db = Config::getInstance()->db;
			$options = $db->fetchAll('SELECT DISTINCT ' . $desc['title'] . ' AS value FROM ' . $desc['table'] . ' WHERE NOT hidden AND NOT deleted');
			$options = $db->IDalize($options, 'value', 'value');
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
	 * @param string $name
	 * @param array/string $value - CSV or array
	 * @param array $desc
	 *        'between' - text that separates checkboxes (default ", ")
	 */
	function set($name, $value = array(), array $desc)
	{
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
			$this->text('<nobr><label>');
			$this->check($newName, $key, in_array($key, $value));
			$this->text(' ' . $val . '</label></nobr>');
			if ($val != end($desc['options'])) {
				$this->text($between);
			}
		}
		$this->class = $tmp;
	}

	/**
	 * A set of radio.
	 *
	 * @param string $name
	 * @param int $value
	 * @param array $desc
	 *        'between' - text separating the options, default <br />
	 */
	function radioset($name, $value, array $desc)
	{
		$between = $desc['between'] ? $desc['between'] : '<br />';
		foreach ($desc['options'] as $key => $val) {
			//debug($name, intval($value), intval($key));
			$this->radioLabel($name, $key, intval($value) == intval($key), $val, $desc['more']);
			$this->text($between);
		}
	}

	function jsCal2($fieldName, $fieldValue)
	{
		if (is_string($fieldValue)) {
			$fieldValue = strtotime($fieldValue);
		}
		//$GLOBALS['HTMLHEADER']['JSCal2'] = '
		$content = '
		<link rel="stylesheet" type="text/css" href="JSCal2/css/jscal2.css" />
    <link rel="stylesheet" type="text/css" href="JSCal2/css/border-radius.css" />
    <link rel="stylesheet" type="text/css" href="JSCal2/css/gold/gold.css" />
    <script type="text/javascript" src="JSCal2/js/jscal2.js"></script>
    <script type="text/javascript" src="JSCal2/js/lang/en.js"></script>';
		$content .= '<input id="calendar-' . $fieldName . '" name="' . $this->getName($fieldName) . '" value="' .
			($fieldValue ? date('Y-m-d', $fieldValue) : '') . '"/>
		<button id="calendar-trigger-' . $fieldName . '" onclick="return false;">...</button>
<script>
    Calendar.setup({
        trigger    	: "calendar-trigger-' . $fieldName . '",
        inputField 	: "calendar-' . $fieldName . '",
        min			: ' . date('Ymd') . ',
/*      selection	: Calendar.dateToInt(new Date(\'' . date('Y-m-d', $fieldValue) . '\')),
        date        : Calendar.dateToInt(new Date(\'' . date('Y-m-d', $fieldValue) . '\')),
*/      selection   : Calendar.dateToInt(new Date(' . (1000 * $fieldValue) . ')),
        date        : Calendar.dateToInt(new Date(' . (1000 * $fieldValue) . ')),
        onSelect   	: function() { this.hide() }
    });
</script>
';
		return $content;
	}

	static function dropSelect($fieldName, array $options)
	{
		$content = '
			<input type="hidden" name="' . $fieldName . '" id="' . $fieldName . '">
			<input type="text" name="' . $fieldName . '_name" id="' . $fieldName . '_name" onchange="setDropSelectValue(this.value, this.value);">
			<img src="design/bb8120_options_icon.gif" id="' . $fieldName . '_selector">
			<link rel="stylesheet" href="js/proto.menu.0.6.css" type="text/css" media="screen" />
			<script src="js/proto.menu.0.6.js" defer="true"></script>
			<script>
				//document.observe("dom:loaded", function() {
				window.onload = function () {
					var myMenuItems = [';
		$optArr = array();
		foreach ($options as $id => $name) {
			$optArr[] = '{
						    name: "' . $name . '",
						    className: "swr",
						    callback: function() {
								setDropSelectValue("' . $id . '", "' . $name . '");
						    }
					    }';
		}
		$content .= implode(',', $optArr) . '
					];
					new Proto.Menu({
					  selector: "#' . $fieldName . '_selector",
					  className: "menu desktop",
					  menuItems: myMenuItems
					});
				};
				function setDropSelectValue(id, name) {
					$("' . $fieldName . '").value = id;
					$("' . $fieldName . '_name").value = name;
				}
			</script>';
		return $content;
	}

	/**
	 * Makes TWO input fields. Keys: from, till. Value must be assiciative array too.
	 */
	function interval($name, $value, $more = '')
	{
		$name1 = array($name, 'from');
		$value1 = $value['from'];
		$value1 = htmlspecialchars($value1, ENT_QUOTES);
		$this->stdout .= "von: <input type=text " . $this->getName($name1) . " $more value=\"" . $value1 . "\" size='10'>\n";
		$name2 = array($name, 'till');
		$value2 = $value['till'];
		$value2 = htmlspecialchars($value2, ENT_QUOTES);
		$this->stdout .= "bis: <input type=text " . $this->getName($name2) . " $more value=\"" . $value2 . "\" size='10'>\n";
	}

	/**
	 * A set of checkboxes in a div.checkarray. Values are provided as an array
	 * @param $name
	 * @param array $options
	 * @param array $selected - only keys are used
	 * @param string $more
	 * @param string $height
	 * @param int $width
	 * @see set()
	 */
	function checkarray(array $name, array $options, array $selected, $more = '', $height = 'auto', $width = 350)
	{
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		$selected = array_keys($selected);
		$sName = $this->getName($name, '', true);
		$this->stdout .= '<div style="width: ' . $width . '; height: ' . $height . '; overflow: auto;" class="checkarray ' . $sName . '">';
		$newName = array_merge($name, array(''));
		foreach ($options as $value => $row) {
			$checked = (!is_array($selected) && $selected == $value) ||
				(is_array($selected) && in_array($value, $selected));
			$this->stdout .= '<label class="checkline_' . ($checked ? 'active' : 'normal') . '">';
			$moreStr = (is_array($more) ? $this->getAttrHTML($more) : $more);
			$moreStr = str_replace(urlencode("###KEY###"), $value, $moreStr);
			$this->check($newName, $value, $checked, $moreStr);
			$this->text('<span title="id=' . $value . '">' . (is_array($row) ? implode(', ', $row) : $row) . '</span>');
			$this->stdout .= '</label>';
		}
		$this->stdout .= '</div>';
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	/**
	 * This one makes a span with a title and is showing data in a specific width
	 * @param $name
	 * @param array $options
	 * @param $selected
	 * @see $this->radioset()
	 */
	function radioArray($name, array $options, $selected)
	{
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->stdout .= '<div class="radioArray">';
		foreach ($options as $value => $row) {
			$checked = (!is_array($selected) && $selected == $value) ||
				(is_array($selected) && in_array($value, $selected));
			$this->stdout .= '<div class="checkline_' . ($checked ? 'active' : 'normal') . '">';
			$this->radioLabel($name, $value, $checked, new htmlString('<span title="id=' . $value . '">' . (is_array($row) ? implode(', ', $row) : $row) . '</span>'));
			$this->stdout .= '</div>';
		}
		$this->stdout .= '</div>';
		if ($GLOBALS['profiler']) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	function __toString()
	{
		return $this->getContent();
	}

	/**
	 * Converts an assoc array into valid HTML name="value" string
	 * @param array $attr
	 * @return string
	 */
	function getAttrHTML(array $attr = NULL)
	{
		$part = array();
		if ($attr) foreach ($attr as $key => $val) {
			if (is_array($val)) {
				$val = implode(' ', $val);
			}
			if (is_scalar($val)) {
				$part[] = $key . '="' . htmlspecialchars($val) . '"';
			} else {
				//debug($attr);
				//throw new Exception(__METHOD__);
			}
		}
		$html = implode(' ', $part);
		return $html;
	}

	function formColorSelector($name, $default)
	{
		$colors = explode(",", "#FFFFFF,#CCCCCC,#999999,#990099,#993300,#009900,#000099,#FF0000,#999900,#00FF00,#0000FF,#FF00FF,#FF9933,#FFFF00,#00FFFF");
		println("<select name=$name id=$name style='width: auto'>");
		foreach ($colors as $color) {
			println("<option style='background-color: $color' value='$color' " . ($color == $default ? "selected" : "") . ">Color</option>");
		}
		println("</select>");
	}

	function recaptcha(array $desc = array())
	{
		require_once('lib/recaptcha-php-1.10/recaptchalib.php');
		$content = recaptcha_get_html($this->publickey, $desc['error']);
		$this->stdout .= $content;
		return $content;
	}

	/**
	 * Make sure to implement in form onSubmit() something like
	 * $(\'input[name="recaptcha_challenge_field"]\').val(Recaptcha.get_challenge());
	 * $(\'input[name="recaptcha_response_field"]\').val(Recaptcha.get_response());
	 *
	 * @param array $desc
	 * @return string
	 */
	function recaptchaAjax(array $desc)
	{
		$content = '<script type="text/javascript" src="http://api.recaptcha.net/js/recaptcha_ajax.js?error=' . htmlspecialchars($desc['captcha-error']) . '"></script>
		<div id="recaptcha_div"></div>
 		<script>
 			Recaptcha.create("' . $this->publickey . '", "recaptcha_div");
 		</script>
 		<input type="hidden" name="' . $desc['name'] . '">
 		<!--input type="hidden" name="recaptcha_challenge_field"-->
 		<!--input type="hidden" name="recaptcha_response_field"-->';
		$this->stdout .= $content;
		return $content;
	}

	/**
	 * $desc['selectID'] - <select name="projects" id="projects">
	 * $desc['treeDivID'] - <div id="treeDivID" style="display: none"></div>
	 * $desc['tableName'] - SELECT * FROM tableName ...
	 * $desc['tableRoot'] - ... WHERE pid = tableRoot
	 * $desc['tableTitle'] - SELECT id, tableTitle FROM ...
	 * $desc['paddedID'] - paddedID.innterHTML = tree.toString()
	 */
	function ajaxTree($desc)
	{
		$GLOBALS['HTMLHEADER']['ajaxTreeOpen'] = '<script src="js/ajaxTreeOpen.js"></script>';
		$GLOBALS['HTMLHEADER']['globalMouse'] = '<script src="js/globalMouse.js"></script>';
		$GLOBALS['HTMLHEADER']['dragWindows'] = '<script src="js/dragWindows.js"></script>';
		$this->stdout .= AppController::ahref('<img
			src="img/tb_folder.gif"
			title="' . $desc['ButtonTitle'] . '">', '#', '', 'onclick="ajaxTreeOpen(
			\'' . $desc['selectID'] . '\',
			\'' . $desc['treeDivID'] . '\',
			\'' . $desc['tableName'] . '\',
			\'' . $desc['tableRoot'] . '\',
			\'' . $desc['tableTitle'] . '\',
			\'' . (isset($desc['paddedID']) ? $desc['paddedID'] : '') . '\',
			\'' . $desc['categoryID'] . '\',
			\'' . $desc['onlyLeaves'] . '\');
			' . $desc['onclickMore'] . '
			return false;
		"');
		$style = 'display: none;
		position: absolute;
		left: 0;
		top: 0;
		width: 480px;
		height: auto;
		border: solid 3px #8FBC8F;
		margin: 3px;
		background-color: white;
		az-index: 98;';
		//$this->stdout .= '<div id="'.$desc['treeDivID'].'" style="'.$style.'"></div>';
		$this->stdout .= AppController::encloseOld('Tree-Element Selector', '',
			array(
				'outerStyle' => $style,
				'foldable' => FALSE,
				'outerID' => $desc['treeDivID'],
				'paddedID' => (isset($desc['paddedID']) ? $desc['paddedID'] : ''),
				'closable' => TRUE,
				'absolute' => TRUE,
				'paddedStyle' => 'height: 640px; overflow: auto;',
				'titleMore' => 'onmousedown="dragStart(event, \'' . $desc['treeDivID'] . '\')" style="cursor: move;"',
			));
	}

	function ajaxTreeInput($fieldName, $fieldValue, array $desc)
	{
		$desc['more'] = isset($desc['more']) ? $desc['more'] : NULL;
		$desc['size'] = isset($desc['size']) ? $desc['size'] : NULL;
		$desc['cursor'] = isset($desc['cursor']) ? $desc['cursor'] : NULL;
		$desc['readonly'] = isset($desc['readonly']) ? $desc['readonly'] : NULL;
		$this->text('<nobr>');
		$this->hidden($fieldName, $fieldValue, 'id="' . $desc['selectID'] . '"');
		$fieldName[sizeof($fieldName) - 1] = end($fieldName) . '_name';
		$this->input($fieldName, $desc['valueName'],
			'style="width: ' . $desc['size'] . '"
			readonly
			id="' . $desc['selectID'] . '_name" ' .
			$desc['more']);
		$this->text('</td><td>');
		$this->ajaxTree($desc);
		$this->text('</nobr>');
	}

}
