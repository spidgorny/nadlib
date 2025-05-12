<?php

use spidgorny\nadlib\HTTP\URL;

/**
 * @todo: move to ORS project
 */
class GroupSwitch extends Controller
{

	/**
	 * Debugging is only enabled for these people.
	 * This is not a standard Nadlib functionality
	 * @see Index
	 * @var array
	 */
	public $allowedUsers = [];

	protected $groups = [];

	public function render()
	{
		//debug($this->user->data);
		$content = '';
		if (!$this->canSwitchGroup()) {
			return $content;
		}

//			$this->performAction($this->detectAction());	// this leads to issues in PrepareGive
		if ($this->detectAction() === 'setGroup') {
			return $this->setGroupAction();
		}

		$this->groups = $this->fetchGroups();
		return $this->renderGroups();
	}

	public function canSwitchGroup(): bool
	{
		return in_array($this->user->getLogin(), $this->allowedUsers);
	}

	public function setGroupAction(): string
	{
		$this->user->pretendOtherDepartment($this->request->getInt('groupID'));
		$referer = new URL($_SERVER['HTTP_REFERER']);
		//$referer->setParams();	// uncommented to let ORS redirect to the same RequestInfo?id=123
		return $this->request->redirect($referer);
	}

	public function fetchGroups()
	{
		return $this->groups;
	}

	public function renderGroups(): string
	{
		$items = [];
		foreach ($this->groups as $groupID => $groupName) {
			$el = $this->makeLink($groupName, [
					'action' => 'setGroup',
					'groupID' => $groupID,
				], get_class($this)) . ' ';
			if ($this->isCurrentGroup($groupID)) {
				$el = '<b>' . $el . '</b>';
			}

			$items[] = $el;
		}

		return implode(' | ', $items);
	}

	public function isCurrentGroup($groupID): bool
	{
		return $this->user->getGroup()->getID() === $groupID;
	}

}
