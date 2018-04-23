<?php

class GroupSwitch extends AppController {

	/**
	 * Debugging is only enabled for these people.
	 * This is not a standard Nadlib functionality
	 * @see Index
	 * @var array
	 */
	public $allowedUsers = array();

	protected $groups = array();

	function render()
	{
		//debug($this->user->data);
		$content = '';
		if ($this->canSwitchGroup()) {
			$this->performAction();
			$this->groups = $this->fetchGroups();
			$content = $this->renderGroups();
		}
		return $content;
	}

	function canSwitchGroup()
	{
		return in_array($this->user->getLogin(), $this->allowedUsers);
	}

	function fetchGroups()
	{
		return $this->groups;
	}

	function isCurrentGroup($groupID)
	{
		return $this->user->rights->groupID == $groupID;
	}

	function setGroupAction()
	{
		$this->user->pretendOtherDepartment($this->request->getInt('groupID'));
		$referer = new URL($_SERVER['HTTP_REFERER']);
		//$referer->setParams();	// uncommented to let ORS redirect to the same RequestInfo?id=123
		$this->request->redirect($referer);
	}

	function renderGroups()
	{
		$items = array();
		foreach ($this->groups as $groupID => $groupName) {
			$el = $this->makeLink($groupName, array(
					'action' => 'setGroup',
					'groupID' => $groupID,
				), get_class($this)) . ' ';
			if ($this->isCurrentGroup($groupID)) {
				$el = '<b>' . $el . '</b>';
			}
			$items[] = $el;
		}
		$content = implode(' | ', $items);
		return $content;
	}

}
