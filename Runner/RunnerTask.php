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
		echo __METHOD__, BR;
		$this->db->runUpdateQuery($this->table,
			[
				'status' => 'working',
				'progress' => 0,
//				'pid' => posix_getpid(),
				'pid' => getmypid(),
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
			echo '#'.$this->id().' >> ' . get_class($this->obj), '->', $this->method, BR;
			$command = [$this->obj, $this->method];
			$params = $this->getParams();
			TaylorProfiler::getInstance()->clearMemory();
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
		echo __METHOD__, BR;
	}

	private function failed(Exception $e) {
		$this->db->runUpdateQuery($this->table, [
			'status' => 'failed',
			'meta' => json_encode($e),
		], ['id' => $this->id()]);
	}

	public function kill() {
		$this->db->runUpdateQuery($this->table, [
			'status' => 'killed',
		], ['id' => $this->id()]);
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
			'status' => new SQLOr([
				'status' => '',
				'status ' => NULL,
			]),
		], 'ORDER BY ctime');
//		echo str_replace("\n", ' ', $task->db->lastQuery), BR;
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
		return get_class($this->obj).' -> '.$this->method;
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

	public function getInfoBox($controller = '') {
		$pb = new ProgressBar();
		$pb->getCSS();
		$content = ['<div class="message">',
				'<a href="'.$controller.'?action=kill&id='.$this->id().'">',
				'<span class="octicon octicon-x flash-close js-flash-close"></span></a>',
				'<p style="float: right;">PID: ',
				$this->get('pid'),
				'</p>',
				'<h5>', $this->getName(),
				'('.implode(', ', $this->getParams()).')',
				' <small>#', $this->id(), '</small>', '</h5>',
				'<p>Status: ', $this->getStatus() ?: 'On Queue', '</p>',
			];
		if (!$this->isDone()) {
			if ($this->getStatus()) {
				$pb = new ProgressBar($this->getProgress());
				$content[] = [
					'<p style="float: right;">Started: ',
					$this->getTime(),
					'</p>',
					'<p>Progress: ',
					number_format($this->getProgress(), 3).'%',
					$pb->getContent(),
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

	/**
	 * @return mixed
	 */
	private function getParams() {
		return json_decode($this->data['params']);
	}

	public function isKilled() {
		return $this->getStatus() == 'killed';
	}

}
