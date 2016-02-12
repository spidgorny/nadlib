<?php

class ProtectedController extends AppController {

	function __construct() {
		parent::__construct();
		if (!$this->user->id) {
			throw new AccessDeniedException('Access Denied to '.get_class($this));
		}
	}

}
