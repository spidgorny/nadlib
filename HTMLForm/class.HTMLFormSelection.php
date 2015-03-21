<?php

class HTMLFormSelection extends HTMLFormType {

	/**
	 * @var array
	 */
	var $options;

	/**
	 * @var boolean
	 */
	var $autoSubmit;

	/**
	 * @var boolean
	 */
	var $multiple;

	/**
	 * @var array
	 */
	var $more = array();

	/**
	 * @var array
	 */
	var $desc = array();

	function __construct($fieldName, array $options, $selected = NULL) {
		$this->setField($fieldName);
		$this->options = $options;
		$this->value = $selected;
	}

	function render() {
		$this->form = $this->form ?: new HTMLForm();
		$content[] = "<select ".$this->form->getName($this->field, $this->multiple ? '[]' : '');
		if ($this->autoSubmit) {
			$content[] = " onchange='this.form.submit()' ";
		}
		if ($this->multiple) {
			$content[] = ' multiple="1"';
		}

		$more = $this->more;
		$more += (isset($this->desc['size']) ? array('size' => $this->desc['size']) : array());
		$more += (isset($this->desc['id']) ? array('id' => $this->desc['id']) : array());
		if (isset($this->desc['more'])) {
			$more += is_array($this->desc['more'])
				? $this->desc['more']
				: HTMLTag::parseAttributes($this->desc['more']);
		}
		$content[] = HTMLTag::renderAttr($more) . ">\n";

		$content[] = $this->getSelectionOptions($this->options, $this->value, $this->desc);
		$content[] = "</select>\n";
		return new MergedContent($content);
	}

	/**
	 * @param array $aOptions
	 * @param $default
	 * @param array $desc
	 * 		boolean '===' - compare value and default strictly (BUG: integer looking string keys will be treated as integer)
	 * 		string 'classAsValuePrefix' - will prefix value with the value of this param with space replaced with _
	 *      boolean 'useTitle'
	 * @return string
	 */
	function getSelectionOptions(array $aOptions, $default, array $desc = array()) {
		//Debug::debug_args($aOptions);
		$content = '';
		foreach ($aOptions as $value => $option) {
			/** PHP feature gettype($value) is integer
			 * even if it's string in an array!!! */
			if (ifsetor($desc['==='])) {
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
				$option->attr('selected', $selected);
				//$option->content .= ' '.$value.' '.$default;
				$content .= $option;
			} else if ($option instanceof Recursive) {
				$content .= '<optgroup label="'.$option.'">';
				$content .= $this->getSelectionOptions($option->getChildren(), $default, $desc);
				$content .= '</optgroup>';
			} else {
				$content .= "<option value=\"$value\"";
				if ($selected) {
					$content .= " selected";
				}
				if (isset($desc['classAsValuePrefix'])) {
					$content .= ' class="'.$desc['classAsValuePrefix'].str_replace(' ', '_', $value).'"';
				}
				if (isset($desc['useTitle']) && $desc['useTitle'] == true) {
					$content .= ' title="'.strip_tags($option).'"';
				}
				$content .= ">$option</option>\n";
			}
		}
		return $content;
	}

}
