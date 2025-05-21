<?php

class HTMLForm implements ToStringable
{

	public const METHOD_GET = 'GET';

	public const METHOD_POST = 'POST';

	public $stdout = "";

	public $enctype = "";

	public $target = "";

	/**
	 * Deprecated use for maybe XSS class in some form fields.
	 * Now it's the class name (or just a unique identifier of the form) to be used
	 * with XSRF protection.
	 * @var string
	 */
	public $class = "";

	/**
	 * @var array
	 */
	public $formMore = [
		//'class' => '',
	];

	public $debug = false;

	protected $action = "";

	protected $method = self::METHOD_POST;

	protected $prefix = [];

	protected $fieldset;

	protected $fieldsetMore = [];

	public function __construct($action = '', $id = null)
	{
		$this->action = $action;
		if ($id) {
			$this->formMore['id'] = $id;
		}
	}

	public function formHideArray(array $ar): string
	{
		$content = [];
		foreach ($ar as $k => $a) {
			if (is_array($a)) {
				$this->prefix[] = $k;
				$this->formHideArray($a);
				array_pop($this->prefix);
			} else {
				//$ret .= "<input type=hidden name=" . $name . ($name?"[":"") . $k . ($name?"]":"") . " value='$a'>";
				$content[] = $this->hidden($k, $a);
			}
		}
		return $this->s($content);
	}

	public function hidden($name, $value, array $more = []): string
	{
		$content = $this->getInput("hidden", $name, $value, $more);
		$this->stdout .= $content;
		return $content;
	}

	/**
	 * @param string|array $name
	 * @param array $more - may be array
	 *
	 */
	public function getInput(string $type, $name, $value = null, array $more = [], string $extraClass = '', $namePlus = ''): string
	{
//		debug($type, $name, $value, $more, $extraClass, $namePlus);
		$attrs = [];
		$attrs['type'] = $type;
		$attrs['class'] = trim($type . ' ' . $extraClass . ' ' . ifsetor($more['class']));
		$attrs['name'] = $this->getName($name, $namePlus, true);
		if ($value || $value === 0) {
			$isHTML = $value instanceof HtmlString;
			//debug($value, $isHTML);
			if (!$isHTML) {
				//$value = htmlspecialchars($value, ENT_QUOTES);
				// escaped by HTMLTag::renderAttr
			} else {
				$value = str_replace('"', '&quot;', $value);
			}

			$attrs['value'] = $value;
		}

		$attrs += $more;
		return "<input " . self::getAttrHTML($attrs) . " />\n";
	}

