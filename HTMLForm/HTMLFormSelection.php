<?php

class HTMLFormSelection extends HTMLFormField
{

	/**
	 * @var ?array
	 */
	public $options;

	/**
	 * @var boolean
	 */
	public $autoSubmit;

	/**
	 * @var boolean
	 */
	public $multiple;

	/**
	 * @var array
	 */
	public $more = [];

	/**
	 * @var HTMLFormField|array
	 */
	public $desc = [];

	public function __construct($fieldName, ?array $options = NULL, $selected = NULL)
	{
		parent::__construct([], $fieldName);
		$this->setField($fieldName);
		$this->options = $options;
		$this->value = $selected;
	}

	public function setDesc(array $desc): void
	{
		$this->desc = new HTMLFormField($desc);
	}

	public function render(): string|array|\ToStringable
	{
		$content[] = "<select " .
			$this->form->getName($this->field, $this->multiple ? '[]' : '');
		if ($this->autoSubmit) {
			$content[] = " onchange='this.form.submit()' ";
		}

		if ($this->multiple) {
			$content[] = ' multiple="1"';
		}

		$more = $this->more;
		$more += (isset($this->desc['size'])
			? ['size' => $this->desc['size']] : []);
		$more += (isset($this->desc['id'])
			? ['id' => $this->desc['id']] : []);

		//debug($this->desc); exit();
		$more += $this->desc->isObligatory()
			? ['required' => true] : [];
		if (isset($this->desc['more'])) {
			$more += is_array($this->desc['more'])
				? $this->desc['more']
				: HTMLTag::parseAttributes($this->desc['more']);
		}

		$content[] = ' ' . HTMLTag::renderAttr($more) . ">\n";

		if (is_null($this->options)) {
			$this->options = $this->fetchSelectionOptions($this->desc->getArray());
		}

		if ($this->desc['null']) {
			$this->options = [NULL => "---"] + $this->options;
		}

		$content[] = $this->getSelectionOptions($this->options, $this->value, $this->desc->getArray());
		$content[] = "</select>\n";

		$mc = new MergedContent($content);
		return $mc->getContent();
	}

	/**
	 * Retrieves data from DB
	 * Provide either 'options' assoc array
	 * OR a DB 'table', 'title' column, 'idField' column 'where' and 'order'
	 * @return array
	 */
	public function fetchSelectionOptions(array $desc)
	{
		if (ifsetor($desc['from']) && $desc['title']) {
			/** @var DBLayerBase $db */
			$db = Config::getInstance()->getDB();
			$options = $db->getTableOptions($desc['from'],
				$desc['title'],
				$desc['where'] ?? [],
				$desc['order'] ?? '',
				$desc['idField'] ?? 'id',
				ifsetor($desc['prefix'])
			//$desc['noDeleted']
			);
			//debug($db->lastQuery, $options);
		} else {
			$options = [];
		}

		if (isset($desc['options'])) {
			$options += $desc['options'];
		}

		if (isset($desc['map'])) {
			$options = array_map($desc['map'], $options);
		}

		//Debug::debug_args($options, $desc['options']);
		return $options;
	}

	/**
	 * @param $default  array|mixed
	 * @param array $desc
	 *    boolean '===' - compare value and default strictly (BUG: integer looking string keys will be treated as integer)
	 *    string 'classAsValuePrefix' - will prefix value with the value of this param with space replaced with _
	 *      boolean 'useTitle'
	 */
	public function getSelectionOptions(array $aOptions, $default, array $desc = []): string
	{
		$content = '';
		//Debug::debug_args($aOptions);
		/** PHP feature gettype($value) is integer even if it's string in an array!!! */
		//debug($this->field, $this->value);
		foreach ($aOptions as $value => $option) {
			if (ifsetor($desc['==='])) {
				$selected = $default === $value;
			} else {
				$arrayContains = is_array($default) && in_array($value, $default);
				// === is required to not match 0:int with any other string
				// === does not prevent NULL from being selected
				// ==  does better compare POST value with DB value
				$justEquals = !is_array($default) && $default == $value;
//				if ($this->field[0] === 'id_person' && $value == 327) {
				//debug($value, $default, $arrayContains, $justEquals);
//				}
				$selected = $arrayContains || $justEquals;
			}

			//debug($default, $value, $selected);
			if ($option instanceof HTMLTag) {
				if ($selected) {
					$option->attr('selected', $selected);
				}

				//$option->content .= ' '.$value.' '.$default;
				$content .= $option;
			} elseif ($option instanceof Recursive) {
				$content .= '<optgroup label="' . $option . '">';
				$content .= $this->getSelectionOptions($option->getChildren(), $default, $desc);
				$content .= '</optgroup>';
			} else {
				$content .= sprintf('<option value="%s"', $value);
				if ($selected) {
					$content .= " selected";
				}

				if (isset($desc['classAsValuePrefix'])) {
					$content .= ' class="' . $desc['classAsValuePrefix'] . str_replace(' ', '_', $value) . '"';
				}

				if (isset($desc['useTitle']) && $desc['useTitle'] == true) {
					$content .= ' title="' . strip_tags($option) . '"';
				}

				$content .= ">{$option}</option>\n";
			}
		}

		return $content;
	}

}
