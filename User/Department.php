<?php

use App\Security\Legacy\RisAccessRights;

class Department extends OODBase
{

	public $table = 'departments';


	// @todo: move to RIS
	public function getRights()
	{
//		return new RisAccessRights(null, $this->db);
	}

	// @todo: move to ORS
	public function getPeople()
	{
		return (new PersonCollection(null,
			['department' => $this->id],
			'ORDER BY surname, firstname',
			$this->db
		))->objectify();
	}

}
