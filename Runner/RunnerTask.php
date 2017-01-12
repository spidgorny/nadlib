<?php

class RunnerTask {

	var $data = [];

	var $obj;

	var $method;

	protected $table = 'runner';

	function __construct(array $row = []) {
		$this->data = $row;
		$this->db = Config::getInstance()->getDB();
	}

	function id() {
		return $this->data['id'];
	}

	function fetch($id) {
//		$query = $this->db->getSelectQuery($this->table, [
//			'id' => $id,
//		]);
		$this->data = $this->db->fetchOneSelectQuery($this->table, [
			'id' => $id,
		]);
		if ($this->data) {
			return $this->isValid();
		} else {
			return false;
		}
	}

	function release() {
		$this->db->commit();
	}

	function reserve() {
		$this->db->runUpdateQuery($this->table,
			[
				'status' => 'working',
				'progress' => 0,
				'pid' => posix_getpid(),
			],
			['id' => $this->id()]);
		$this->db->commit();
	}

	function isValid() {
		$command = $this->data['command'];
		$command = json_decode($command);
		if (sizeof($command) == 2) {
			$class = $command[0];
			$method = $command[1];
			if (class_exists($class)) {
				$this->obj = new $class($this);
				if (is_callable([$this->obj, $method])) {
					$this->method = $method;
					return true;
				}
			}
		}
		return false;
	}

	function __invoke() {
		try {
			echo '>> ' . get_class($this->obj), '->', $this->method, BR;
			$command = [$this->obj, $this->method];
			$params = json_decode($this->data['params']);
			call_user_func_array($command, $params);
			$this->done();
		} catch (Exception $e) {
			echo '!!!', get_class($e), '!!!', $e->getMessage(), BR;
			echo $e->getTraceAsString(), BR;
			$this->failed($e);
		}
	}

	private function done() {
		$this->db->runUpdateQuery($this->table,
			['status' => 'done'],
			['id' => $this->id()]);
	}

	private function failed(Exception $e) {
		$this->db->runUpdateQuery($this->table,
			['status' => 'failed'],
			['meta' => json_encode($e)]);
	}

	/**
	 * Use this function to insert a new task.
	 * @param $class
	 * @param $method
	 * @param array $params
	 * @return RunnerTask
	 */
	static function schedule($class, $method, array $params = []) {
		$task = new self([]);
		$id = $task->insert([
			'command' => json_encode([$class, $method]),
			'params' => json_encode($params),
		]);
		$task->fetch($id);
		return $task;
	}

	function insert(array $data) {
		$res = $this->db->runInsertQuery($this->table, $data);
		if (is_resource($res)) {
			$id = $this->db->lastInsertID($res);
		} elseif ($res instanceof PDOStatement) {
			$id = $this->db->lastInsertID($res);
		} else {
			$id = $res;
		}
		return $id;
	}

	static function getNext() {
		$task = new RunnerTask([]);
		$task->db->transaction();
		$row = $task->db->fetchOneSelectQuery('runner', [
			'status' => '',
		], 'ORDER BY ctime');
		if ($row) {
			$task->data = $row;
			return $task;
		} else {
			$task->release();
		}
		return NULL;
	}

	function getStatus() {
		return $this->data['status'];
	}

	function getTime() {
		return $this->data['ctime'];
	}

	public function render() {
		return '<div class="message '.__CLASS__.'">'.
			'Task #'.$this->id().' is '.$this->getStatus().' since '.$this->getTime().'.</div>';
	}

	public function getName() {
		return get_class($this->obj).'->'.$this->method;
	}

	public function setProgress($p) {
		$this->db->runUpdateQuery($this->table,
			['progress' => $p],
			['id' => $this->id()]);
	}

	public function getProgress() {
		return $this->data['progress'];
	}

	function isDone() {
		return $this->getStatus() == 'done';
	}

	function get($name) {
		return ifsetor($this->data[$name]);
	}

	public function getInfoBox() {
		$content = ['<div class="message">',
				'<h3>', $this->getName(), ' <small>#', $this->id(), '</small>', '</h3>',
				'<p>Status: ', $this->getStatus() ?: 'On Queue', '</p>',
			];
		if (!$this->isDone()) {
			if ($this->getStatus()) {
				$content[] = [
					'<p>Started: ',
					$this->getTime(),
					'</p>',
					'<p>Progress: ',
					$this->getProgress(),
					'</p>',
					'<p>PID: ',
					$this->get('pid'),
					'</p>',
					'</div>',
				];
			} else {
				$content[] = [
					'<p>Queue position: ',
					$this->getQueuePosition(),
					'</p>',
					'</div>',
				];
			}
		} else {
			$content[] = '</div>';
		}
		return $content;
	}

	private function getQueuePosition() {
		return $this->db->fetchOneSelectQuery($this->table, [
			'status' => '',
			'ctime' => new AsIsOp("< '".$this->getTime()."'"),
		], '', 'count(*) as count')['count'];
	}

}
