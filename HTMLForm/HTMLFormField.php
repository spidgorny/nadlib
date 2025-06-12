<?php

/**
 * Class HTMLFormField
 * This represents an element of HTMLFormTable desc array
 * Do not confuse it with HTMLFormType and it's descendants.
 */
class HTMLFormField extends HTMLFormType
{
	use MagicDataProps;
	use ArrayAccessData;

	public $elementID;

	/**
	 * All different desc parameters for the form element.
	 * Temporary solution while we transition from array assoc
	 * to the HTMLFormField with specific members
	 * @var array
	 */
	public $data = [];

	/**
	 * @var string
	 */
	public $fieldName;

	/**
	 * @var HTMLForm
	 */
	public $form;

	/**
	 * Filled when render()
	 * @var string
	 */
	protected $content;

	public function __construct(array|HTMLFormType $desc, $fieldName = null)
	{
		$this->data = $desc;
		if ($fieldName) {
			$this->setField($fieldName);
		}

		$this->form = new HTMLForm();
	}

	public function setField($fieldName): void
	{
		parent::setField($fieldName);
		$this->fieldName = $fieldName;
	}

	public function isOptional(): bool
	{
		return !$this->isObligatory();
	}

	public function isObligatory(): bool
	{
		$type = $this->getTypeString();
		return !ifsetor($this->data['optional']) &&
			!in_array($type, ['check', 'checkbox', 'submit']);
	}

	public function getTypeString(): ?string
	{
		$type = ifsetor($this->data['type']);
		if (is_null($type)) {
			return null;
		}

		return is_string($type) ? $type : get_class($type);
	}

	public function render(): string|array|ToStringable
	{
		$fieldName = $this->fieldName;
		$desc = $this;
		$fieldValue = $this['value'];
//		debug($fieldValue);
		if ($desc['prefix']) {
			$content[] = $this->form->text($desc['prefix']);
		}

//		debug($desc['id']);
		if (!empty($desc['id'])) {
			$elementID = $desc['id'];
		} elseif (!empty($desc['more']['id'])) {
			$elementID = $desc['more']['id'];
		} else {
			$elementID = $this->getID($this->fieldName);
			$desc['id'] = $elementID;
		}

		$this['elementID'] = $elementID;
//		debug($elementID);

		$type = ifsetor($desc['type']);
		if ($type instanceof HTMLFormType) {
			$type->setField($fieldName);
			$type->setForm($this->form);
			if (ifsetor($desc['value'])) {
				$type->setValue($desc['value']);
			}

//			if (ifsetor($desc['jsParams'])) {
//				$type->jsParams = $desc['jsParams'] ?: [];
//			}

			$type->desc = $desc;
			$content[] = $type->render();
		} elseif ($type instanceof HTMLFormCollection) {
			/** @var HTMLFormCollection $type */
			$type->setField($fieldName);
			$type->setForm($this->form);
			$type->setValue($desc['value']);
			$type->setDesc($desc);
			$content[] = $type->renderHTMLForm();
		} else {
			$content[] = $this->switchTypeRaw($type, $fieldValue, $fieldName);
		}

		$this->content = MergedContent::mergeStringArrayRecursive($content);
		return $this->content;
	}

	public function getID($from): string
	{
		$elementID = is_array($from) ? 'id-' . implode('-', $from) : 'id-' . $from;

		if ($elementID === '0') {
			$elementID = uniqid('id-', true);
		}

		return $elementID;
	}

	public function setForm(HTMLForm $form): void
	{
		$this->form = $form;
	}

	public function setValue($value): void
	{
//		llog('setValue', $this->fieldName, $value);
		parent::setValue($value);
		$this->data['value'] = $value;
	}

