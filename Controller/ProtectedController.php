<?php

class ProtectedController extends AppControllerBE
{

	public function __construct()
	{
		parent::__construct();
		if (!$this->user->getID()) {
			throw new AccessDeniedException('Access Denied to ' . get_class($this));
		}
	}

}
