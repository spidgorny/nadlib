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

	public function __construct()
	{
		$this->db = Config::getInstance()->getDB();
	}

	public function run(): void
	{
		echo 'Ready...', BR;
		while (true) {
			/** @var ?RunnerTask $command */
			$command = $this->getNextCommand();
			if ($command) {
				$command();
			} else {
				echo 'Nothing to do for ' . TaylorProfiler::getElapsedTimeString() . ' :-(', BR;
			}

			sleep(1);
		}
	}

	public function getNextCommand(): ?RunnerTask
	{
		$task = RunnerTask::getNext();
		if ($task instanceof RunnerTask) {
			$task->reserve();
			$this->currentTask = $task;
			if ($task->isValid()) {
				return $task;
			} else {
				$e = new BadMethodCallException('Method ' . $task->getName() . ' is not found.');
				$task->failed($e);
			}
		}

		return null;
	}

	public function getPendingTasks()
	{
		//debug($this->db->lastQuery);
		return $this->db->fetchAllSelectQuery('runner', [
			'status' => new SQLOr([
				'status' => new SQLNotIn(['done', 'failed', 'killed']),
				'status ' => null,
			]),
		], 'ORDER BY ctime');
	}

	public function getTaskQueue()
	{
		return $this->db->fetchAllSelectQuery('runner', [
			'status' => new SQLOr([
				'status' => '',
				'status ' => null,
			]),
		], 'ORDER BY ctime');
	}

}
