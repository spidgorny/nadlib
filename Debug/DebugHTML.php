<?php

class DebugHTML {

	const LEVELS = 'LEVELS';

	static $stylesPrinted = false;

	/**
	 * @var Debug
	 */
	var $helper;

	function __construct(Debug $helper) {
		$this->helper = $helper;
	}

	function render() {
		$args = func_get_args();
		$levels = $this->getLevels($args);

		$db = debug_backtrace();
		$db = array_slice($db, 3, sizeof($db));

		$content = self::renderHTMLView($db, $args, $levels);
		$content .= self::printStyles();
		if (!headers_sent()) {
			if (method_exists($this->helper->index, 'renderHead')) {
				$this->helper->index->renderHead();
			} else {
				$content = '<!DOCTYPE html>
				<html>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
				' . $content;
			}
		}
		return $content;
	}

	function getLevels(array &$args) {
		if (sizeof($args) == 1) {
			$a = $args[0];
			$levels = /*NULL*/3;
		} else {
			$a = $args;
			if ($a[1] === self::LEVELS) {
				$levels = $a[2];
				$a = $a[0];
			} else {
				$levels = NULL;
			}
		}
		$args = $a;
		return $levels;
	}

	function renderHTMLView($db, $a, $levels) {
		$first = $db[1];
		if ($first) {
			$function = $this->helper->getMethod($first);
		} else {
			$function = '';
		}
		$props = array(
			'<span class="debug_prop">Function:</span> '.$function,
			'<span class="debug_prop">Type:</span> '.gettype2($a)
		);
		if (!is_array($a) && !is_object($a) && !is_resource($a)) {
			$props[] = '<span class="debug_prop">Length:</span> '.strlen($a);
		}

		require_once __DIR__.'/TaylorProfiler.php';
		$memPercent = TaylorProfiler::getMemUsage()*100;
		require_once __DIR__.'/../HTML/ProgressBar.php';
		$pb = new ProgressBar();
		$pb->destruct100 = false;
		$props[] = '<span class="debug_prop">Mem:</span>
 			'.number_format(memory_get_usage(true)/1024/1024, 3, '.', '').'M '.
			$pb->getImage($memPercent).' of '.ini_get('memory_limit');

		$memDiff = TaylorProfiler::getMemDiff();
		$memDiff = $memDiff[0] == '+'
			? '<span style="color: green">'.$memDiff.'</span>'
			: '<span style="color: red">'.$memDiff.'</span>';
		$props[] = '<span class="debug_prop">Mem ±:</span> '. $memDiff;

		static $lastElepsed;
		$elapsed = number_format(microtime(true) - $_SERVER['REQUEST_TIME'], 3);
		$elapsedDiff = '+'.number_format($elapsed - $lastElepsed, 3, '.', '');
		$props[] = '<span class="debug_prop">Elapsed:</span> '.
			$elapsed.' (<span style="color: green">'.$elapsedDiff.'</span>)'.BR;
		$lastElepsed = $elapsed;

		//$trace = Debug::getTraceTable($db);
		$backlog = Debug::getBackLog(1, 6);
		$trace = '<ul><li>'.Debug::getBackLog(20, 6, '<li>').'</ul>';

		$content = '
			<div class="debug">
				<div class="caption">
					'.implode(BR, $props).'
					<a href="javascript: void(0);" onclick="
						var a = this.nextSibling.nextSibling;
						a.style.display = a.style.display == \'block\' ? \'none\' : \'block\';
					">'.$backlog.'</a>
					<div style="display: none;">'.$trace.'</div>
				</div>
				'.self::view_array($a, $levels > 0 ? $levels : 5).'
			</div>';
		return $content;
	}

	/**
	 * @param $a
	 * @param $levels
	 * @return string|NULL	- will be recursive while levels is more than zero, but NULL is a special case
	 */
	static function view_array($a, $levels = 1) {
		if (is_object($a)) {
			if (method_exists($a, 'debug')) {
				$a = $a->debug();
				//} elseif (method_exists($a, '__toString')) {    // commenting this often leads to out of memory
				//	$a = $a->__toString();
				//} elseif (method_exists($a, 'getName')) {
				//	$a = $a->getName();	-- not enough info
			} elseif ($a instanceof htmlString) {
				$a = $a; // will take care below
			} elseif ($a instanceof SimpleXMLElement) {
				$a = 'XML['.$a->asXML().']';
			} else {
				$a = get_object_vars($a);
			}
		}

		if (is_array($a)) {	// not else if so it also works for objects
			$content = '<table class="view_array">';
			foreach ($a as $i => $r) {
				$type = gettype2($r);
				$content .= '<tr>
					<td>'.$i.'</td>
					<td>'.$type.'</td>
					<td>';

				//var_dump($levels); echo '<br/>'."\n";
				//echo '"', $levels, '": null: '.is_null($levels), ' ', gettype($r), BR;
				//debug_pre_print_backtrace(); flush();
				if (($a !== $r) && (is_null($levels) || $levels > 0)) {
					$content .= self::view_array($r,
						is_null($levels) ? NULL : $levels-1);
				} else {
					$content .= '<i>Too deep, $level: '.$levels.'</i>';
				}
				//$content = print_r($r, true);
				$content .= '</td></tr>';
			}
			$content .= '</table>';
		} else if (is_object($a)) {
			if ($a instanceof htmlString) {
				$content = 'html['.$a.']';
			} else {
				$content = '<pre style="font-size: 12px;">'.
					htmlspecialchars(print_r($a, TRUE)).'</pre>';
			}
		} else if (is_resource($a)) {
			$content = $a;
		} else if (is_string($a) && strstr($a, "\n")) {
			$content = '<pre style="font-size: 12px;">'.htmlspecialchars($a).'</pre>';
		} else if ($a instanceof __PHP_Incomplete_Class) {
			$content = '__PHP_Incomplete_Class';
		} else {
			$content = htmlspecialchars($a.'');
		}
		return $content;
	}

	static function printStyles() {
		$content = '';
		if (!self::$stylesPrinted) {
			$content = '<style>'.file_get_contents(__DIR__.'/Debug.css').'</style>';
			self::$stylesPrinted = true;
		} else {
			$content .= '<!-- styles printed -->';
		}
		return $content;
	}

}
