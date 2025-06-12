<?php

class Department extends OODBase
{

	public $table = 'departments';

	public function getRights()
	{
		return new AccessRights(null, $this->db);
	}

	/**
	 * @return Person[]
	 */
	public function getPeople()
	{
		return [];
	}

}