	public function getName($name, string $namePlus = '', $onlyValue = false): string
	{
		$a = '';
		$path = $this->prefix;
		$path = array_merge($path, is_array($name) ? $name : [$name]);

		$first = array_shift($path);
		$a .= $first;
		if ($path !== []) {
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
	 * Converts an assoc array into valid HTML name="value" string
	 *
	 */
	public static function getAttrHTML(?array $attr = null): string
	{
		if ($attr) {
			return HTMLTag::renderAttr($attr);
		}

		return '';
	}

	public function action($action): void
	{
		$this->action = $action;
	}

	public function method($method): void
	{
		$this->method = $method;
	}

	public function target($target): void
	{
		$this->target = $target;
	}

	/**
	 * Set empty to unset prefix
	 *
	 * @param string|null $p
	 *
	 * @return $this
	 */
	public function prefix($p = ''): static
	{
		if (is_array($p)) {
			$this->prefix = $p;
		} elseif ($p) {
			$this->prefix = [$p];
		} else {
			$this->prefix = [];
		}

		return $this;
	}

	public function fieldset($name, $more = []): void
	{
		$this->fieldset = $name;
		$this->fieldsetMore = $more;
	}

	public function getFieldset()
	{
		return $this->fieldset;
	}

	public function getNameField($name, string $namePlus = '', $onlyValue = false): string
	{
		return $this->getName($name, $namePlus, $onlyValue);
	}

	public function getNameTag($name): string
	{
		return $this->getName($name, '', false);
	}

	public function label(string $for, string $text): string
	{
		return '<label for="' . $for . '">' . $text . '</label>';
	}

	/**
	 *
	 * Table row with $text and input
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function tinput(string $text, $name, $value = "", array $more = []): void
	{
		$this->text('<tr><td>' . $text . '</td><td>');
		$this->input($name, $value, $more);
		$this->text('</td></tr>');
	}

	public function text($a): string
	{
		return MergedContent::mergeStringArrayRecursive($a);
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param array $more - may be array
	 */
	public function input($name, $value = "", array $more = [], string $type = 'text', $extraClass = ''): string
	{
		//$value = htmlspecialchars($value, ENT_QUOTES);
		//$this->stdout .= '<input type="'.$type.'" '.$this->getName($name).' '.$more.' value="'.$value.'" />'."\n";
		return $this->getInput($type, $name, $value, $more, $extraClass);
	}

	public function password($name, $value = "", array $desc = []): string
	{
		return $this->getInput("password", $name, $value, $desc, ifsetor($desc['class']));
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param string $checked - must be value
	 */
	public function radio($name, $value, $checked, array $more = []): void
	{
		//$value = htmlspecialchars($value, ENT_QUOTES);
		//$this->stdout .= "<input type=radio ".$this->getName($name)." value=\"$value\" ".($value==$checked?"checked":"")." $more>";
		$this->stdout .= $this->getInput("radio", $name, $value, ($value == $checked ? ["checked" => 'checked'] : []) + $more);
	}

	public function labelCheck($name, $value = 1, $checked = false, array $more = [], $autoSubmit = false, string $label = ''): void
	{
		$this->stdout .= '<label>';
		$this->check($name, $value, $checked, $more, $autoSubmit);
		$this->stdout .= ' ' . ($label) . '</label>';
	}

	public function check($name, $value = 1, $checked = false, array $more = [], $autoSubmit = false, array $desc = []): HTMLFormCheckbox
	{
		$desc['more'] = $more;
		$desc['autoSubmit'] = $autoSubmit;
		$desc['value'] = $value;
		$box = new HTMLFormCheckbox($name, $checked, $desc);
		$box->form = $this;    // for prefix to work
		return $box;
	}

	public function checkLabel($name, $value = 1, $checked = false, array $more = [], $autoSubmit = false, string $label = ''): void
	{
		$this->stdout .= '<div>';
		$id = $this->getID($this->getPrefix() + [$name]);
		$this->check($name, $value, $checked, $more + ['id' => $id], $autoSubmit);
		$this->stdout .= ' <label for="' . $id . '">' . ($label) . '</label></div>';
	}

	public function getID($from): string
	{
		$elementID = is_array($from) ? 'id-' . implode('-', $from) : 'id-' . $from;

		if ($elementID === '0') {
			$elementID = uniqid('id-', true);
		}

		return $elementID;
	}

	/**
	 * @return array
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

	public function file($name, array $desc = []): string
	{
		//$this->stdout .= "<input type=file ".$this->getName($name)." ".$desc['more'].">";
		$this->method = 'POST';
		$this->enctype = "multipart/form-data";
		return $this->getInput("file", $name, '', ifsetor($desc['more'], []), ifsetor($desc['class'], ''));
	}

	/**
	 * Default value is no longer "today"
	 *
	 * @param $name
	 * @param $value
	 * @param array $desc
	 */
	public function date($name, $value, array $desc = []): string
	{
//		debug($value);
		$format = ifsetor($desc['format']) ? $desc['format'] : 'd.m.Y';
		if (is_numeric($value)) {
			$value = date($format, $value);
		} elseif (!$value) {
			//$value = date('d.m.Y');
		}

		if (ifsetor($desc['more']) && !is_array($desc['more'])) {
			debug($name, $desc);
			debug_pre_print_backtrace();
			exit();
			//throw new InvalidArgumentException(__METHOD__ . ' $desc[more] is not array');
		}

		$extraClass = '';
		if (ifsetor($desc['error'])) {
			$extraClass .= ' is-invalid';
		}

		return $this->input($name, $value,
			(isset($desc['id']) ? ['id' => $desc['id']] : []) +
			ifsetor($desc['more'], []),
			'date',
			$extraClass
		);
	}

	/**
	 * Make sure to include the JSCal2 JS in advance.
	 * And set defer=false
	 *
	 * @param $name
	 * @param array $params
	 *
	 * @throws Exception
	 */
	public function datepopup($name, $value = null, string $type = "input", $activator = null, $id = null, $params = []): string
	{
		$id = $id ?: uniqid('datepopup', true);
		$fullname = $this->getName($name, '', true);
		if (is_numeric($value)) {
			$value = $value > 0 ? date('Y-m-d', $value) : '';
		}

		$this->stdout .= '
		<input type="' . $type . '"
			name="' . $fullname . '"
			id="id_field_' . $id . '"
			value="' . $value . '" />
			' . ($activator ?: '<button type="button"
			 id="id_button_' . $id . '"
			 style="width: auto">...</button>');

		// this will be appended to the footer
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
//		if (class_exists('Index')) {
//			$index = Index::getInstance();
//			$index->footer['init_cal_' . $id] = $script;
//			return '';
//		}

		return $script;
	}

	public function datepopup2($name, $value = null, $plusConfig = '', array $desc = [])
	{
		$dp2 = new HTMLFormDatePopup2($this, $name, $value, $desc + [
				'plusConfig' => $plusConfig,
				'phpFormat' => 'Y-m-d',
			]);
		$this->stdout .= $dp2 . '';

		return $dp2->id;
	}

	public function money($name, $value, array $desc): string
	{
		if (!$value) {
			$value = "0.00";
		}

		return $this->input($name, $value, $desc['more']) . $this->text("&euro;");
	}

	public function textarea($name, $value = null, $more = ''): string
	{
		$more = is_array($more) ? self::getAttrHTML($more) : $more;
		return "<textarea " . $this->getName($name) . sprintf(' %s>', $more) .
			htmlspecialchars($value ?? '') .
			"</textarea>";
	}

	/**
	 * Changelog: second $more parameter was removed, please use $params instead
	 *
	 * @param string|null $value
	 *
	 */
	public function submit($value = null, array $params = []): string
	{
		$field = new HTMLSubmit($value, $params);
		$field->setForm($this);
		return $this->add($field);
	}

	public function add(HTMLFormFieldInterface $field): string
	{
		$field->setForm($this);
		return $this->s($field->render());
	}

	public function s($content): string
	{
		return MergedContent::mergeStringArrayRecursive($content);
	}

	/**
	 * It was doing echo() since 2002 - in 2017 it's doing return
	 */
	public function render(): string
	{
		return $this->getContent();
	}

	public function getContent(): string
	{
		return $this->getFormTag() . $this->stdout . $this->getFormEnd();
	}

	public function getFormTag(): string
	{
		$attributes = is_string($this->formMore) ? HTMLTag::parseAttributes($this->formMore) : $this->formMore;

		if ($this->action) {
			$attributes += [
				'action' => $this->action,
			];
		}

		if ($this->method) {
			$attributes += [
				'method' => $this->method,
			];
		}

		if ($this->enctype) {
			$attributes["enctype"] = $this->enctype;
		}

		if ($this->target) {
			$attributes['target'] = $this->target;
		}

		$a = "<form " . HTMLTag::renderAttr($attributes) . ">\n";
		if ($this->fieldset) {
			$a .= "<fieldset " . $this->getAttrHTML($this->fieldsetMore) . ">" .
				"<legend>" . $this->fieldset . "</legend>";
			$a .= is_array($this->fieldsetMore) ? implode(' ', $this->fieldsetMore) : $this->fieldsetMore;
		}

		return $a;
	}

	public function getFormEnd(): string
	{
		$a = "</form>\n";
		if ($this->fieldset) {
			$a .= "</fieldset>";
		}

		return $a;
	}

	public function button($innerHTML = null, array $more = []): string
	{
		$more = HTMLTag::renderAttr($more);
		return "<button {$more}>{$innerHTML}</button>\n";
	}

	public function image($value = null, array $more = [], array $desc = []): string
	{
		$sMore = HTMLTag::renderAttr($more);
		$value = htmlspecialchars($value, ENT_QUOTES);
		return "<input type=image
		src=" . $desc['src'] . "
		class='submitbutton' " .
			($value !== '' && $value !== '0' ? sprintf('value="%s"', $value) : "") . " {$sMore}>\n";
	}

	public function reset($value = null, $more = ""): void
	{
		$value = htmlspecialchars($value, ENT_QUOTES);
		$this->stdout .= "<input type=reset class=submit " . ($value !== '' && $value !== '0' ? sprintf('value="%s"', $value) : "") . " {$more}>\n";
	}

	public function getBuffer()
	{
		return $this->stdout;
	}

	public function combo($fieldName, array $desc): array
	{
		$content = [];
		if ($desc['from']) {
			// TODO: replace with SQLBuilder->getTableOptions()
			$db = Config::getInstance()->getDB();
			$options = $db->fetchAll('SELECT DISTINCT ' . $desc['title'] . ' AS value
			FROM ' . $desc['from'] . '
			WHERE NOT hidden AND NOT deleted
			ORDER BY value');
			$options = ArrayPlus::from($options)->IDalize('value', 'value');
		} else {
			$options = $desc['options'];
		}

		if (class_exists('Index')) {
//			Index::getInstance()->addJQuery();
			$content[] = $this->selection($fieldName, $options, $desc['value'], false, 'onchange="jQuery(this).nextAll(\'input\').val(
			jQuery(this).val()
		);"', false, $desc);
			$content[] = $this->input($fieldName, $desc['value']);
		}
		return $content;
	}

	/**
	 * @param string|string[] $name
	 * @param string|int|null $default
	 * @param bool $autoSubmit
	 * @param bool $multiple
	 *
	 * @see renderSelectionOptions
	 */
	public function selection(
		$name,
		?array $aOptions = null,  // should allow null in case we load options from db
		$default = null,
		$autoSubmit = false,
		array $more = [],
		$multiple = false,
		array $desc = []
	): string|array|ToStringable
	{
		$sel = new HTMLFormSelection($name, $aOptions, $default);
		$sel->autoSubmit = $autoSubmit;
		$sel->more = is_string($more) ? HTMLTag::parseAttributes($more) : $more;
		$sel->multiple = $multiple;
		$sel->setDesc($desc);
		//debug($name, $desc);
		$sel->setForm($this);
		return $sel->render();
	}

	/**
	 * A set of checkboxes. The value is COMMA SEPARATED!
	 *
	 * @param string|array $name
	 * @param array|string $value - CSV or array
	 * @param array $desc
	 *        'between' - text that separates checkboxes (default ", ")
	 *
	 * @return $this
	 */
	public function set($name, $value, array $desc): array
	{
		$content = [];
		if ($value) {
			if (!is_array($value)) {
				$value = trimExplode(',', $value);
			}
		} else {
			$value = [];
		}

		$aName = is_array($name) ? $name : [$name];
		$newName = array_merge($aName, ['']);    // []
		$tmp = $this->class;
		$this->class = 'submit';
		$between = ifsetor($desc['between'], ', ');
		foreach ((array)$desc['options'] as $key => $val) {
			$content[] = $this->text('<nobr><label title="' . $key . '">');
			$checked = in_array($key, $value);
			//debug($key, $value, $checked);
			$content[] = $this->check($newName, $key, $checked);
			$content[] = $this->text(' ' . $val . '</label></nobr>');
			if ($val != end($desc['options'])) {
				$content[] = $this->text($between);
			}
		}

		$this->class = $tmp;

		return $content;
	}

	/**
	 * This is checking using isset()
	 *
	 * @param $name
	 * @param array $value
	 */
	public function keyset($name, $value = [], array $desc = []): array
	{
		$content = [];
		if ($value) {
			if (!is_array($value)) {
				$value = explode(',', $value);
			}
		} else {
			$value = [];
		}

		$tmp = $this->class;
		$this->class = 'submit';
		$between = ifsetor($desc['between'], ', ');
//		debug($desc['options']);
		foreach ((array)$desc['options'] as $key => $val) {
			$content[] = $this->text('<nobr><label title="' . $key . '">');
			$checked = isset($value[$key]);
			$newName = array_merge($name, [$key]);
			$content[] = $this->check($newName, $key, $checked);
			$content[] = $this->text(' ' . $val . '</label></nobr>');
			if ($val != end($desc['options'])) {
				$content[] = $this->text($between);
			}
		}

		$this->class = $tmp;
		return $content;
	}

	/**
	 * A set of radio.
	 *
	 * @param string $name
	 * @param int $value
	 * @param array $desc
	 *        'between' - text separating the options, default <br />
	 */
	public function radioset($name, $value, array $desc): array
	{
		$content = [];
		$between = ifsetor($desc['between'], '<br />');
		$keys = array_keys($desc['options']);
		foreach ($desc['options'] as $key => $val) {
			//debug($name, intval($value), intval($key));
			// if you need to compare intval's, do it separately
			$content[] = ifsetor($desc['beforeItem']);
			$content[] = $this->radioLabel($name, $key, $value == $key, $val, ifsetor($desc['more']));
			$content[] = ifsetor($desc['afterItem']);
			if ($key != end($keys)) {
				$content[] = $this->text($between);
			}
		}
		return $content;
	}

	/**
	 * @param $name
	 * @param $value
	 * @param bool $checked
	 * @param string $label
	 * @param string $more
	 */
	public function radioLabel($name, $value, $checked, $label = "", $more = ''): array
	{
		$value = htmlspecialchars($value, ENT_QUOTES);
		$aName = is_array($name) ? $name : [];
		$id = implode('_', array_merge($this->prefix, $aName)) . "_" . $value;
		$content[] = '<label class="radio" for="' . $id . '">
		<input
			type="radio"
			' . $this->getName($name) . '
			value="' . htmlspecialchars($value, ENT_QUOTES) . '" ' .
			($checked ? "checked" : "") . '
			id="' . $id . '"
			' . (is_array($more) ? $this->getAttrHTML($more) : $more) . '> ';
		$content[] = $this->hsc($label) . "</label>";
		return $content;
	}

	public function hsc($label): HtmlString|string
	{
		if ($label instanceof HtmlString) {
			return $label;
		}

		return htmlspecialchars($label, ENT_QUOTES);
	}

	public function jsCal2(string $fieldName, $fieldValue, string $location = 'js/JSCal2/'): string
	{
		if (is_string($fieldValue)) {
			$fieldValue = strtotime($fieldValue);
		}

//		$index = Index::getInstance();
//		$index->addCSS($location . "css/jscal2.css");
//		$index->addCSS($location . "css/border-radius.css");
//		$index->addCSS($location . "css/gold/gold.css");
//		$index->addJS($location . "js/jscal2.js");
//		$index->addJS($location . "js/lang/en.js");

		$content = '<input id="calendar-' . $fieldName . '" name="' . $this->getName($fieldName) . '" value="' .
			($fieldValue ? date('Y-m-d', $fieldValue) : '') . '"/>
		<button id="calendar-trigger-' . $fieldName . '" onclick="return false;">...</button>';

		$content .= '<script defer="true">
document.observe("dom:loaded", () => {
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
});
</script>';

		return $content;
	}

	/**
	 * Makes TWO input fields. Keys: from, till. Value must be assiciative array too.
	 */
	public function interval($name, array $value, $more = ''): void
	{
		$name1 = [$name, 'from'];
		$value1 = $value['from'];
		$value1 = htmlspecialchars($value1, ENT_QUOTES);
		$this->stdout .= "von: <input type=text " . $this->getName($name1) . sprintf(' %s value="', $more) . $value1 . "\" size='10'>\n";
		$name2 = [$name, 'till'];
		$value2 = $value['till'];
		$value2 = htmlspecialchars($value2, ENT_QUOTES);
		$this->stdout .= "bis: <input type=text " . $this->getName($name2) . sprintf(' %s value="', $more) . $value2 . "\" size='10'>\n";
	}

	/**
	 * A set of checkboxes in a div.checkarray. Values are provided as an array
	 *
	 * @param array $name
	 * @param array $selected - only keys are used
	 * @param array $more
	 * @param int $width
	 * @see set()
	 */
	public function checkarray(array $name, array $options, array $selected, $more = [], string $height = 'auto', $width = 350): array
	{
		TaylorProfiler::start(__METHOD__);
		$selected = array_keys($selected);
		$sName = $this->getName($name, '', true);
		$content[] = '<div style="
			width: ' . $width . ';
			height: ' . $height . ';
			overflow: auto;
			" class="checkarray ' . $sName . '">';
		$newName = array_merge($name, ['']);
		foreach ($options as $value => $row) {
			$checked = (!is_array($selected) && $selected == $value) ||
				(is_array($selected) && in_array($value, $selected));
			$content[] = '<label class="checkline_' . ($checked ? 'active' : 'normal') . '" style="white-space: nowrap;">';
			$more = collect($more)->map(fn($val) => str_replace(urlencode("###KEY###"), $value, $val))->toArray();
			$content[] = $this->check($newName, $value, $checked, $more);
			$content[] = $this->text('<span title="id=' . $value . '">' . (is_array($row) ? implode(', ', $row) : $row) . '</span>');
			$content[] = '</label> ';
		}

		$content[] = '</div>';
		TaylorProfiler::stop(__METHOD__);
		return $content;
	}

	/**
	 * This one makes a span with a title and is showing data in a specific width
	 *
	 * @param $name
	 * @param $selected
	 * @see $this->radioset()
	 */
	public function radioArray($name, array $options, $selected): array
	{
		TaylorProfiler::start(__METHOD__);
		$content[] = '<div class="radioArray">';
		foreach ($options as $value => $row) {
			$checked = (!is_array($selected) && $selected == $value) ||
				(is_array($selected) && in_array($value, $selected));
			$content[] = '<div class="checkline_' . ($checked ? 'active' : 'normal') . '">';
			$content[] = $this->radioLabel($name, $value, $checked, new HtmlString('<span title="id=' . $value . '">' . (is_array($row) ? implode(', ', $row) : $row) . '</span>'));
			$content[] = '</div>';
		}

		$content[] = '</div>';
		TaylorProfiler::stop(__METHOD__);
		return $content;
	}

	public function __toString(): string
	{
		return $this->getContent();
	}

	public function formColorSelector($name, $default): void
	{
		$colors = explode(",", "#FFFFFF,#CCCCCC,#999999,#990099,#993300,#009900,#000099,#FF0000,#999900,#00FF00,#0000FF,#FF00FF,#FF9933,#FFFF00,#00FFFF");
		print(sprintf("<select name=%s id=%s style='width: auto'>", $name, $name));
		foreach ($colors as $color) {
			print(sprintf("<option style='background-color: %s' value='%s' ", $color, $color) . ($color == $default ? "selected" : "") . ">Color</option>");
		}

		print("</select>");
	}

	public function recaptcha(array $desc = [])
	{
		$hfr = new HTMLFormRecaptcha();
		$r = Request::getInstance();
		$content = $r->isAjax() ? $hfr->getFormAjax($desc) : $hfr->getForm($desc);

		$this->stdout .= $content;

		return $content;
	}

	/**
	 * Make sure to implement in form onSubmit() something like
	 * $(\'input[name="recaptcha_challenge_field"]\').val(Recaptcha.get_challenge());
	 * $(\'input[name="recaptcha_response_field"]\').val(Recaptcha.get_response());
	 *
	 *
	 */
	public function recaptchaAjax(array $desc): string
	{
		$hfr = new HTMLFormRecaptcha();
		$content = $hfr->getFormAjax($desc);
		$this->stdout .= $content;

		return $content;
	}

	public function flipSwitch(string $name, string $value, $checked, string $more = ''): void
	{
		$id = uniqid('flipSwitch_', true);
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

	public function inLabel(string $string, array $attrs = []): string
	{
		return '<label ' . HTMLTag::renderAttr($attrs) . '>' . $string;
	}

	public function endLabel(): string
	{
		return '</label>' . PHP_EOL;
	}

	/**
	 * TODO
	 *
	 * @param       $fieldName
	 * @param       $fieldValue
	 */
	public function captcha($fieldName, $fieldValue, array $params)
	{
		return 'not implemented';
	}

	/**
	 * TODO
	 *
	 * @param           $fieldName
	 * @param           $fieldValue
	 * @param           $desc
	 * @param           $bool
	 * @param bool|TRUE $doDiv
	 * @param string $class
	 */
	public function datatable($fieldName, $fieldValue, $desc, $bool, $doDiv = true, $class = 'htmlftable')
	{
		return 'not implemented';
	}

	public function ajaxSingleChoice($fieldName, $fieldValue, array $desc)
	{
		return 'not implemented';
	}

	/**
	 * TODO
	 *
	 * @param $fieldName
	 * @param $fieldValue
	 * @param $isUnlimited
	 */
	public function time($fieldName, $fieldValue, $isUnlimited): string
	{
		return $this->input($fieldName, $fieldValue, [], 'time');
	}

	/**
	 * TODO
	 *
	 * @param $fieldName
	 * @param $tree
	 * @param $fieldValue
	 */
	public function tree($fieldName, $tree, $fieldValue)
	{
		return 'not implemented';
	}

}
