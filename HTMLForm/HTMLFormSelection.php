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
	 * @var HTMLFormField
	 */
	var $desc = array();

	function __construct($fieldName, array $options = NULL, $selected = NULL) {
		$this->setField($fieldName);
		$this->options = $options;
		$this->value = $selected;
	}

	function setDesc(array $desc) {
		$this->desc = new HTMLFormField($desc);
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
		$more += (isset($this->desc['size'])
			? array('size' => $this->desc['size']) : array());
		$more += (isset($this->desc['id'])
			? array('id' => $this->desc['id']) : array());

		//debug($this->desc); exit();
		$more += $this->desc->isObligatory()
				? array('required' => true) : array();
		if (isset($this->desc['more'])) {
			$more += is_array($this->desc['more'])
				? $this->desc['more']
				: HTMLTag::parseAttributes($this->desc['more']);
		}
		$content[] = HTMLTag::renderAttr($more) . ">\n";

		if (is_null($this->options)) {
			$this->options = $this->fetchSelectionOptions($this->desc->getArray());
		}
		$content[] = $this->getSelectionOptions($this->options, $this->value, $this->desc->getArray());
		$content[] = "</select>\n";

		$mc = new MergedContent($content);
		return $mc->getContent();
	}

	/**
	 * @param array $aOptions
	 * @param $default  array|mixed
	 * @param array $desc
	 * 		boolean '===' - compare value and default strictly (BUG: integer looking string keys will be treated as integer)
	 * 		string 'classAsValuePrefix' - will prefix value with the value of this param with space replaced with _
	 *      boolean 'useTitle'
	 * @return string
	 */
	function getSelectionOptions(array $aOptions, $default, array $desc = array()) {
		//Debug::debug_args($aOptions);
		$content = '';
		/** PHP feature gettype($value) is integer even if it's string in an array!!! */
		foreach ($aOptions as $value => $option) {
			if (ifsetor($desc['==='])) {
				$selected = $default === $value;
			} else {
				if ($this->field == 'relcoordinator') {
					//debug($value, $default);
				}
				if ((is_array($default) && in_array($value, $default))
					|| (!is_array($default) && $default == $value)) {
					$selected = true;
				} else {
					$selected = false;
				}
			}
			//debug($default, $value, $selected);
			if ($option instanceof HTMLTag) {
				if ($selected) {
					$option->attr('selected', $selected);
				}
				//$option->content .= ' '.$value.' '.$default;
				$content .= $option;
			} elseif ($option instanceof Recursive) {
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

	/**
	 * Retrieves data from DB
	 * Provide either 'options' assoc array
	 * OR a DB 'table', 'title' column, 'idField' column 'where' and 'order'
	 * @param array $desc
	 * @return array
	 */
	function fetchSelectionOptions(array $desc) {
		if (ifsetor($desc['from']) && $desc['title']) {
			/** @var dbLayerBase $db */
			$db = Config::getInstance()->getDB();
			$options = $db->getTableOptions($desc['from'],
				$desc['title'],
				isset($desc['where']) 	? $desc['where'] : array(),
				isset($desc['order']) 	? $desc['order'] : '',
				isset($desc['idField']) ? $desc['idField'] : 'id',
				ifsetor($desc['prefix'])
			//$desc['noDeleted']
			);
			//debug($db->lastQuery, $options);
		} else {
			$options = array();
		}
		if (isset($desc['options'])) {
			$options += $desc['options'];
		}
		if (isset($desc['null'])) {
			$options = array(NULL => "---") + $options;
		}
		//Debug::debug_args($options, $desc['options']);
		return $options;
	}

}