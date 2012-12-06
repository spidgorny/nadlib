<?php

class HomeBE extends AppControllerBE {

	function render() {
		$content = '';
		$content .= new Markdown('Home.text');

		//$connection = ssh2_connect('kreuzfahrt-auswahl.de', 22);
		//$auth_methods = ssh2_auth_none($connection, 'ec2-user');
		//debug($auth_methods);

		return $content;
	}

}
