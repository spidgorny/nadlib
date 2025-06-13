<?php

namespace spidgorny\nadlib\Debug;

use DebugCLI;
use DebugBulma;
use DebugHTML;
use slTable;
use ReflactionClass;
use ArrayPlus;
use Request;

class Debug
{

	protected static $instance;

	/**
	 * no debug unless $_COOKIE['debug']
	 * @var string
	 */
	public $renderer = 'HTML';

	public $name;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 */
	public function __construct($debugRenderer = null)
	{
		self::$instance = $this;
		$this->renderer = ifsetor($debugRenderer, $this->detectRenderer());
//		pre_print_r($_COOKIE);
		if (0 && ifsetor($_COOKIE['debug'])) {
			llog([
				'canCLI' => DebugCLI::canCLI(),
				'canDebugster' => $this->canDebugster(),
				'canBulma' => DebugBulma::canBulma(),
				'canHTML' => $this->canHTML(),
			]);
		}

		$this->request = Request::getInstance();
	}

	public function detectRenderer(): string
	{
		if (DebugCLI::canCLI()) {
			return DebugCLI::class;
		}
		if ($this->canDebugster()) {
			return 'Debugster';
		}
		if (DEVELOPMENT) {
			if (DebugBulma::canBulma()) {
				return DebugBulma::class;
			}
			if ($this->canHTML()) {
				return 'HTML';
			}
		}
		return '';
	}

	public function canDebugster(): bool
	{
		return false;
	}

	public function canHTML()
	{
//		pre_print_r(__METHOD__, $_COOKIE);
		return ifsetor($_COOKIE['debug']);
	}

	public static function getInstance(): Debug
	{
		if (!self::$instance) {
			$index = class_exists('Index', false) ? \Index::getInstance() : null;
			self::$instance = new self($index);
		}

		return self::$instance;
	}

