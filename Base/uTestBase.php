<?php

/**
 * Class uTestBase
 * PHPUnit alternative
 */
class uTestBase extends AppControllerBE
{

	protected $start;

	/**
	 * true/false counter
	 * @var array
	 */
	protected $stat = [
		true  => 0,
		false => 0,
	];

	function render()
	{
		$this->index->bodyClasses[] = 'fullScreen';
		$content = '<style>
	body, td {
		/*font-size: 9pt;*/
	}
</style>';
		$content .= '
		<table class="nospacing table">
		<tr>
			<!--th>File</th-->
			<th>Function</th>
			<th>Line</th>
			<th>Comment</th>
			<th>OK</th>
			<th>Get</th>
			<th>Must</th>
			<th>Elapsed</th>
		';
		$tests = get_class_methods($this);
		foreach ($tests as $function) {
			if (substr($function, 0, 5) == 'test_') {
				$this->start = microtime(true);
				ob_start();
				$contentPlus = call_user_func([$this, $function]);
				$debug = ob_get_clean();
				if ($debug) {
					$content .= '<tr><td colspan="99">' . $debug . '</td></tr>';
				}
				$content .= $contentPlus;
			}
		}
		$content .= '</table>';
		//$content .= getDebug($this->stat);
		$content = $this->encloseIn(new htmlString('&mu;Test'), $content, true);
		if ($GLOBALS['profiler']) $content .= $GLOBALS['profiler']->printTimers(1);
		return $content;
	}

	function get_var_dump($a)
	{
		ob_start();
		var_dump($a);
		return ob_get_clean();
	}

	function assertEqual($v1, $v2, $comment = '', $bool = null)
	{
		$row = [];
		$dbt = debug_backtrace();
		$i = 0;
		$db = $dbt[$i];
		while (in_array($db['function'], ['assert', 'assertNotEqual', 'assertTrue', 'assertGreaterThan'])) {
			$db = $dbt[++$i];
		}
		//$row['file'] = basename($db1['file']);
		$row['function'] = $db['function'];
		$file = ifsetor($db['file']);
		$line = ifsetor($db['line']);
		$row['line'] = ifsetor($file[$line]);

		$row['comment'] = $comment;

		$row['result'] = '';

		$row['v1'] = is_array($v1)
			? '<pre style="font-size: 8pt;">' . htmlspecialchars($this->get_var_dump($v1)) . '</pre>' : $v1 . '';
		$row['v2'] = is_array($v2)
			? '<pre style="font-size: 8pt;">' . htmlspecialchars($this->get_var_dump($v2)) . '</pre>' : $v2 . '';

		if (is_null($bool)) {
			$bool = $v1 == $v2;
		}
		if ($bool) {
			$row['result'] = '<font color="green">OK</font>';
		} else {
			$row['result'] = '<font color="red">FAILED</font>';
		}
		$this->stat[$bool]++;

		$row['dur'] = number_format(microtime(true) - $this->start, 3, '.', '');

		static $odd = 0;
		if (Request::isCLI()) {
			echo strip_tags(implode(TAB, $row)), BR;
		} else {
			$content = '<tr class="' . ($odd++ % 2 ? 'odd' : '') . '"><td>' . implode('</td><td>', $row) . '</td></tr>';
		}
		return $content;
	}

	function assert($bool)
	{
		$content = $this->assertEqual($bool, true);
		return $content;
	}

	function assertNotEqual($v1, $v2, $comment = null)
	{
		return $this->assertEqual($v1, $v2, $comment, $v1 !== $v2);
	}

	function test_OK()
	{
		return $this->assertEqual(1, 1, '1=1?');
	}

	function run()
	{
		$tests = get_class_methods($this);
		foreach ($tests as $function) {
			if (substr($function, 0, 5) == 'test_') {
				echo '>> ', $function, BR;
				$this->start = microtime(true);
				ob_start();
				$contentPlus = call_user_func([$this, $function]);
				$this->echoBQ($contentPlus);
				$debug = ob_get_clean();
				if ($debug) {
					$this->echoBQ($debug);
				}
				echo number_format(microtime(true) - $this->start, 3, '.', ''), BR;
			}
		}
	}

	function echoBQ($text)
	{
		$lines = trimExplode(PHP_EOL, $text);
		foreach ($lines as &$line) {
			$line = str_repeat(' ', 8) . $line;
			echo $line;
		}
	}

	function assertTrue($is)
	{
		return $this->assertEqual($is, $is);
	}

	function assertGreaterThan($must, $is)
	{
		return $this->assertTrue($is >= $must);
	}

}
