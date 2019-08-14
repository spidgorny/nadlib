<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Majid (Pedram) Jokar
 * Date: 01.10.13
 * Time: 16:52
 * To change this template use File | Settings | File Templates.
 */


class MessageQueue extends OODBase
{
	const CLASS_POSTFIX = 'Task';

	const STATUS_NEW = 'NEW';
	const STATUS_IN_PROGRESS = 'IN PROGRESS';
	const STATUS_DONE = 'DONE';
	const STATUS_FAILED = 'FAILED';

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


	public function __construct($type)
	{
		parent::__construct();

		if (empty($type)) {
			throw new Exception('Type not set!');
		}

		$this->type = $type;
	}

	/**
	 * @return object
	 * @throws Exception
	 */
	public function getTaskObject()
	{
		// get next task available
		if ($this->fetchNextTask($this->type)) {
			// set task data retrieved from DB
			$this->setTaskData($this->data['data']);

			$className = $this->getClassName($this->type);
			return new $className($this);
		};

		// if there is no next task return false
		return false;
	}

	/**
	 * Get class name for given type
	 *
	 * @param string $type
	 * @return string
	 */
	private function getClassName($type)
	{
		return $type . self::CLASS_POSTFIX;
	}

	/**
	 * Fetches next task for given type from db
	 * and puts it's data into $this->data
	 *
	 * @param $type
	 * @return bool
	 */
	private function fetchNextTask($type)
	{
		$where = array(
			'status' => self::STATUS_NEW,
			'type' => $type
		);

		$orderBy = 'ORDER BY id ASC';

		$this->findInDB($where, $orderBy);

		if (!empty($this->data['id'])) {
			// Set the status to "IN PROGRESS"
			$this->setStatus(MessageQueue::STATUS_IN_PROGRESS);

			return true;
		}

		return false;
	}

	/**
	 * Getter for $this->taskData
	 *
	 * @return array
	 */
	public function getTaskData()
	{
		return $this->taskData;
	}

	/**
	 * Setter for $this->taskData
	 *
	 * @param array $data
	 */
	public function setTaskData($data)
	{
		$this->taskData = json_decode($data, true);
	}

	/**
	 * Sets status of current task
	 *
	 * @param string $status MessageQueue::STATUS_*
	 * @return void
	 */
	public function setStatus($status)
	{
		$data = array(
			'status' => $status
		);
		$this->update($data);
	}

	/**
	 * Push a message into queue
	 *
	 * @param array $taskData
	 * @param int|null $userId If not provided current user is used
	 * @return OODBase
	 */
	public function push($taskData, $userId = null)
	{
		$data = array(
			'ctime' => 'NOW()',
			'type' => $this->type,
			'status' => self::STATUS_NEW,
			'data' => json_encode($taskData)
		);

		if (!empty($userId)) {
			$data['cuser'] = $userId;
		}
		return $this->insert($data);
	}
}

