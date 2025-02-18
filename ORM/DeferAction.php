<?php

class DeferAction extends OODBase
{
	const table = 'defer_action';
	const idField = 'id';
	public $table = self::table;
	public $idField = self::idField;

	public $queue;

	public function __construct($queue = null)
	{
		parent::__construct(null);
		$this->queue = $queue;
		//debug($this->table);
	}

	public function put(Time $time, $object, array $constructor, $action, array $params)
	{
		$insert = [
			'queue' => $this->queue,
			'time' => new SQLDateTime($time),
			'object' => $object,
			'constructor' => json_encode($constructor, JSON_THROW_ON_ERROR),
			'action' => $action,
			'params' => json_encode($params, JSON_THROW_ON_ERROR),
		];
		//debug($time, $insert);
		return $this->insert($insert);
	}

	public function processNextTask()
	{
		$content = '';
		$this->db->transaction();
		$task = $this->fetchTasks();
		if ($task) {
			$this->id = $task['id'];
			$this->data = $task;

			//debug($this->db->lastQuery, $task);
			include_once(dirname(__FILE__) . '/../../../ext/' . $task['object'] . '/class.' . $task['object'] . '.php');
			$klass = new ReflectionClass($task['object']);
			$thing = $klass->newInstanceArgs(json_decode($task['constructor'], true, 512, JSON_THROW_ON_ERROR));
			$content = call_user_func_array([$thing, $task['action']], json_decode($task['params'], true, 512, JSON_THROW_ON_ERROR));

			$this->update(['done' => true]);
		}
		$this->db->commit();
		return $content;
	}

	public function fetchTasks()
	{
		return $this->db->fetchSelectQuery($this->table, [
			'time' => new AsIsOp('< NOW()'),
			'done' => false,
		], 'LIMIT 1');
	}

}
