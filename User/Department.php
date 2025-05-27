<?php

use App\Security\Legacy\RisAccessRights;

class Department extends OODBase
{

	public $table = 'departments';


	public function getRights()
	{
		return new RisAccessRights(null, $this->db);
	}

	public function getPeople()
	{
		return (new PersonCollection(
			['department' => $this->id],
			'ORDER BY surname, firstname',
			$this->db
		))->objectify();
	}

}
