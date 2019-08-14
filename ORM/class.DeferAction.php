<?php

class DeferAction extends OODBase
{
	const table = 'defer_action';
	var $table = self::table;
	const idField = 'id';
	var $idField = self::idField;

	function __construct($queue)
	{
		parent::__construct();
		$this->queue = $queue;
		//debug($this->table);
	}

	function put(Time $time, $object, array $constructor, $action, array $params)
	{
		$insert = array(
			'queue' => $this->queue,
			'time' => new SQLDateTime($time),
			'object' => $object,
			'constructor' => json_encode($constructor),
			'action' => $action,
			'params' => json_encode($params),
		);
		//debug($time, $insert);
		return $this->insert($insert);
	}

	function processNextTask()
	{
		$this->db->transaction();
		$task = $this->fetchTasks();
		if ($task) {
			$this->id = $task['id'];
			$this->data = $task;

			//debug($this->db->lastQuery, $task);
			include_once(dirname(__FILE__) . '/../../../ext/' . $task['object'] . '/class.' . $task['object'] . '.php');
			$klass = new ReflectionClass($task['object']);
			$thing = $klass->newInstanceArgs(json_decode($task['constructor'], true));
			$content = call_user_func_array(array($thing, $task['action']), json_decode($task['params'], true));

			$this->update(array('done' => true));
		}
		$this->db->commit();
		return $content;
	}

	function fetchTasks()
	{
		return $this->db->fetchSelectQuery($this->table, array(
			'time' => new AsIs('< NOW()', true),
			'done' => false,
		), 'LIMIT 1');
	}

}
