<?php

class RunnerTask
{

	/**
	 * @var mixed[]
	 */
	public $data = [];

	public $obj;

	public $method;

	protected $table = 'runner';

	/**
	 * @var DBInterface
	 */
	protected $db;

	public $pinfo;

	public function __construct(array $row = [])
	{
		$this->data = $row;
		$this->db = Config::getInstance()->getDB();
	}

	/**
	 * Use this function to insert a new task.
	 * @param $class
	 * @param $method
	 */
	public static function schedule($class, $method, array $params = []): self
	{
		$task = new self([]);
		$id = $task->insert([
			'command' => json_encode([$class, $method], JSON_THROW_ON_ERROR),
			'params' => json_encode($params, JSON_THROW_ON_ERROR),
		]);
		$task->fetch($id);
		return $task;
	}

	public function insert(array $data)
	{
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

	public function fetch($id)
	{
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

	public function isValid(): bool
	{
		$command = $this->data['command'];
		$command = json_decode($command);
		if (count($command) == 2) {
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

	public static function getNext(): ?\RunnerTask
	{
		$task = new RunnerTask([]);
		$task->db->transaction();

		$row = $task->db->fetchOneSelectQuery('runner', [
			'status' => new SQLOr([
				'status' => '',
				'status ' => null,
			]),
		], 'ORDER BY ctime');
//		echo str_replace("\n", ' ', $task->db->lastQuery), BR;
		if ($row) {
			$task->data = $row;
			return $task;
		}

		$task->release();
		return null;
	}

	public function release(): void
	{
		$this->db->commit();
	}

	public function reserve(): void
	{
		echo __METHOD__, BR;
		$this->db->runUpdateQuery($this->table,
			[
				'status' => 'working',
				'progress' => 0,
//				'pid' => posix_getpid(),
				'pid' => getmypid(),
				'mtime' => new SQLNow(),
			],
			['id' => $this->id()]);
		$this->db->commit();
	}

	public function id()
	{
		return $this->data['id'];
	}

	public function __invoke(): void
	{
		try {
			echo '#' . $this->id() . ' >> ' . get_class($this->obj), '->', $this->method, BR;
			$command = [$this->obj, $this->method];
			$params = $this->getParams();
			TaylorProfiler::getInstance()->clearMemory();
			call_user_func_array($command, $params);
			$this->done();
		} catch (Exception $exception) {
			echo '!!!', get_class($exception), '!!!', $exception->getMessage(), BR;
			echo $exception->getTraceAsString(), BR;
			$this->failed($exception);
		}
	}

	public function getParams(): mixed
	{
		return json_decode($this->data['params']);
	}

	private function done(): void
	{
		echo __METHOD__, BR;
		$this->db->runUpdateQuery($this->table,
			[
				'status' => 'done',
				'mtime' => new SQLNow(),
			],
			['id' => $this->id()]);
	}

	public function failed(Exception $e): void
	{
		echo __METHOD__, BR;
		echo '[', get_class($e), '] ', $e->getMessage(), BR;
		echo $e->getTraceAsString();
		$this->db->runUpdateQuery($this->table, [
			'status' => 'failed',
			'meta' => json_encode($e, JSON_THROW_ON_ERROR),
			'mtime' => new SQLNow(),
		], ['id' => $this->id()]);
		$this->db->commit();
	}

	public function kill(): void
	{
		echo __METHOD__, BR;
		$this->db->runUpdateQuery($this->table, [
			'status' => 'killed',
			'mtime' => new SQLNow(),
		], ['id' => $this->id()]);
	}

	public function render(): string
	{
		return '<div class="message ' . __CLASS__ . '">' .
			'Task #' . $this->id() . ' is ' . $this->getStatus() . ' since ' . $this->getTime() . '.</div>';
	}

	public function getStatus()
	{
		return $this->data['status'];
	}

	public function getTime()
	{
		return $this->data['ctime'];
	}

	public function setProgress($p): void
	{
		$this->db->runUpdateQuery($this->table,
			[
				'progress' => $p,
				'mtime' => new SQLNow(),
			],
			['id' => $this->id()]);
	}

	public function getInfoBoxCLI()
	{
		$content[] = 'ID: ' . TAB . TAB . $this->id() . BR;
		$content[] = 'Name: ' . TAB . TAB . $this->getName() . BR;
		$content[] = 'Params: ' . TAB . '(' . implode(', ', $this->getParams()) . ')' . BR;
		$content[] = 'Status: ' . TAB . $this->getStatus() . BR;
		$content[] = 'Started: ' . TAB . $this->getTime() . BR;
		$content[] = 'Modified: ' . TAB . $this->getMTime() . BR;
		$content[] = 'PID: ' . TAB . TAB . $this->get('pid') . BR;
		$content[] = 'Progress: ' . TAB . $this->getProgress() . BR;
		$content[] = 'Position: ' . TAB . $this->getQueuePosition() . BR;
		return $content;
	}

	public function getName(): string
	{
		$command = json_decode($this->get('command'));
		$class = $this->obj ? get_class($this->obj) : $command[0];
		$method = $this->method ?: $command[1];
		return $class . ' -> ' . $method;
	}

	public function get($name)
	{
		return ifsetor($this->data[$name]);
	}

	public function getMTime(): \Time
	{
		return new Time($this->data['mtime']);
	}

	public function getProgress()
	{
		return $this->data['progress'];
	}

	private function getQueuePosition()
	{
		return $this->db->fetchOneSelectQuery($this->table, [
			'status' => '',
			'ctime' => new AsIsOp("< '" . $this->getTime() . "'"),
		], '', 'count(*) as count')['count'];
	}

	public function getInfoBox(string $controller = ''): array
	{
		$pb = new ProgressBar();
		$pb->getCSS();

		$content = ['<div class="message">',
			'<a href="' . $controller . '?action=kill&id=' . $this->id() . '">',
			'<span class="octicon octicon-x flash-close js-flash-close"></span></a>',
			'<p style="float: right;">PID: ',
			$this->get('pid'),
			'</p>',
			'<h5>', $this->getName(),
			'(' . implode(', ', $this->getParams()) . ')',
			' <small>#', $this->id(), '</small>', '</h5>',
			'<p style="float: right;">Started: ',
			$this->getTime(),
			'</p>',
			'<p>Status: ', $this->getStatus() ?: 'On Queue', '</p>',
		];
		if (!$this->isDone()) {
			if ($this->getStatus()) {
				$pb = new ProgressBar($this->getProgress());
				$content[] = [
					'<p style="float: right;">Updated: ',
					$this->getMTime()->getSince()->nice(),
					'</p>',
					'<p>Progress: ',
					number_format($this->getProgress(), 3) . '%',
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

	public function isDone(): bool
	{
		return $this->getStatus() == 'done';
	}

	public function isKilled(): bool
	{
		return $this->getStatus() == 'killed';
	}

	public function getPID()
	{
		$this->fetch($this->id());
		return $this->get('pid');
	}

}
