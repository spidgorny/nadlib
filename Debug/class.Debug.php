<?php

class Debug {

	const LEVELS = 'LEVELS';

	static $stylesPrinted = false;

	var $index;

	/**
	 * @var Debug
	 */
	static protected $instance;

	/**
	 * no debug unless $_COOKIE['debug']
	 * @var string
	 */
	var $renderer = 'HTML';

	/**
	 * @param $index Index|IndexBE
	 */
	function __construct($index) {
		$this->index = $index;
		self::$instance = $this;
		if (class_exists('Config')) {
			$c = Config::getInstance();
			if (ifsetor($c->debugRenderer)) {
				$this->renderer = $c->debugRenderer;
			} else {
				$this->renderer = $this->detectRenderer();
			}
		} else {
			$this->renderer = $this->detectRenderer();
		}
		//var_dump($_COOKIE);
		if (false && $_COOKIE['debug']) {
			echo 'Renderer: ' . $this->renderer;
			echo '<pre>';
			var_dump(array(
				'canCLI' => $this->canCLI(),
				'canFirebug' => $this->canFirebug(),
				'canDebugster' => $this->canDebugster(),
				'canHTML' => $this->canHTML(),
			));
			echo '</pre>';
		}
	}

	function detectRenderer() {
		return $this->canCLI() ? 'CLI'
				: ($this->canFirebug() ? 'Firebug'
						: ($this->canDebugster() ? 'Debugster'
								: ($this->canHTML() ? 'HTML'
										: '')));
	}

	static function getInstance() {
		if (!self::$instance) {
			$index = class_exists('Index', false) ? Index::getInstance() : NULL;
			self::$instance = new self($index);
		}
		return self::$instance;
	}

	public static function shallow($coming) {
		$debug = Debug::getInstance();
		if (is_array($coming)) {
			foreach ($coming as $key => &$val) {
				$debug->getSimpleType($val);
			}
		} elseif (is_object($coming)) {
			$props = get_object_vars($coming);
			foreach ($props as $key => $val) {
				$coming->$key = $debug->getSimpleType($val);
			}
		}
		$debug->debug_args($coming);
	}

	function getSimpleType($val) {
		if (is_array($val)) {
			$val = 'array['.sizeof($val).']';
		} elseif (is_object($val)) {
			$val = 'object['.get_class($val).']';
		} elseif (is_string($val) && strlen($val) > 100) {
			$val = substr($val, 0, 100).'...';
		}
		return $val;
	}

	function canFirebug() {
		$can = class_exists('FirePHP')
			&& !Request::isCLI()
			&& !headers_sent()
			&& ifsetor($_COOKIE['debug']);
		if ($can) {
			$fb = FirePHP::getInstance(true);
			$can = $fb->detectClientExtension();
		}
		return $can;
	}

	function debugWithFirebug(array $params, $title = '') {
		$content = '';
		$require = 'vendor/firephp/firephp/lib/FirePHPCore/FirePHP.class.php';
		if (!class_exists('FirePHP') && file_exists($require)) {
			require_once $require;
		}
		$fp = FirePHP::getInstance(true);
		if ($fp->detectClientExtension()) {
			$fp->setOption('includeLineNumbers', true);
			$fp->setOption('maxArrayDepth', 10);
			$fp->setOption('maxDepth', 20);
			$trace = Debug::getSimpleTrace();
			array_shift($trace);
			array_shift($trace);
			array_shift($trace);
			if ($trace) {
				$fp->table(implode(' ', first($trace)), $trace);
			}
			$fp->log(1 == sizeof($params) ? first($params) : $params, $title);
		} else {
			$content = call_user_func_array(array('Debug', 'debug_args'), $params);
		}
		return $content;
	}

	/**
	 * Main entry point.
	 * @param $params
	 * @return string
	 */
	function debug($params) {
		$content = '';
		if ($this->renderer) {
			$method = 'debugWith' . $this->renderer;
			if (method_exists($this, $method)) {
				$content = $this->$method($params);
			}
		} else {
			pre_print_r($params);
		}
		return $content;
	}

	function canCLI() {
		return Request::isCLI();
	}

