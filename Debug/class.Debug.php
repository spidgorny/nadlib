<?php

class Debug
{

	const LEVELS = 'LEVELS';

	static function debug_args()
	{
		$args = func_get_args();
		if (sizeof($args) == 1) {
			$a = $args[0];
			$levels = NULL;
		} else {
			$a = $args;
			if ($a[1] === self::LEVELS) {
				$levels = $a[2];
				$a = $a[0];
			}
		}

		$db = debug_backtrace();
		$db = array_slice($db, 2, sizeof($db));

		//print_r(array($_SERVER['argc'], $_SERVER['argv']));
		//if (isset($_SERVER['argc'])) {
		if (Request::isCLI()) {
			foreach ($db as $row) {
				$trace[] = self::getMethod($row);
			}
			echo '---' . implode(' // ', $trace) . "\n";

			if (is_object($a)) {
				$a = get_object_vars($a);   // prevent private vars
			}

			ob_start();
			var_dump(
				$a
			);
			$dump = ob_get_clean();
			$dump = str_replace("=>\n", ' =>', $dump);
			echo $dump, "\n";
		} else if ($_COOKIE['debug']) {
			$content = self::renderHTMLView($db, $a, $levels);
			$content .= '
			<style>
				div.debug {
					color: black;
				}
				div.debug a {
					color: black;
				}
				td.view_array {
					border: dotted 1px #555;
					font-size: 12px;
					vertical-align: top;
					border-collapse: collapse;
					color: black;
				}
			</style>';
			if (!headers_sent()) {
				echo '<!DOCTYPE html><html>';
			}
			print($content);
			flush();
		}
		return $content;
	}

	static function renderHTMLView($db, $a, $levels)
	{
		$trace = Debug::getTraceTable($db);

		reset($db);
		$first = current($db);
		$function = self::getMethod($first);
		$props = array(
			'<span style="display: inline-block; width: 5em;">Function:</span> ' . $function,
			'<span style="display: inline-block; width: 5em;">Type:</span> ' . gettype($a) .
			(is_object($a) ? ' ' . get_class($a) . '#' . spl_object_hash($a) : '')
		);
		if (is_array($a)) {
			$props[] = '<span style="display: inline-block; width: 5em;">Size:</span> ' . sizeof($a);
		} else if (!is_object($a) && !is_resource($a)) {
			$props[] = '<span style="display: inline-block; width: 5em;">Length:</span> ' . strlen($a);
		}
		$memPercent = TaylorProfiler::getMemUsage() * 100;
		$pb = new ProgressBar();
		$pb->destruct100 = false;
		$props[] = '<span style="display: inline-block; width: 5em;">Mem:</span> ' . $pb->getImage($memPercent, 'display: inline');
		$props[] = '<span style="display: inline-block; width: 5em;">Mem ±:</span> ' . TaylorProfiler::getMemDiff();
		$props[] = '<span style="display: inline-block; width: 5em;">Elapsed:</span> ' . number_format(microtime(true) - $_SERVER['REQUEST_TIME'], 3) . '<br />';

		$content = '
			<div class="debug" style="
				background: #EEEEEE;
				border: solid 1px silver;
				display: inline-block;
				font-size: 12px;
				font-family: verdana;
				vertical-align: top;">
				<div class="caption" style="background-color: #EEEEEE">
					' . implode(BR, $props) . '
					<a href="javascript: void(0);" onclick="
						var a = this.nextSibling.nextSibling;
						a.style.display = a.style.display == \'block\' ? \'none\' : \'block\';
					">Trace: </a>
					<div style="display: none;">' . $trace . '</div>
				</div>
				' . Debug::view_array($a, $levels) . '
			</div>';
		return $content;
	}

	static function getSimpleTrace($db = NULL)
	{
		$db = $db ?: debug_backtrace();
		foreach ($db as &$row) {
			$row['file'] = basename(dirname($row['file'])) . '/' . basename($row['file']);
			$row['object'] = (isset($row['object']) && is_object($row['object'])) ? get_class($row['object']) : NULL;
			$row['args'] = sizeof($row['args']);
		}
		return $db;
	}

	/**
	 * @param array $db
	 * @return string
	 */
	static function getTraceTable(array $db)
	{
		$db = self::getSimpleTrace($db);
		if (!array_search('slTable', ArrayPlus::create($db)->column('object')->getData())) {
			$trace = '<pre style="white-space: pre-wrap; margin: 0;">' .
				new slTable($db, 'class="nospacing"', array(
					'file' => 'file',
					'line' => 'line',
					'class' => 'class',
					'type' => 'type',
					'function' => 'function',
					'args' => 'args',
					'object' => 'object',
				)) . '</pre>';
		} else {
			$trace = 'No self-trace in slTable';
		}
		return $trace;
	}

