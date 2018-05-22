<?php

class Debug {

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
	 * @var Request
	 */
	var $request;

	var $name;

	/**
	 * @param $index Index|IndexBE
	 */
	function __construct($index)
	{
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

//		pre_print_r($_COOKIE);
		if (0 && ifsetor($_COOKIE['debug'])) {
			echo 'Renderer: ' . $this->renderer;
			echo '<pre>';
			var_dump([
				'canCLI'       => $this->canCLI(),
				'canFirebug'   => $this->canFirebug(),
				'canDebugster' => $this->canDebugster(),
				'canHTML'      => $this->canHTML(),
			]);
			echo '</pre>';
		}
		$this->request = Request::getInstance();
	}

	function detectRenderer()
	{
		return $this->canCLI()
			? 'CLI'
			: ($this->canFirebug()
				? 'Firebug'
				: ($this->canDebugster()
					? 'Debugster'
					: ($this->canHTML() ? 'HTML'
						: '')));
	}

	static function getInstance()
	{
		if (!self::$instance) {
			$index = class_exists('Index', false) ? Index::getInstance() : null;
			self::$instance = new self($index);
		}
		return self::$instance;
	}

	public static function shallow($coming)
	{
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
		$dh = new DebugHTML($debug);
		$dh->render($coming);
	}

	function getSimpleType($val)
	{
		if (is_array($val)) {
			$val = 'array[' . sizeof($val) . ']';
		} elseif (is_object($val)) {
			$val = 'object[' . get_class($val) . ']';
		} elseif (is_string($val) && strlen($val) > 100) {
			$val = substr($val, 0, 100) . '...';
		}
		return $val;
	}

	function canFirebug()
	{
		$can = class_exists('FirePHP', false)
			&& !Request::isCLI()
			&& !headers_sent()
			&& ifsetor($_COOKIE['debug']);

		$require = 'vendor/firephp/firephp/lib/FirePHPCore/FirePHP.class.php';
		if (!class_exists('FirePHP') && file_exists($require)) {
			/** @noinspection PhpIncludeInspection */
			require_once $require;
		}
		$can = $can && class_exists('FirePHP');

		if ($can) {
			$fb = FirePHP::getInstance(true);
			$can = $fb->detectClientExtension();
		}
		return $can;
	}

	public static function header($url)
	{
		if (!headers_sent()) {
			static $i = 0;
			$diff = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
			$diff = round($diff, 3);
			header('X-nadlib-debug-' . $i . ': ' . $url . ' (+' . $diff . ')');
			$i++;
		}
	}

