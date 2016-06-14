<?php

class HTMLFormCheckbox extends HTMLFormType implements HTMLFormTypeInterface {

	var $checked;
	
	public function __construct($name, $checked, array $more = []) {
		$this->field = $name;
		$this->checked = $checked;
		$this->desc = $more;
	}

	function render() {
		$this->form = $this->form instanceof HTMLForm
			? $this->form
			: new HTMLForm();
		//$value = htmlspecialchars($value, ENT_QUOTES);
		//$this->stdout .= "<input type=checkbox ".$this->getName($name)." ".($checked?"checked":"")." value=\"$value\" $more>";
		$more = $this->desc['more'];
		if (ifsetor($this->desc['elementID'])) {
			//$more['id'] = $this->desc['elementID'];
		}
		//debug($this->desc);
		return $this->form->getInput("checkbox", $this->field, $this->value,
			($this->checked?'checked="checked"':"").' '.
			($this->desc['autoSubmit'] ? "onchange=this.form.submit()" : '').' '.
			(is_array($more) ? $this->form->getAttrHTML($more) : $more),
			is_array($more) ? ifsetor($more['class']) : ''
		);
	}

}
