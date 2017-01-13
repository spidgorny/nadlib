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
		sleep(1);
		while (true) {
			$command = $this->getNextCommand();
			if ($command) {
				$command();
			}
			sleep(1);
			break;	// restart
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
