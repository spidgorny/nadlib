<?php

class SysInfo extends AppControllerME
{

	function render()
	{
		$a = array();
		$a['phpVersion'] = phpversion();
		$a['modRewrite'] = $this->request->apacheModuleRewrite();
		$a['variables_order'] = ini_get('variables_order');
		$a['post_max_size'] = ini_get('post_max_size');
		$a['upload_max_filesize'] = ini_get('upload_max_filesize');
		return slTable::showAssoc($a);
	}

}
