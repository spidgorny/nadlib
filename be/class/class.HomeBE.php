<?php

class HomeBE extends AppControllerBE {

	static public $public = true;

	function render() {
		$content = '';
		$content .= new Markdown('Home.text');

		//$connection = ssh2_connect('kreuzfahrt-auswahl.de', 22);
		//$auth_methods = ssh2_auth_none($connection, 'ec2-user');
		//debug($auth_methods);

		$cmd = 'hg log -l1';
		$res = exec($cmd);
		debug($res);

		return $content;
	}

}