	public static function shallow($coming): void
	{
		$debug = Debug::getInstance();
		if (is_array($coming)) {
			foreach ($coming as &$val) {
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

	public function getSimpleType($val)
	{
		if (is_array($val)) {
			$val = 'array[' . count($val) . ']';
		} elseif (is_object($val)) {
			$val = 'object[' . get_class($val) . ']';
		} elseif (is_string($val) && strlen($val) > 100) {
			$val = substr($val, 0, 100) . '...';
		}

		return $val;
	}

	public static function header(string $url): void
	{
		if (!headers_sent()) {
			static $i = 0;
			$diff = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
			$diff = round($diff, 3);
			header('X-nadlib-debug-' . $i . ': ' . $url . ' (+' . $diff . ')');
			$i++;
		}
	}

	public static function getTraceTable(array $db): string
	{
		$db = self::getSimpleTrace($db);
		require_once __DIR__ . '/../Data/ArrayPlus.php';
		$traceObj = ArrayPlus::create($db)->column('object')->getData();
		if (!array_search('slTable', $traceObj, true) && class_exists('slTable', false)) {
			$trace = '<pre style="white-space: pre-wrap; margin: 0;">' .
				new slTable($db, 'class="nospacing"', [
					'file' => 'file',
					'line' => 'line',
					'class' => 'class',
					'object' => 'object',
					'type' => 'type',
					'function' => 'function',
					'args' => 'args',
				]) . '</pre>';
		} else {
			$trace = 'No self-trace in slTable';
		}

		return $trace;
	}

	public static function getSimpleTrace($db = null)
	{
		$db = $db ?: debug_backtrace();
		foreach ($db as &$row) {
			$file = ifsetor($row['file']);
			$row['file'] = basename(dirname($file)) . '/' . basename($file);
			$row['object'] = (isset($row['object']) && is_object($row['object'])) ? get_class($row['object']) : null;
			$row['args'] = count($row['args']);
		}

		return $db;
	}

	public static function getTraceTable2(array $db): string
	{
		$db = self::getSimpleTrace($db);
		$thes = [
			'file' => 'file',
			'line' => 'line',
//			'class' => 'class',
			'object' => 'object',
//			'type' => 'type',
			'function' => 'function',
//			'args' => 'args',
		];
		$trace[] = '<table class="table">';
		$trace[] = '<thead>';
		foreach ($thes as $t) {
			$trace[] = '<td>' . $t . '</td>';
		}

		$trace[] = '</thead>';
		$trace[] = '<tbody>';
		foreach ($db as $row) {
			$trace[] = '<tr>';
			foreach (array_keys($thes) as $t) {
				$trace[] = '<td>' . ifsetor($row[$t]) . '</td>';
			}

			$trace[] = '</tr>';
		}

		$trace[] = '</tbody>';
		$trace[] = '</table>';
		return implode(PHP_EOL, $trace);
	}

	/**
	 * @throws \ReflectionException
	 */
	public static function getMethod(array $first, array $next = []): string
	{
//		pre_print_r($_SERVER);
		$isPhpStorm = isset($_SERVER['IDE_PHPUNIT_CUSTOM_LOADER'])
			|| isset($_SERVER['IDE_PHPUNIT_PHPUNIT_PHAR']);
		$curFunc = ifsetor($next['function']);
		$nextFunc = ifsetor($first['function']);
		$line = ifsetor($first['line']);
		$file = ifsetor($first['file']);

		$isPhpStorm = false;    // don't like it
		if ($isPhpStorm) {
			$path = $file;
		} else {
			$path = basename(dirname($file, 2)) .
				'/' . basename(dirname($file)) .
				'/' . basename($file);
			if ($path[0] === 'C') {
				llog($file, $path);
				exit;
			}
		}

		if (isset($first['object']) && $first['object']) {
			$ref = new \ReflectionClass($first['object']);
			$path = $ref->getFileName();

			$path = basename(dirname($file, 2)) .
				'/' . basename(dirname($file)) .
				'/' . basename($file);

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
	 */
	public static function getCaller($stepBack = 2): string
	{
		$btl = debug_backtrace();
		reset($btl);
		$bt = current($btl);
		for ($i = 0; $i < $stepBack; $i++) {
			$bt = next($btl);
		}

		$btBefore = $btl[$stepBack - 1];
		$object = ifsetor($bt['object']);
		return ifsetor($bt['class'], is_object($object) ? get_class($object) : null)
			. '::' . ifsetor($bt['function']) . '#' . ifsetor($btBefore['line']);
	}

	/**
	 * Returns a string with multiple methods chain
	 * @param int $limit
	 * @param int $cut
	 * @param string $join
	 * @param bool $withHash
	 * @return string
	 */
	public static function getBackLog($limit = 5, $cut = 7, $join = null, $withHash = true)
	{
		$debug = debug_backtrace();
		for ($i = 0; $i < $cut; $i++) {
			array_shift($debug);
		}

		$content = [];
		foreach ($debug as $i => $debugLine) {
			$object = ifsetor($debugLine['object']) ? typ($debugLine['object'], $withHash, true) : '';

			$file = basename($debugLine['file'] ?? '');
			$file = str_replace('class.', '', $file);
			$file = str_replace('.php', '', $file);
			$nextFunc = ifsetor($debug[$i + 1]['function']);
			$line = ifsetor($debugLine['line']);
			$content[] = $file . '::' . $nextFunc . '#' . $line . ':' .
				$object . '->' . $debugLine['function'];
			if (--$limit === 0) {
				break;
			}
		}

		if ($join) {
			$content = implode($join, $content);
		}

		return $content;
	}

	/**
	 * http://stackoverflow.com/a/2510459/417153
	 * @param int $bytes
	 * @param int $precision
	 */
	public static function formatBytes($bytes, $precision = 2): string
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

	public static function getArraySize(array $tmpArray): int
	{
		$size = [];
		foreach ($tmpArray as $key => $row) {
			$size[$key] = strlen(serialize($row));
		}

//		debug(array_sum($size), $size);
		return array_sum($size);
	}

	public static function peek($row): ?array
	{
		$typeName = null;
		if (is_object($row)) {
			$typeName = get_class($row);
			$row = get_object_vars($row);
		}

		if ($row !== null) {
			$types = array_map(static function ($a) use ($typeName): array {
				$val = null;
				if (is_scalar($a)) {
					$val = substr($a, 0, 10) . '...';
				} elseif (is_array($a)) {
					$val = self::peek($a);
				} elseif (is_object($a)) {
					$typeName = get_class($a);
					$val = self::peek($a);
				}

				return [
					'type' => $typeName ?: trim(strip_tags(typ($a) . '')),
					'value' => $val,
				];
			}, $row);
			return array_combine(array_keys($row), $types);
		}

		return null;
	}

	/**
	 * This is like peek() but recursive
	 * @param     $row
	 * @param int $spaces
	 */
	public static function dumpStruct($row, $spaces = 0): void
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

	public static function findObject($struct, $type, $path = []): void
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

	public static function investigate($var): void
	{
		llog('Investigate');
		if (is_object($var)) {
			$var = get_object_vars($var);
		}

		if (is_scalar($var)) {
			$var = [$var];
		}

		llog('Keys: ' . implode(',', array_keys($var)));
		foreach ($var as $key => $var) {
			llog(' * ' . $key . ': [' . trim(strip_tags(typ($var))) . ']');
		}

		llog('-----');
	}

	/**
	 * Main entry point.
	 * @param mixed $params
	 * @return string
	 */
	public function debug($params)
	{
		$content = '';
		if (!$this->renderer) {
			// don't show anything to the users who are not DEVELOPMENT
			//pre_print_r($params);
			//debug_pre_print_backtrace();
			return '';
		}

		$method = 'debugWith' . $this->renderer;
		if (method_exists($this, $method)) {
			$content = $this->$method($params);
		} elseif (class_exists($this->renderer)) {
			$dgger = new $this->renderer($this);
			$content = $dgger->debug($params);
			echo $content;
		} else {
			pre_print_r($params);
			debug_pre_print_backtrace();
		}

		return $content;
	}

	/**
	 * @param mixed $params - any type
	 */
	public function debugWithHTML($params): void
	{
		if (!class_exists('DebugHTML')) {
			debug_pre_print_backtrace();
		}

		$debugHTML = new DebugHTML($this);
		$content = $debugHTML->render($params);
		if (!is_null($content)) {
			print($content);
		}

		if (ob_get_level() == 0) {
			flush();
		}
	}

	/**
	 * @param mixed $debugAccess
	 * @throws \Exception
	 */
	public function consoleLog($debugAccess): void
	{
		if ($this->request->isAjax()) {
			return;
		}

		if (func_num_args() > 1) {
			$debugAccess = func_get_args();
		}

		$json = json_encode($debugAccess, JSON_THROW_ON_ERROR);
		$script = '<script type="text/javascript">
		setTimeout(function () {
			var json = ' . $json . ';
			console.log(json);
		}, 1);
		</script>';
		echo $script;
	}

}