	/**
	 * @param string $type
	 * @param mixed $fieldValue
	 * @param string $fieldName
	 * @throws Exception
	 */
	private function switchTypeRaw($type, $fieldValue, $fieldName): string|array
	{
		$desc = $this;
		switch ($type) {
			case "string":
				return $this->form->text($fieldValue);
			case "textarea":
				return $this->form->textarea(
					$fieldName,
					$fieldValue,
					(is_array(ifsetor($desc['more']))
						? HTMLForm::getAttrHTML($desc['more'])
						: $desc['more']
					) .
					($desc['id'] ? ' id="' . $desc['id'] . '"' : '') .
					(ifsetor($desc['disabled']) ? ' disabled="1"' : '') .
					(ifsetor($desc['class']) ? ' class="' . htmlspecialchars($desc['class'], ENT_QUOTES) . '"' : '')
				);
			case "date":
				//t3lib_div::debug(array($fieldName, $fieldValue));
				return $this->form->date($fieldName, $fieldValue, $desc->getArray());
			case "datepopup":
				return $this->form->datepopup($fieldName, $fieldValue);
			case "datepopup2":
				return $this->form->datepopup2($fieldName, $fieldValue, ifsetor($desc['plusConfig']), $desc->getArray());
			case "money":
				return $this->form->money($fieldName, $fieldValue, $desc->getArray());

			case "select":
			case "selection":
				return $this->form->selection(
					$fieldName,
					$desc['options'],
					ifsetor($fieldValue, ifsetor($desc['default'])),
					$desc['autosubmit'] ?? null,
					[],    // more
					$desc['multiple'] ?? null,
					$desc->getArray()
				);
			case "file":
				return $this->form->file($fieldName, $desc->getArray());

			case "password":
				return $this->form->password($fieldName, $fieldValue, $desc->getArray());

			case "check":
			case "checkbox":
				if (ifsetor($desc['set0'])) {
					$content[] = $this->form->hidden($fieldName, 0);
				}

				$elementID = $this['elementID'];
				$more = ifsetor($desc['more'], []) + ['id' => $elementID];
				if (is_string($fieldValue) && ifsetor($desc['postgresql'])) {
					$fieldValue = $fieldValue === 't';
				}

				$content[] = $this->form->check($fieldName, ifsetor($desc['post-value'], 1), $fieldValue, /*$desc['postLabel'], $desc['urlValue'], '', false,*/
					$more, ifsetor($desc['autoSubmit']), $desc->getArray());
				return $content;

			case "time":
				return $this->form->time($fieldName, $fieldValue, $desc['unlimited']);

			case "hidden":
			case "hide":
				return $this->form->hidden($fieldName, $fieldValue, $desc['id']
					? ['id' => $desc['id']]
					: []);
			case 'hiddenArray':
				$name = is_array($fieldName) ? end($fieldName) : $fieldName;
				return $this->form->formHideArray([$name => $fieldValue]);

			case 'html':
				return $this->form->text($desc['code']);

			case 'tree':
				return $this->form->tree($fieldName, $desc['tree'], $fieldValue);

			case 'submit':
//				llog('submit', $desc);
				$desc['name'] = ifsetor($desc['name'], $fieldName);
				//debug($desc);
				$more = (is_array(ifsetor($desc->data['more']))
						? $desc->data['more'] : []) + [
						'id' => $desc->data['id']
					];
				return $this->form->submit($desc['value'], $more);

			case 'captcha':
				return $this->form->captcha($fieldName, $fieldValue, $desc->getArray());
			case 'datatable':
				return $this->form->datatable($fieldName, $fieldValue, $desc, false, $doDiv = true, 'htmlftable');
			case 'ajaxSingleChoice':
				return $this->form->ajaxSingleChoice($fieldName, $fieldValue, $desc->getArray());
			case 'set':
				return $this->form->set($fieldName, $fieldValue, $desc->getArray());
			case 'keyset':
				return $this->form->keyset($fieldName, $fieldValue, $desc->getArray());
			case 'checkarray':
				if (!is_array($fieldValue)) {
					debug($fieldName, $fieldValue, $desc->getArray());
				}

				return $this->form->checkarray($fieldName, $desc['set'], $fieldValue, $desc->getArray());
			case 'radioset':
				return $this->form->radioset($fieldName, $fieldValue, $desc->getArray());
			case 'radiolist':
				return $this->form->radioArray($fieldName, $desc['options'], $fieldValue);
			case 'combo':
				return $this->form->combo($fieldName, $desc->getArray());
			case 'button':
				return $this->form->button($desc['innerHTML'], $desc['more'] ?: []);
			case 'fieldset':
				//$this->fieldset($desc['label']);	// it only sets the global fieldset name
				return '<fieldset>
					<legend>' . htmlspecialchars($desc['label']) . '</legend>';
			case '/fieldset':
				return '</fieldset>';
			case 'tfieldset':
				if (!$desc['close']) {
					$content[] = '</td></tr></table>
						<fieldset><legend>' . $desc['legend'] . '</legend>
						<table><tr><td>';
				} else {
					$content[] = '</td></tr></table>
						</fieldset>
						<table><tr><td>';
				}

				return $content;
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'email':
				$type = 'email';
			//break;	// intentional
			case "input":
			case "text":
			default:
				$type = $type ?: 'text';
				//$this->text(htmlspecialchars($desc['more']));
//				debug($desc);
				$more = ifsetor($desc['more']);
				if ($more && !is_array($more)) {
					$more = HTMLTag::parseAttributes($more);
				}

				if (ifsetor($desc['id'])) {
					$more['id'] = $desc['id'];
				}

				if (ifsetor($desc['size'])) {
					$more['size'] = $desc['size'];
				}

				if (ifsetor($desc['readonly'])) {
					$more['readonly'] = 'readonly';
				}

				if (ifsetor($desc['disabled'])) {
					$more['disabled'] = 'disabled';
				}

				if ($desc->isObligatory()) {
					$more['required'] = "required";
				}

				if (ifsetor($desc['autofocus'])) {
					$more['autofocus'] = 'autofocus';
				}

				return $this->form->input($fieldName, $fieldValue, $more, $type === 'input' ? 'text' : $type,
					ifsetor($desc['class'],
						is_array(ifsetor($desc['more']))
							? ifsetor($desc['more']['class'], '')
							: ''
					)
				);
		}
	}

	public function getArray()
	{
		return $this->data;
	}

	public function getContent(): string
	{
		if (!$this->content) {
			$this->render();
		}
		return $this->content;
	}

	public function isCheckbox(): bool
	{
		return $this->getTypeString() === 'checkbox';
	}

	public function setOptional($is = true): void
	{
		$this->data['optional'] = $is;
	}

}
