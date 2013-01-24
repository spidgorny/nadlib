<?php

class Debug {

	static function debug_args() {
		if ($_COOKIE['debug']) {
			$args = func_get_args();
			if (sizeof($args) == 1) {
				$a = $args[0];
			} else {
				$a = $args;
			}

			$db = debug_backtrace();
			$db = array_slice($db, 2, sizeof($db));
			$trace = Debug::getTraceTable($db);

			reset($db);
			$first = current($db);
			if ($first['object']) {
				$function = get_class($first['object']).'::'.$first['function'].'#'.$first['line'];
			} else if ($first['class']) {
				$function = $first['class'].'::'.$first['function'].'#'.$first['line'];
			} else {
				$function = basename(dirname($first['file'])).'/'.basename($first['file']).'#'.$first['line'];
			}
			$props = array(
				'<span style="display: inline-block; width: 5em;">Function:</span> '.$function,
				'<span style="display: inline-block; width: 5em;">Type:</span> '.gettype($a).
					(is_object($a) ? ' '.get_class($a).'#'.spl_object_hash($a) : '')
			);
			if (is_array($a)) {
				$props[] = '<span style="display: inline-block; width: 5em;">Size:</span> '.sizeof($a);
			} else if (!is_object($a) && !is_resource($a)) {
				$props[] = '<span style="display: inline-block; width: 5em;">Length:</span> '.strlen($a);
			}

			$content = '
			<div class="debug" style="
				background: #EEEEEE;
				border: solid 1px silver;
				display: inline-block;
				font-size: 12px;
				font-family: verdana;
				vertical-align: top;">
				<div class="caption" style="background-color: #EEEEEE">
				'.implode('<br />', $props).'
				<a href="javascript: void(0);" onclick="
					var a = this.nextSibling.nextSibling;
					a.style.display = a.style.display == \'block\' ? \'none\' : \'block\';
				">Trace: </a>
				<div style="display: none;">'.$trace.'</div>
			</div>';
			$content .= Debug::view_array($a);
			$content .= '</div>
			<style>
				td.view_array {
					border: dotted 1px #555;
					font-size: 12px;
					vertical-align: top;
					border-collapse: collapse;
				}
			</style>';
			if (!headers_sent()) {
				echo '<!DOCTYPE html><html>';
			}
			print($content); flush();
		}
		return $content;
	}

	function getTraceTable(array $db) {
		foreach ($db as &$row) {
			$row['file'] = basename(dirname($row['file'])).'/'.basename($row['file']);
			$row['object'] = (isset($row['object']) && is_object($row['object'])) ? get_class($row['object']) : NULL;
			$row['args'] = sizeof($row['args']);
		}
		if (!array_search('slTable', ArrayPlus::create($db)->column('object')->getData())) {
			$trace = '<pre style="white-space: pre-wrap; margin: 0;">'.
				new slTable($db, 'class="nospacing"', array(
					'file' => 'file',
					'line' => 'line',
					'class' => 'class',
					'type' => 'type',
					'function' => 'function',
					'args' => 'args',
					'object' => 'object',
				)).'</pre>';
		} else {
			$trace = 'No self-trace in slTable';
		}
		return $trace;
	}

	static function view_array($a) {
		if (is_object($a)) {
			if (method_exists($a, 'debug')) {
				$a = $a->debug();
			//} elseif (method_exists($a, '__toString')) {
			//	$a = $a->__toString();
			} else {
				$a = get_object_vars($a);
			}
		}

		if (is_array($a)) {	// not else if so it also works for objects
			$content = '<table class="view_array" style="border-collapse: collapse; margin: 2px;">';
			foreach ($a as $i => $r) {
				$type = gettype($r);
				$type = gettype($r) == 'object' ? get_class($r) : $type;
				$type = gettype($r) == 'string' ? gettype($r).'['.strlen($r).']' : $type;
				$type = gettype($r) == 'array'  ? gettype($r).'['.sizeof($r).']' : $type;
				$content .= '<tr>
					<td class="view_array">'.$i.'</td>
					<td class="view_array">'.$type.'</td>
					<td class="view_array">';

				$content .= Debug::view_array($r);
				//$content = print_r($r, true);
				$content .= '</td></tr>';
			}
			$content .= '</table>';
		} else if (is_object($a)) {
			$content = '<pre style="font-size: 12px;">'.htmlspecialchars(print_r($a, TRUE)).'</pre>';
		} else if (is_resource($a)) {
			$content = $a;
		} else if (strstr($a, "\n")) {
			$content = '<pre style="font-size: 12px;">'.htmlspecialchars($a).'</pre>';
		} else {
			$content = htmlspecialchars($a);
		}
		return $content;
	}

}
