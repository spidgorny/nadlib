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

	/**
	 * @var array
	 */
	var $formMore = array(
		'class' => '',
	);

	public $debug = false;

	function __construct($action = '')
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
		$this->stdout .= MergedContent::mergeStringArrayRecursive($a);
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

	function getNameField($name, $namePlus = '', $onlyValue = FALSE)
	{
		return $this->getName($name, $namePlus, $onlyValue);
	}

	function getNameTag($name)
	{
		return $this->getName($name, '', false);
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
//		debug($type, $name, $value, $more, $extraClass, $namePlus);
		$a = '';
		$moreClass = is_array($more) ? ifsetor($more['class']) : '';
		$a .= '<input type="' . $type . '" class="' . $type . ' ' . $extraClass . ' ' . $moreClass . '"';
		$a .= $this->getName($name, $namePlus);
		if ($value || $value === 0) {
			$isHTML = $value instanceof htmlString;
			//debug($value, $isHTML);
			if (!$isHTML) {
				if (is_array($value)) {
					$sName = $this->getName($name, $namePlus, true);
					throw new Exception('HTMLForm has an array value for field ' . $sName);
				}
				$value = htmlspecialchars($value, ENT_QUOTES);
			} else {
				$value = str_replace('"', '&quot;', $value);
			}
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
		$this->stdout .= $this->getInput("password", $name, $value, $desc, ifsetor($desc['class']));
	}

	function hidden($name, $value, $more = "")
	{
//		debug(__METHOD__, $name, $value);
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
			' . (is_array($more) ? $this->getAttrHTML($more) : $more) . '> ';
		$this->stdout .= $this->hsc($label) . "</label>";
	}

	function check($name, $value = 1, $checked = false, $more = "", $autoSubmit = false)
	{
		$desc = [];
		$desc['more'] = $more;
		$desc['autoSubmit'] = $autoSubmit;
		$desc['value'] = $value;
		$box = new HTMLFormCheckbox($name, $checked, $desc);
		$box->form = $this;    // for prefix to work
		$this->stdout .= $box;
	}

	function checkbox($name, $value = 1, $label = '', $checked = false, $more = "", $autoSubmit = false)
	{
		$desc = [];
		$desc['more'] = $more;
		$desc['autoSubmit'] = $autoSubmit;
		$desc['value'] = $value;
		$box = new HTMLFormCheckbox($name, $checked, $desc);
		$box->form = $this;    // for prefix to work
		$this->stdout .= $box;
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
		$this->stdout .= $this->getInput("file", $name, '', ifsetor($desc['more']), ifsetor($desc['class']));
		$this->method = 'POST';
		$this->enctype = "multipart/form-data";
	}

	/**
	 * @param $name
	 * @param $aOptions
	 * @param $default
	 * @param bool $autoSubmit
	 * @param array $more
	 * @param bool $multiple
	 * @param array $desc
	 * @see renderSelectionOptions
	 */
	function selection($name, array $aOptions = NULL, $default,
										 $autoSubmit = FALSE, $more = array(),
										 $multiple = false, array $desc = array())
	{
		$sel = new HTMLFormSelection($name, $aOptions, $default);
		$sel->autoSubmit = $autoSubmit;
		$sel->more = is_string($more) ? HTMLTag::parseAttributes($more) : $more;
		$sel->multiple = $multiple;
		$sel->setDesc($desc);
		//debug($name, $desc);
		$sel->setForm($this);
		$this->stdout .= $sel->render();
	}

	/**
	 * Default value is no longer "today"
	 * @param $name
	 * @param $value
	 * @param array $desc
	 */
	function date($name, $value, array $desc = array())
	{
//		debug($value);
		$format = ifsetor($desc['format']) ? $desc['format'] : 'd.m.Y';
		if (is_numeric($value)) {
			$value = date($format, $value);
		} elseif (!$value) {
			//$value = date('d.m.Y');
		}

		if ($desc['more'] && !is_array($desc['more'])) {
			debug($name, $desc);
			debug_pre_print_backtrace();
			exit();
			throw new InvalidArgumentException(__METHOD__ . ' $desc[more] is not array');
		}

		$this->input($name, $value,
			(isset($desc['id']) ? ' id="' . $desc['id'] . '"' : '') .
			(isset($desc['more']) ? HTMLTag::renderAttr($desc['more']) : ''),
			'date'
		);
	}

	/**
	 * Make sure to include the JSCal2 JS in advance
	 * @param $name
	 * @param null $value
	 * @param string $type
	 * @param null $activator
	 * @param null $id
	 * @param array $params
	 */
	function datepopup($name, $value = NULL, $type = "input", $activator = NULL, $id = NULL, $params = array())
	{
		$id = $id ? $id : uniqid('datepopup');
		$fullname = $this->getName($name, '', TRUE);
		if (is_numeric($value)) {
			$value = $value > 0 ? date('Y-m-d', $value) : '';
		}
		$this->stdout .= '
		<input type="' . $type . '"
			name="' . $fullname . '"
			id="id_field_' . $id . '"
			value="' . $value . '" />
			' . ($activator ? $activator : '<button type="button"
			 id="id_button_' . $id . '"
			 style="width: auto">...</button>');
		$script = '
	<script type="text/javascript">
		var setobj = {
	        inputField: "id_field_' . $id . '",     // id of the input field
	        ifFormat: "%Y-%m-%d",       		// format of the input field
	        showsTime: false,            		// will display a time selector
	        trigger: "id_button_' . $id . '",   	// trigger for the calendar (button ID)
	        singleClick: false,           		// double-click mode
	        onSelect   : function() { this.hide() },';
		if ($params) {
			foreach ($params as $key => $val) {
				$script .= $key . ':' . $val . ',';
			}
		}
		$script .= '
	        step:    1                		// show all years in drop-down boxes (instead of every other year as default)
	    };
	    var cal_' . $id . ' = Calendar.setup(setobj);
	</script>
';
		$index = Index::getInstance();
		$index->footer['init_cal_' . $id] = $script;
	}

	function datepopup2($name, $value = NULL, $plusConfig = '', array $desc = array())
	{
		$dp2 = new HTMLFormDatePopup2($this, $name, $value, $desc + array(
				'plusConfig' => $plusConfig,
				'phpFormat' => 'Y-m-d',
			));
		$this->stdout .= $dp2 . '';
		return $dp2->id;
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
	 * Changelog: second $more parameter was removed, please use $params instead
	 * @param null $value
	 * @param array $params
	 * @return string
	 */
	function submit($value = NULL, array $params = array())
	{
		$params['class'] = ifsetor($params['class'], 'submit btn');
		$params['name'] = ifsetor($params['name'], 'btnSubmit');
		//$value = htmlspecialchars(strip_tags($value), ENT_QUOTES);
		//$this->stdout .= "<input type=\"submit\" ".$this->getAttrHTML($params)." ".($value?'value="'.$value.'"':"") . " $more />\n";
		// this.form.submit() will not work
		//debug('submit', $params);
		$content = $this->getInput("submit", $params['name'], $value, $this->getAttrHTML($params), $params['class']);
		$this->stdout .= $content;
		return $content;
	}

	function button($innerHTML = NULL, array $more = array())
	{
		$more = HTMLTag::renderAttr($more);
		$this->stdout .= "<button $more>$innerHTML</button>\n";
	}

	function image($value = NULL, $more = "", $desc = array())
	{
		$more = is_array($more) ? HTMLTag::renderAttr($more) : $more;
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=image
		" . $this->getName('imgSubmit') . "
		src=" . $desc['src'] . "
		class='submitbutton' " .
			($value ? "value=\"$value\"" : "") . " $more>\n";
	}

	function reset($value = NULL, $more = "")
	{
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=reset class=submit " . ($value ? "value=\"$value\"" : "") . " $more>\n";
	}

	function getFormTag()
	{
		if (is_string($this->formMore)) {
			$attributes = HTMLTag::parseAttributes($this->formMore);
		} else {
			$attributes = $this->formMore;
		}
		$attributes += array(
			'action' => $this->action,
			'method' => $this->method,
		);
		if ($this->enctype) {
			$attributes["enctype"] = $this->enctype;
		}
		if ($this->target) {
			$attributes['target'] = $this->target;
		}
		$a = "<form " . HTMLTag::renderAttr($attributes) . ">\n";
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
		if ($desc['from']) {
			// TODO: replace with SQLBuilder->getTableOptions()
			$db = Config::getInstance()->getDB();
			$options = $db->fetchAll('SELECT DISTINCT ' . $desc['title'] . ' AS value
			FROM ' . $desc['from'] . '
			WHERE NOT hidden AND NOT deleted
			ORDER BY value');
			$options = $db->IDalize($options, 'value', 'value');
		} else {
			$options = $desc['options'];
		}
		Index::getInstance()->addJQuery();
		$this->selection($fieldName, $options, $desc['value'], FALSE, 'onchange="$(this).nextAll(\'input\').val($(this).val());"', false, $desc);
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
		$newName = array_merge($name, array(''));    // []
		$tmp = $this->class;
		$this->class = 'submit';
		$between = ifsetor($desc['between'], ', ');
		foreach ((array)$desc['options'] as $key => $val) {
			$this->text('<nobr><label title="' . $key . '">');
			$checked = in_array($key, $value);
			//debug($key, $value, $checked);
			$this->check($newName, $key, $checked);
			$this->text(' ' . $val . '</label></nobr>');
			if ($val != end($desc['options'])) {
				$this->text($between);
			}
		}
		$this->class = $tmp;
	}

	function keyset($name, $value = array(), array $desc)
	{
		if ($value) {
			if (!is_array($value)) {
				$value = explode(',', $value);
			}
		} else {
			$value = array();
		}
		$tmp = $this->class;
		$this->class = 'submit';
		$between = ifsetor($desc['between'], ', ');
//		debug($desc['options']);
		foreach ((array)$desc['options'] as $key => $val) {
			$this->text('<nobr><label title="' . $key . '">');
			$checked = isset($value[$key]);
			$newName = array_merge($name, [$key]);
			$this->check($newName, $key, $checked);
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
		$between = ifsetor($desc['between'], '<br />');
		$keys = array_keys($desc['options']);
		foreach ($desc['options'] as $key => $val) {
			//debug($name, intval($value), intval($key));
			// if you need to compare intval's, do it separately
			$this->stdout .= ifsetor($desc['beforeItem']);
			$this->radioLabel($name, $key, $value == $key, $val, ifsetor($desc['more']));
			$this->stdout .= ifsetor($desc['afterItem']);
			if ($key != end($keys)) {
				$this->text($between);
			}
		}
	}

	function jsCal2($fieldName, $fieldValue, $location = 'js/JSCal2/')
	{
		if (is_string($fieldValue)) {
			$fieldValue = strtotime($fieldValue);
		}
		$index = Index::getInstance();
		$index->addCSS($location . "css/jscal2.css");
		$index->addCSS($location . "css/border-radius.css");
		$index->addCSS($location . "css/gold/gold.css");
		$index->addJS($location . "js/jscal2.js");
		$index->addJS($location . "js/lang/en.js");
		$content = '<input id="calendar-' . $fieldName . '" name="' . $this->getName($fieldName) . '" value="' .
			($fieldValue ? date('Y-m-d', $fieldValue) : '') . '"/>
		<button id="calendar-trigger-' . $fieldName . '" onclick="return false;">...</button>';
		$index->footer['jsCal2-' . $fieldName] = '<script defer="true"> 
    Calendar.setup({
        trigger    	: "calendar-trigger-' . $fieldName . '",
        inputField 	: "calendar-' . $fieldName . '",
        min			: ' . date('Ymd') . ',
/*      selection	: Calendar.dateToInt(new Date(\'' . date('Y-m-d', $fieldValue) . '\')),
        date        : Calendar.dateToInt(new Date(\'' . date('Y-m-d', $fieldValue) . '\')),
*/      selection   : Calendar.dateToInt(new Date(' . (1000 * $fieldValue) . ')),
        Calendar.dateToInt(new Date(' . (1000 * $fieldValue) . ')),
        onSelect   	: function() { this.hide() }
    })
</script>';
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
		TaylorProfiler::start(__METHOD__);
		$selected = array_keys($selected);
		$sName = $this->getName($name, '', true);
		$this->stdout .= '<div style="
			width: ' . $width . ';
			height: ' . $height . ';
			overflow: auto;
			" class="checkarray ' . $sName . '">';
		$newName = array_merge($name, array(''));
		foreach ($options as $value => $row) {
			$checked = (!is_array($selected) && $selected == $value) ||
				(is_array($selected) && in_array($value, $selected));
			$this->stdout .= '<label class="checkline_' . ($checked ? 'active' : 'normal') . '" style="white-space: nowrap;">';
			$moreStr = (is_array($more) ? $this->getAttrHTML($more) : $more);
			$moreStr = str_replace(urlencode("###KEY###"), $value, $moreStr);
			$this->check($newName, $value, $checked, $moreStr);
			$this->text('<span title="id=' . $value . '">' . (is_array($row) ? implode(', ', $row) : $row) . '</span>');
			$this->stdout .= '</label> ';
		}
		$this->stdout .= '</div>';
		TaylorProfiler::stop(__METHOD__);
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
		TaylorProfiler::start(__METHOD__);
		$this->stdout .= '<div class="radioArray">';
		foreach ($options as $value => $row) {
			$checked = (!is_array($selected) && $selected == $value) ||
				(is_array($selected) && in_array($value, $selected));
			$this->stdout .= '<div class="checkline_' . ($checked ? 'active' : 'normal') . '">';
			$this->radioLabel($name, $value, $checked, new htmlString('<span title="id=' . $value . '">' . (is_array($row) ? implode(', ', $row) : $row) . '</span>'));
			$this->stdout .= '</div>';
		}
		$this->stdout .= '</div>';
		TaylorProfiler::stop(__METHOD__);
	}

	/**
	 * Displays a disabled form input field with the name of the tree node and a hidden field with it's ID.
	 * There is a button that opens a pop-up with the tree where it's possible to select another node.
	 * $desc must have the following defined:
	 * $desc['self'], $desc['table'], $desc['titleColumn'], $desc['pid'], $desc['leaves']
	 *
	 * @param string $name
	 * @param int $valueID
	 * @param string $valueName
	 * @param array $desc
	 */
	function popuptree($name, $valueID, $valueName, $desc)
	{
		$id1 = 'popuptree' . uniqid();
		$id2 = 'popuptree' . uniqid();
		$functionName = 'accept_' . $desc['table'] . '_' . $desc['titleColumn'] . '_' . (++$GLOBALS['popuptreeCall']);
		$this->hidden($name, $valueID, 'style="width: 5em" readonly id="' . $id1 . '"'); // hidden
		$this->text(NL);
		$this->input('dummy', $valueName, 'style="width: 30em" readonly id="' . $id2 . '"');
		$this->text(NL);
		$this->popupLink($desc['self'], $desc['table'], $desc['titleColumn'], $valueID, $desc['pid'], $desc['leaves'], $id1, $id2, $functionName, $desc['selectRoot']);
	}

	function popupLink($self, $table, $titleColumn, $selected, $pid, $leaves, $id1, $id2, $functionName, $selectRoot)
	{
		$this->stdout .= str::ahref('<img src="skin/default/img/browsefolder.png">',
			'bijouTreeSelect.php?self=' . $self . '&table=' . $table . '&titleColumn=' . $titleColumn .
			'&pid=' . $pid . '&leaves=' . $leaves . '&selected=' . $selected . '&callback=' . $functionName .
			'&selectRoot=' . $selectRoot, FALSE, 'bijouTreeTarget');
		$this->stdout .= '<script>
			function ' . $functionName . '(val1, val2) {
				//alert(val1+" "+val2);
				var obj = document.getElementById("' . $id1 . '");
				obj.value = val1;
				var obj = document.getElementById("' . $id2 . '");
				obj.value = val2;
			}
		</script>';
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
	static function getAttrHTML(array $attr = NULL)
	{
		if ($attr) {
			return HTMLTag::renderAttr($attr);
		} else {
			return '';
		}
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
		$hfr = new HTMLFormRecaptcha();
		$r = Request::getInstance();
		if ($r->isAjax()) {
			$content = $hfr->getFormAjax($desc);
		} else {
			$content = $hfr->getForm($desc);
		}
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
		$hfr = new HTMLFormRecaptcha();
		$content = $hfr->getFormAjax($desc);
		$this->stdout .= $content;
		return $content;
	}

	function flipSwitch($name, $value, $checked, $more = '')
	{
		$id = uniqid('flipSwitch_');
		$this->stdout .= '<div class="onoffswitch">
    <input type="checkbox" name="' . $name . '"
     value="' . $value . '"
     class="onoffswitch-checkbox"
     id="' . $id . '" ' . ($checked ? 'checked' : '') . '
     ' . $more . '>
    <label class="onoffswitch-label" for="' . $id . '">
        <span class="onoffswitch-inner"></span>
        <span class="onoffswitch-switch"></span>
    </label>
</div>';
	}

	public function inLabel($string)
	{
		$this->stdout .= '<label>' . $string;
	}

	public function endLabel()
	{
		$this->stdout .= '</label>';
	}

	/**
	 * TODO
	 * @param       $fieldName
	 * @param       $fieldValue
	 * @param array $params
	 */
	function captcha($fieldName, $fieldValue, array $params)
	{

	}

	/**
	 * TODO
	 * @param           $fieldName
	 * @param           $fieldValue
	 * @param           $desc
	 * @param           $bool
	 * @param bool|TRUE $doDiv
	 * @param string $class
	 */
	function datatable($fieldName, $fieldValue, $desc, $bool, $doDiv = TRUE, $class = 'htmlftable')
	{

	}

	function ajaxSingleChoice($fieldName, $fieldValue, array $desc)
	{

	}

	/**
	 * TODO
	 * @param $fieldName
	 * @param $fieldValue
	 * @param $isUnlimited
	 */
	function time($fieldName, $fieldValue, $isUnlimited)
	{
		$this->input($fieldName, $fieldValue, '', 'time');
	}

	/**
	 * TODO
	 * @param $fieldName
	 * @param $tree
	 * @param $fieldValue
	 */
	function tree($fieldName, $tree, $fieldValue)
	{

	}

	public function getPrefix()
	{
		return $this->prefix;
	}

}
