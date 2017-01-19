<?php

class Runner {

	/**
	 * @var DBInterface
	 */
	var $db;

	/**
	 * @var RunnerTask
	 */
	var $currentTask;

	function __construct() {
		$this->db = Config::getInstance()->getDB();
	}

	function run() {
		echo 'Ready...', BR;
		while (true) {
			$command = $this->getNextCommand();
			if ($command) {
				$command();
				break;	// restart is task is found
			} else {
				echo 'Nothing to do for '.TaylorProfiler::getElapsedTime().' :-(', BR;
			}
			sleep(1);
		}
	}

	function getNextCommand() {
		$task = RunnerTask::getNext();
		if ($task) {
			$task->reserve();
			$this->currentTask = $task;
			if ($task->isValid()) {
				return $task;
			}
		}
		return NULL;
	}

}
