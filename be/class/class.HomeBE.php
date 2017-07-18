<?php

class HomeBE extends AppControllerBE {

	static public $public = true;

	function render() {
		$content = '';
		$content .= new MarkdownView('Home.md');

		//$connection = ssh2_connect('kreuzfahrt-auswahl.de', 22);
		//$auth_methods = ssh2_auth_none($connection, 'ec2-user');
		//debug($auth_methods);

		$cmd = 'hg log -l1';
		exec($cmd, $output);
		$content .= implode('<br />', $output);

		$content .= getDebug(AutoLoad::getInstance()->debug());
		$content .= SysInfo::getInstance()->render();

		$content .= getDebug($_ENV);

		return $content;
	}

}
