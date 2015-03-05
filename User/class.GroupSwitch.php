<?php

class GroupSwitch extends AppController {

	/**
	 * Debugging is only enabled for these people.
	 * This is not a standard Nadlib functionality
	 * @see Index
	 * @var array
	 */
	public $allowedUsers = array(
		'depidsvy',
		'deloprub',
        //'dejokmaj',
        'dedomedu', // requested by deloprub on Feb. 11th 2014
        'destadea', // requested by deloprub on 2014-04-17
        'deguipie', // requested by deloprub on 2014-04-17
	);

	protected $groups = array(
		'1815684' => 'AppDev',
		'83079' => 'Artwork',
		'62' => 'EPES',
		'13868' => 'Lotcheck',
		'1815688' => 'Testers',
		'1895312' => 'Coords',
	);

	function render() {
		//debug($this->user->data);
		$content = '';
		if (in_array($this->user->data['login'], $this->allowedUsers)) {
			$this->performAction();
			$items = array();
			foreach ($this->groups as $groupID => $groupName) {
				$el = $this->makeLink($groupName, array(
					'action' => 'setGroup',
					'groupID' => $groupID,
				), 'GroupSwitch').' ';
				if ($this->user->rights->groupID == $groupID) {
					$el = '<b>'.$el.'</b>';
				}
				$items[] = $el;
			}
			$content = implode(' | ', $items);
		}
		return $content;
	}

	//function __toString() {
	//	return $this->render().'';
	//}

	function setGroupAction() {
		$this->user->pretendOtherDepartment($this->request->getInt('groupID'));
		$referer = new URL($_SERVER['HTTP_REFERER']);
		//$referer->setParams();	// uncommented to let ORS redirect to the same RequestInfo?id=123
		$this->request->redirect($referer);
	}

}
