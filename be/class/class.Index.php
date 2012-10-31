<?php

class Index extends IndexBase {

	public $projectName = 'nadlib|BE';

	function showSidebar() {
		$m = new Menu(array(
			'Home' => 'Home',
			'ServerStat' => 'Server Stat',
			'ServerData' => 'Server Data',
			'Session' => 'Session',
			'PHPInfo' => 'phpinfo()',
		));
		return '<div class="well" style="padding: 8px 0;">'.$m.'</div>'.
			parent::showSidebar();
	}

}
