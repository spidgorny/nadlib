<?php

class Runner
{

	/**
	 * @var DBInterface
	 */
	public $db;

	/**
	 * @var RunnerTask
	 */
	public $currentTask;

	function __construct()
	{
		$this->db = Config::getInstance()->getDB();
	}

	function run()
	{
		echo 'Ready...', BR;
		while (true) {
			/** @var RunnerTask $command */
			$command = $this->getNextCommand();
			if ($command) {
				$command();
				break;    // restart is task is found
			} else {
				echo 'Nothing to do for ' . TaylorProfiler::getElapsedTime() . ' :-(', BR;
			}
			sleep(1);
		}
	}

	function getNextCommand()
	{
		$task = RunnerTask::getNext();
		if ($task) {
			$task->reserve();
			$this->currentTask = $task;
			if ($task->isValid()) {
				return $task;
			} else {
				$e = new BadMethodCallException('Method ' . $task->getName() . ' is not found.');
				$task->failed($e);
			}
		}
		return NULL;
	}

	public function getPendingTasks()
	{
		$rows = $this->db->fetchAllSelectQuery('runner', [
			'status' => new SQLOr([
				'status' => new SQLNotIn(['done', 'failed', 'killed']),
				'status ' => NULL,
			]),
		], 'ORDER BY ctime');
		//debug($this->db->lastQuery);
		return $rows;
	}

	public function getTaskQueue()
	{
		$rows = $this->db->fetchAllSelectQuery('runner', [
			'status' => new SQLOr([
				'status' => '',
				'status ' => NULL,
			]),
		], 'ORDER BY ctime');
		return $rows;
	}

}