	function debugWithFirebug($params, $title = '')
	{
		$content = '';
		$params = is_array($params) ? $params : [$params];
		//debug_pre_print_backtrace();
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
			$content = call_user_func_array(['Debug', 'debug_args'], $params);
		}
		return $content;
	}

	/**
	 * Main entry point.
	 * @param $params
	 * @return string
	 */
	function debug($params)
	{
		$content = '';
		if ($this->renderer) {
			$method = 'debugWith' . $this->renderer;
			if (method_exists($this, $method)) {
				$content = $this->$method($params);
			} else {
				pre_print_r($params);
				debug_pre_print_backtrace();
			}
		} else {
			// don't show anything to the users who are not DEVELOPMENT
			//pre_print_r($params);
			//debug_pre_print_backtrace();
		}
		return $content;
	}

	function canCLI()
	{
		$isCURL = str_contains(ifsetor($_SERVER['HTTP_USER_AGENT']), 'curl');
		return Request::isCLI() || $isCURL;
	}

	function debugWithCLI($args)
	{
		if (!DEVELOPMENT) return;
		$db = debug_backtrace();
		$db = array_slice($db, 2, sizeof($db));
		$trace = [];
		$i = 0;
		foreach ($db as $i => $row) {
			$trace[] = ' * ' . self::getMethod($row, ifsetor($db[$i + 1], []));
			if (++$i > 7) break;
		}
		echo '--- ' . $this->name . ' ---' . BR .
			implode(BR, $trace) . "\n";

		if ($args instanceof htmlString) {
			$args = strip_tags($args);
		}

		if (is_object($args)) {
			echo 'Object: ', get_class($args), BR;
			if (method_exists($args, '__debugInfo')) {
				$args = $args->__debugInfo();
			} else {
				$args = get_object_vars($args);   // prevent private vars
			}
		}

		ob_start();
		var_dump($args);
		$dump = ob_get_clean();
		$dump = str_replace("=>\n", ' =>', $dump);
		echo $dump;
		echo '--- ' . $this->name . ' ---', BR;
		$this->name = null;
	}

	function canHTML()
	{
//		pre_print_r(__METHOD__, $_COOKIE);
		return ifsetor($_COOKIE['debug']);
	}

	/**
	 * @param mixed $params - any type
	 */
	function debugWithHTML($params)
	{
		if (!class_exists('DebugHTML')) {
			debug_pre_print_backtrace();
		}
		$debugHTML = new DebugHTML($this);
		$content = call_user_func([$debugHTML, 'render'], $params);
		if (!is_null($content)) {
			print($content);
		}
		if (ob_get_level() == 0) {
			flush();
		}
	}

	static function getSimpleTrace($db = null)
	{
		$db = $db ? $db : debug_backtrace();
		foreach ($db as &$row) {
			$file = ifsetor($row['file']);
			$row['file'] = basename(dirname($file)) . '/' . basename($file);
			$row['object'] = (isset($row['object']) && is_object($row['object'])) ? get_class($row['object']) : null;
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
		require_once __DIR__ . '/../Data/ArrayPlus.php';
		$traceObj = ArrayPlus::create($db)->column('object')->getData();
		if (!array_search('slTable', $traceObj) && class_exists('slTable', false)) {
			$trace = '<pre style="white-space: pre-wrap; margin: 0;">' .
				new slTable($db, 'class="nospacing"', array(
					'file' => 'file',
					'line' => 'line',
					'class' => 'class',
					'object' => 'object',
					'type' => 'type',
					'function' => 'function',
					'args' => 'args',
				)) . '</pre>';
		} else {
			$trace = 'No self-trace in slTable';
		}
		return $trace;
	}

	/**
	 * @param array $first
	 * @param array $next
	 * @return string
	 * @throws ReflectionException
	 */
	static function getMethod(array $first, array $next = array())
	{
//		pre_print_r($_SERVER);
		$isPhpStorm = isset($_SERVER['IDE_PHPUNIT_CUSTOM_LOADER'])
			|| isset($_SERVER['IDE_PHPUNIT_PHPUNIT_PHAR']);
		$curFunc = ifsetor($next['function']);
		$nextFunc = ifsetor($first['function']);
		$line = ifsetor($first['line']);
		$file = ifsetor($first['file']);
		if ($isPhpStorm) {
			$path = $file;
		} else {
			$path = basename(dirname(dirname($file))) .
				'/' . basename(dirname($file)) .
				'/' . basename($file);
		}
		if (isset($first['object']) && $first['object']) {
			$ref = new ReflectionClass($first['object']);
			$path = $ref->getFileName();
			$function = $path .
				':' . $line . ' ' .
				get_class($first['object']) .
				' -> ' . $curFunc .
				' -> ' . $nextFunc;
		} elseif (ifsetor($first['class'])) {
			$function = $path .
				':' . $line . ' ' .
				$first['class'] .
				' -> ' . $curFunc .
				' -> ' . $nextFunc;
		} else {
			$function = $path .
				':' . $line .
				' -> ' . $nextFunc;
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
		$bt = current($btl);
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
	 * @param int $limit
	 * @param int $cut
	 * @param string $join
	 * @param bool $withHash
	 * @return string
	 */
	static function getBackLog($limit = 5, $cut = 7, $join = ' // ', $withHash = true)
	{
		$debug = debug_backtrace();
		for ($i = 0; $i < $cut; $i++) {
			array_shift($debug);
		}
		$content = [];
		foreach ($debug as $i => $debugLine) {
			if (ifsetor($debugLine['object'])) {
				$object = typ($debugLine['object'], $withHash);
			} else {
				$object = '';
			}
			$file = basename(ifsetor($debugLine['file']));
			$file = str_replace('class.', '', $file);
			$file = str_replace('.php', '', $file);
			$nextFunc = ifsetor($debug[$i + 1]['function']);
			$line = ifsetor($debugLine['line']);
			$content[] = $file . '::' . $nextFunc . '#' . $line . ':' .
				$object . '->' . $debugLine['function'];
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
	static function formatBytes($bytes, $precision = 2)
	{
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		// Uncomment one of the following alternatives
		// $bytes /= pow(1024, $pow);
		$bytes /= (1 << (10 * $pow));

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	static function getArraySize(array $tmpArray)
	{
		$size = [];
		foreach ($tmpArray as $key => $row) {
			$size[$key] = strlen(serialize($row));
		}
//		debug(array_sum($size), $size);
		return array_sum($size);
	}

	function canDebugster()
	{
		return false;
	}

	/**
	 * @param $debugAccess ...
	 */
	public function consoleLog($debugAccess)
	{
		if ($this->request->isAjax()) return;
		if (func_num_args() > 1) {
			$debugAccess = func_get_args();
		}
		$json = json_encode($debugAccess);
		$script = '<script type="text/javascript">
		setTimeout(function () {
			var json = ' . $json . ';
			console.log(json);
		}, 1);
		</script>';
		if (false && class_exists('Index', false)) {
			$index = Index::getInstance();
			$index->footer[] = $script;
		} else {
			echo $script;
		}
	}

	static function peek($row)
	{
		if (is_object($row)) {
			$row = get_object_vars($row);
		}
		if (!is_null($row)) {
			$types = array_map(function ($a) {
				$val = null;
				if (is_scalar($a)) {
					$val = $a;
				}
				return ['type' => typ($a) . '', 'value' => $val];
			}, $row);
			pre_print_r(array_combine(array_keys($row), $types));
		}
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
			$recursive = [];
		}
		if (is_object($row)) {
			$hash = spl_object_hash($row);
			if (!ifsetor($recursive[$hash])) {
				$sleep = method_exists($row, '__sleep')
					? $row->__sleep() : null;
				$recursive[$hash] = typ($row);    // before it's array
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
				cap(typ($el), "\n");
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

	static function findObject($struct, $type, $path = [])
	{
		static $recursive;
		if (!$path) {
			$recursive = [];
		}
		if (is_object($struct)) {
			$hash = spl_object_hash($struct);
			if (!ifsetor($recursive[$hash])) {
				$sleep = method_exists($struct, '__sleep1')
					? $struct->__sleep() : null;
				$recursive[$hash] = typ($struct);    // before it's array
				$struct = get_object_vars($struct);
				if ($sleep) {
					$sleep = array_combine($sleep, $sleep);
					// del properties removed by sleep
					$struct = array_intersect_key($struct, $sleep);
				}
			}
		}
		if (is_array($struct)) {
			foreach ($struct as $key => $el) {
				$pathPlus1 = $path;
				$pathPlus1[] = $key . '(' . typ($el) . ')';
				if ($el instanceof $type) {
					echo implode('->', $pathPlus1), '->', typ($el), BR;
				}
				self::findObject($el, $type, $pathPlus1);
			}
		}
	}

}
