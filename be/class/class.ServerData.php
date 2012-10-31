<?php

class ServerData extends AppController {

	function render() {
		$s = slTable::showAssoc($_SERVER);
		$s->more = 'class="table table-striped table-condensed"';
		return $s;
	}

}
