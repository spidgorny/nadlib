<?php

class GroupSwitch extends Controller {

	protected $allowedUsers = array(
		'depidsvy',
		'deloprub',
	);

	protected $groups = array(
		'1815684' => 'AppDev',
		'62' => 'EPES',
		'13868' => 'Lotcheck',
		'1815688' => 'Testing',
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
		$referer->setParams();
		$this->request->redirect($referer);
	}

}