<?php

class SysInfo extends AppController
{

	function render()
	{
		$a = array();
		$a['phpVersion'] = phpversion();
		$a['modRewrite'] = $this->request->apacheModuleRewrite();
		$a['variables_order'] = ini_get('variables_order');
		return slTable::showAssoc($a);
	}

}
