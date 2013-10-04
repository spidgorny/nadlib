<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Majid (Pedram) Jokar
 * Date: 01.10.13
 * Time: 16:52
 * To change this template use File | Settings | File Templates.
 */


class MessageQueue extends OODBase {
	const CLASS_POSTFIX = 'Task';

	const STATUS_NEW = 'NEW';
	const STATUS_IN_PROGRESS = 'IN PROGRESS';
	const STATUS_DONE = 'DONE';

	var $table = 'message_queue';
	var $idField = 'id';

	/**
	 * Class name
	 * @var string
	 */
	private $type = null;

	/**
	 * Contains data
	 * @var array
	 */
	private $taskData = array();

	/**
	 * @param string $type
	 * @return object
	 * @throws Exception
	 */
	public function getTaskObject($type) {
		if (empty($type)) {
			throw new Exception('Type not set!');
		}

		$this->type = $type;

		// get next task available
		$this->fetchNextTask($this->type);

		if(!$this->data) {
			return false;
		}

		// set task data retrieved from DB
		$this->setTaskData($this->data['data']);

		$className = $this->getClassName($this->type);
		return new $className($this);
	}

	/**
	 * Get class name for given type
	 *
	 * @param string $type
	 * @return string
	 */
	private function getClassName($type) {
		return $type . self::CLASS_POSTFIX;
	}

	/**
	 * Fetches next task for given type from db
	 * and puts it's data into $this->data
	 *
	 * @param $type
	 */
	private function fetchNextTask($type) {
		$where = array(
			'status' 	=> self::STATUS_NEW,
			'type'		=> $type
		);

		$orderBy = 'ORDER BY id ASC';

		$this->findInDB($where, $orderBy);
	}

	/**
	 * Getter for $this->taskData
	 *
	 * @return array
	 */
	public function getTaskData() {
		return $this->taskData;
	}

	/**
	 * Setter for $this->taskData
	 *
	 * @param array $data
	 */
	public function setTaskData($data) {
		$this->taskData = json_decode($data, true);
	}

	/**
	 * Sets status of current task
	 *
	 * @param string $status MessageQueue::STATUS_*
	 * @return bool
	 */
	public function setStatus($status) {
		$data = array(
			'status'	=> $status
		);
		return $this->update($data) ?: false;
	}

	/**
	 * @param string $type
	 * @param string $taskData
	 * @param int|null $userId If not provided current user is used
	 * @internal param int $userID
	 * @internal param array $data
	 * @return OODBase
	 */
	public function createTask($type, $taskData, $userId = null) {
		$data = array(
			'ctime' 	=> 'NOW()',
			'cuser'		=> $userId ? $userId : Index::getInstance()->user->id,
			'type'		=> $type,
			'status' 	=> self::STATUS_NEW,
			'data'		=> $taskData
		);

		$msgQ = new MessageQueue();
		return $msgQ->insert($data);
	}


}