	function debugWithCLI() {
		$db = debug_backtrace();
		$db = array_slice($db, 2, sizeof($db));
		$trace = array();
		foreach ($db as $row) {
			$trace[] = self::getMethod($row);
		}
		echo '---' . implode(' // ', $trace) . "\n";

		$args = func_get_args();
		$this->getLevels($args);
		if (is_object($args)) {
			$args = get_object_vars($args);   // prevent private vars
		}

		ob_start();
		var_dump($args);
		$dump = ob_get_clean();
		$dump = str_replace("=>\n", ' =>', $dump);
		echo $dump, "\n";
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

	function canHTML() {
		return ifsetor($_COOKIE['debug']);
	}

	function debugWithHTML(array $params) {
		$content = call_user_func_array(array('Debug', 'debug_args'), $params);
		if (!is_null($content)) {
			print($content);
		}
		if (ob_get_level() == 0) {
			flush();
		}
	}

	function debug_args() {
		$args = func_get_args();
		$levels = $this->getLevels($args);

		$db = debug_backtrace();
		$db = array_slice($db, 3, sizeof($db));

		$content = self::renderHTMLView($db, $args, $levels);
		$content .= self::printStyles();
		if (!headers_sent()) {
			if (method_exists($this->index, 'renderHead')) {
				$this->index->renderHead();
			} else {
				$content = '<!DOCTYPE html><html>' . $content;
			}
		}
		return $content;
	}

	static function renderHTMLView($db, $a, $levels) {
		$trace = Debug::getTraceTable($db);

		$first = $db[1];
		if ($first) {
			$function = self::getMethod($first);
		} else {
			$function = '';
		}
		$props = array(
			'<span class="debug_prop">Function:</span> '.$function,
			'<span class="debug_prop">Type:</span> '.gettype($a).
				(is_object($a) ? ' '.get_class($a).'#'.spl_object_hash($a) : '')
		);
		if (is_array($a)) {
			$props[] = '<span class="debug_prop">Size:</span> '.sizeof($a);
		} else if (!is_object($a) && !is_resource($a)) {
			$props[] = '<span class="debug_prop">Length:</span> '.strlen($a);
		}
		$memPercent = TaylorProfiler::getMemUsage()*100;
		$pb = new ProgressBar();
		$pb->destruct100 = false;
		$props[] = '<span class="debug_prop">Mem:</span> '.$pb->getImage($memPercent).' of '.ini_get('memory_limit');
		$props[] = '<span class="debug_prop">Mem ±:</span> '.TaylorProfiler::getMemDiff();
		$props[] = '<span class="debug_prop">Elapsed:</span> '.number_format(microtime(true)-$_SERVER['REQUEST_TIME'], 3).'<br />';

		$content = '
			<div class="debug">
				<div class="caption">
					'.implode(BR, $props).'
					<a href="javascript: void(0);" onclick="
						var a = this.nextSibling.nextSibling;
						a.style.display = a.style.display == \'block\' ? \'none\' : \'block\';
					">'.Debug::getBackLog(6, 7, '<br />').'</a>
					<div style="display: none;">'.$trace.'</div>
				</div>
				'.Debug::view_array($a, $levels > 0 ? $levels : 5).'
			</div>';
		return $content;
	}

	static function getSimpleTrace($db = NULL) {
		$db = $db ? $db : debug_backtrace();
		foreach ($db as &$row) {
			$file = ifsetor($row['file']);
			$row['file'] = basename(dirname($file)).'/'.basename($file);
			$row['object'] = (isset($row['object']) && is_object($row['object'])) ? get_class($row['object']) : NULL;
			$row['args'] = sizeof($row['args']);
		}
		return $db;
	}

	/**
	 * @param array $db
	 * @return string
	 */
	static function getTraceTable(array $db) {
		$db = self::getSimpleTrace($db);
		if (!array_search('slTable', ArrayPlus::create($db)->column('object')->getData())) {
			$trace = '<pre style="white-space: pre-wrap; margin: 0;">'.
				new slTable($db, 'class="nospacing"', array(
					'file' => 'file',
					'line' => 'line',
					'class' => 'class',
					'object' => 'object',
					'type' => 'type',
					'function' => 'function',
					'args' => 'args',
				)).'</pre>';
		} else {
			$trace = 'No self-trace in slTable';
		}
		return $trace;
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
			} else {
				$a = get_object_vars($a);
			}
		}

