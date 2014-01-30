<?php

class uTestBase extends AppControllerBE {
	protected $start;
	protected $stat = array();			// true/false counter

	function render() {
		$content = '<style>
	body, td {
		/*font-size: 9pt;*/
	}
	.contentContainer .contentLeft {
		display: none;
	}
	.contentContainer .content {
		float: none;
		width: auto;
	}
	.contentContainer .widthFix {
		width: auto;
		overflow: visible;
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
				$this->start = microtime(TRUE);
				ob_start();
				$contentPlus = call_user_func(array($this, $function));
				$debug = ob_get_clean();
				if ($debug) {
					$content .= '<tr><td colspan="99">'.$debug.'</td></tr>';
				}
				$content .= $contentPlus;
			}
		}
		$content .= '</table>';
		//$content .= getDebug($this->stat);
		$content = $this->encloseIn(new htmlString('&mu;Test'), $content, true);
		if ($GLOBALS['prof']) $content .= $GLOBALS['prof']->printTimers(1);
		return $content;
	}

	function get_var_dump($a) {
		ob_start();
		var_dump($a);
		return ob_get_clean();
	}

	function assertEqual($v1, $v2, $comment = '', $bool = NULL) {
		$row = array();
		$dbt = debug_backtrace();
		if (in_array($dbt[1]['function'], array('assert', 'assertNotEqual'))) {
			$db = $dbt[2];
			$db1 = $dbt[3];
		} else {
			$db = $dbt[1];
			$db1 = $dbt[2];
		}
		//$row['file'] = basename($db1['file']);
		$row['function'] = $db['function'];
		$file = $dbt[2]['file'] ? file($dbt[2]['file']) : NULL;
		$row['line'] = $file[$dbt[1]['line']];

		$row['comment'] = $comment;

		$row['result'] = '';

		$row['v1'] = $v1.'' == 'Array' ? '<pre style="font-size: 8pt;">'.htmlspecialchars($this->get_var_dump($v1, TRUE)).'</pre>' : $v1.'';
		$row['v2'] = $v2.'' == 'Array' ? '<pre style="font-size: 8pt;">'.htmlspecialchars($this->get_var_dump($v2, TRUE)).'</pre>' : $v2.'';

		if (is_null($bool)) {
			$bool = $v1 == $v2;
		}
		if ($bool) {
			$row['result'] = '<font color="green">OK</font>';
		} else {
			$row['result'] = '<font color="red">FAILED</font>';
		}
		$this->stat[$bool]++;

		$row['dur'] = number_format(microtime(TRUE) - $this->start, 3, '.', '');

		static $odd = 0;
		$content = '<tr class="'.($odd++%2?'odd':'').'"><td>'.implode('</td><td>', $row).'</td></tr>';
		return $content;
	}

	function assert($bool) {
		$content = $this->assertEqual($bool, TRUE);
		return $content;
	}

	function assertNotEqual($v1, $v2, $comment = NULL) {
		return $this->assertEqual($v1, $v2, $comment, $v1 !== $v2);
	}

	function test_OK() {
		return $this->assertEqual(1, 1, '1=1?');
	}

}