	/**
	 * @param $a
	 * @param $levels
	 * @return string|NULL    - will be recursive while levels is more than zero, but NULL is a special case
	 */
	static function view_array($a, $levels = NULL)
	{
		if (is_object($a)) {
			if (method_exists($a, 'debug')) {
				$a = $a->debug();
				//} elseif (method_exists($a, '__toString')) {
				//	$a = $a->__toString();
			} elseif ($a instanceof htmlString) {
				$a = $a; // will take care below
			} else {
				$a = get_object_vars($a);
			}
		}

		if (is_array($a)) {    // not else if so it also works for objects
			$content = '<table class="view_array" style="border-collapse: collapse; margin: 2px;">';
			foreach ($a as $i => $r) {
				$type = gettype($r) == 'object' ? gettype($r) . ' ' . get_class($r) : gettype($r);
				$type = gettype($r) == 'string' ? gettype($r) . '[' . strlen($r) . ']' : $type;
				$type = gettype($r) == 'array' ? gettype($r) . '[' . sizeof($r) . ']' : $type;
				$content .= '<tr>
					<td class="view_array">' . $i . '</td>
					<td class="view_array">' . $type . '</td>
					<td class="view_array">';

				//var_dump($levels); echo '<br/>'."\n";
				//echo $levels, ': null: '.is_null($levels)."<br />\n";
				if (is_null($levels) || $levels > 0) {
					$content .= Debug::view_array($r, is_null($levels) ? NULL : $levels - 1);
				}
				//$content = print_r($r, true);
				$content .= '</td></tr>';
			}
			$content .= '</table>';
		} else if (is_object($a)) {
			if ($a instanceof htmlString) {
				$content = $a . '';
			} else {
				$content = '<pre style="font-size: 12px;">' . htmlspecialchars(print_r($a, TRUE)) . '</pre>';
			}
		} else if (is_resource($a)) {
			$content = $a;
		} else if (is_string($a) && strstr($a, "\n")) {
			$content = '<pre style="font-size: 12px;">' . htmlspecialchars($a) . '</pre>';
		} else if ($a instanceof __PHP_Incomplete_Class) {
			$content = '__PHP_Incomplete_Class';
		} else {
			$content = htmlspecialchars($a . '');
		}
		return $content;
	}

	static function getMethod(array $first)
	{
		if ($first['object']) {
			$function = get_class($first['object']) . '::' . $first['function'] . '#' . $first['line'];
		} else if ($first['class']) {
			$function = $first['class'] . '::' . $first['function'] . '#' . $first['line'];
		} else {
			$function = basename(dirname($first['file'])) . '/' . basename($first['file']) . '#' . $first['line'];
		}
		return $function;
	}

	/**
	 * Returns a single method several steps back in trace
	 * @param int $stepBack
	 * @return string
	 */
	static function getCaller($stepBack = 2)
	{
		$btl = debug_backtrace();
		reset($btl);
		for ($i = 0; $i < $stepBack; $i++) {
			$bt = next($btl);
		}
		if ($bt['function'] == 'runSelectQuery') {
			$bt = next($btl);
		}
		return "{$bt['class']}::{$bt['function']}";
	}

	/**
	 * Returns a string with multiple methods chain
	 * @param int $limit
	 * @return string
	 */
	static function getBackLog($limit = 5)
	{
		$debug = debug_backtrace();
		array_shift($debug);
		$content = array();
		foreach ($debug as $debugLine) {
			$content[] = $debugLine['class'] . '::' . $debugLine['function'];
		}
		$content = implode(' // ', $content);
		return $content;
	}

	/**
	 * This is like peek() but recursive
	 * @param     $row
	 * @param int $spaces
	 */
	static function dumpStruct($row, $spaces = 0)
	{
		static $recursive;
		if (!$spaces) {
			echo '<pre class="debug">';
			$recursive = array();
		}
		if (is_object($row)) {
			$hash = spl_object_hash($row);
			if (!ifsetor($recursive[$hash])) {
				$sleep = method_exists($row, '__sleep')
					? $row->__sleep() : NULL;
				$recursive[$hash] = gettype2($row);    // before it's array
				$row = get_object_vars($row);
				if ($sleep) {
					$sleep = array_combine($sleep, $sleep);
					// del properties removed by sleep
					$row = array_intersect_key($row, $sleep);
				}
			} else {
				$row = '*RECURSIVE* ' . $recursive[$hash];
			}
		}
		if (is_array($row)) {
			foreach ($row as $key => $el) {
				echo str_repeat(' ', $spaces), $key, '->',
				cap(gettype2($el), "\n");
				self::dumpStruct($el, $spaces + 4);
			}
		} else {
			echo str_repeat(' ', $spaces);
			switch (gettype($row)) {
				case 'string':
					$len = mb_strlen($row);
					if ($len > 32) {
						$row = substr($row, 0, 32) . '...';
					}
					echo '"', htmlspecialchars($row), '"';
					break;
				case 'null':
					echo 'NULL';
					break;
				case 'boolean':
					echo $row ? 'TRUE' : 'FALSE';
					break;
				default:
					echo $row;
			}
			echo BR;
		}
		if (!$spaces) {
			echo '</pre>';
		}
	}

}
