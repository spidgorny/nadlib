<?php

class RunnerTaskController extends AppController
{

	/**
	 * @var RunnerTask
	 */
	public $task;

	function __construct(RunnerTask $task)
	{
		parent::__construct();
		$this->task = $task;
	}

//	abstract function runTask();

}
