<?php

class ProtectedController extends Controller {

	function __construct() {
		parent::__construct();
		if (!$this->user->id) {
			throw new LoginException('Access Denied to '.get_class($this));
		}
	}

}