		if (is_array($a)) {	// not else if so it also works for objects
			$content = '<table class="view_array">';
			foreach ($a as $i => $r) {
				$type = gettype($r);
				$type = $type == 'object' ? gettype($r).' '.get_class($r) : gettype($r);
				$type = $type == 'string' ? gettype($r).'['.strlen($r).']' : $type;
				$type = $type == 'array'  ? gettype($r).'['.sizeof($r).']' : $type;
				$content .= '<tr>
					<td class="view_array">'.$i.'</td>
					<td class="view_array">'.$type.'</td>
					<td class="view_array">';

				//var_dump($levels); echo '<br/>'."\n";
				//echo '"', $levels, '": null: '.is_null($levels), ' ', gettype($r), BR;
				//debug_pre_print_backtrace(); flush();
				if (($a !== $r) && (is_null($levels) || $levels > 0)) {
					$content .= Debug::view_array($r,
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
				$content = $a.'';
			} else {
				$content = '<pre style="font-size: 12px;">'.htmlspecialchars(print_r($a, TRUE)).'</pre>';
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

	static function getMethod(array $first) {
		if (isset($first['object']) && $first['object']) {
			$function = get_class($first['object']).'::'.$first['function'].'#'.ifsetor($first['line']);
		} else if (isset($first['class']) && $first['class']) {
			$function = $first['class'].'::'.$first['function'].'#'.$first['line'];
		} else {
			$function = basename(dirname($first['file'])).'/'.basename($first['file']).'#'.$first['line'];
		}
		return $function;
	}

	/**
	 * Returns a single method several steps back in trace
	 * @param int $stepBack
	 * @return string
	 */
	static function getCaller($stepBack = 2) {
		$btl = debug_backtrace();
		reset($btl);
		for ($i = 0; $i < $stepBack; $i++) {
			$bt = next($btl);
		}
		if ($bt['function'] == 'runSelectQuery') {
			$bt = next($btl);
		}
		return ifsetor($bt['class'], get_class(ifsetor($bt['object'])))
			. '::' . $bt['function'];
	}

	/**
	 * Returns a string with multiple methods chain
	 * @param int    $limit
	 * @param int    $cut
	 * @param string $join
	 * @return string
	 */
	static function getBackLog($limit = 5, $cut = 7, $join = ' // ') {
		$debug = debug_backtrace();
		for ($i = 0; $i < $cut; $i++) {
			array_shift($debug);
		}
		$content = array();
		foreach ($debug as $debugLine) {
			$file = basename(ifsetor($debugLine['file']));
			$file = str_replace('class.', '', $file);
			$file = str_replace('.php', '', $file);
			$content[] = $file.'::'.$debugLine['function'].':'.ifsetor($debugLine['line']);
			if (!--$limit) {
				break;
			}
		}
		$content = implode($join, $content);
		return $content;
	}

	/**
	 * http://stackoverflow.com/a/2510459/417153
	 * @param $bytes
	 * @param int $precision
	 * @return string
	 */
	static function formatBytes($bytes, $precision = 2) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		// Uncomment one of the following alternatives
		// $bytes /= pow(1024, $pow);
		$bytes /= (1 << (10 * $pow));

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	static function getArraySize(array $tmpArray) {
		$size = array();
		foreach ($tmpArray as $key => $row) {
			$size[$key] = strlen(serialize($row));
		}
		debug(array_sum($size), $size);
	}

	static function printStyles() {
		$content = '';
		if (!self::$stylesPrinted) {
			$content = '
			<style>
				div.debug {
					color: black;
					background: #EEEEEE;
					border: solid 1px silver;
					display: inline-block;
					font-size: 12px;
					font-family: verdana;
					vertical-align: top;
				}
				div.debug a {
					color: black;
				}
				div.debug .caption {
					background-color: #EEEEEE;
				}
				td.view_array {
					border: dotted 1px #555;
					font-size: 12px;
					vertical-align: top;
					border-collapse: collapse;
					color: black;
					margin: 2px;
				}
				.debug_prop {
					display: inline-block;
					width: 5em;
				}
			</style>';
			self::$stylesPrinted = true;
		} else {
			$content .= '<!-- styles printed -->';
		}
		return $content;
	}

	function canDebugster() {
		return false;
	}

	/**
	 * @param $debugAccess...
	 */
	public function consoleLog($debugAccess) {
		if (Request::getInstance()->isAjax()) return;
		if (func_num_args() > 1) {
			$debugAccess = func_get_args();
		}
		$json = htmlspecialchars(json_encode($debugAccess), ENT_QUOTES);
		$script = '<script type="text/javascript">
		setTimeout(function () {
			var json = "'.$json.'";
			json = json.replace(/&quot;/g, \'"\');
			json = json.replace(/&gt;/g, \'>\');
			var obj = JSON.parse(json);
			console.log(obj);
		}, 1);
		</script>';
		if (false && class_exists('Index', false)) {
			$index = Index::getInstance();
			$index->footer[] = $script;
		} else {
			echo $script;
		}
	}

	static function peek($row) {
		pre_print_r(array_combine(array_keys($row), array_map(function ($a) {
			return gettype($a);
		}, $row)));
	}

}
