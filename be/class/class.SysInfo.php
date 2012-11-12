<?php

class SysInfo extends AppController {

	function render() {
		$a = array();
		$a['phpVersion'] = phpversion();
		$a['modRewrite'] = $this->request->apacheModuleRewrite();
		return slTable::showAssoc($a);
	}

}
