<?php

class DebugFirebug
{

	public static function canFirebug()
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

	public function debug($params, $title = '')
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

}
