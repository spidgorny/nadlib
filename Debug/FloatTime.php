<?php

class FloatTime {

	var $withCSS;

	function __construct($withCSS) {
		$this->withCSS = $withCSS;
	}

	function render() {
		if (Request::isCLI()) return '';
		$totalTime = TaylorProfiler::getElapsedTime();
		$dbTime = $this->getDBTime();
		if (Session::isActive()) {
			// total
			$totalMax = ifsetor($_SESSION[__CLASS__]['totalMax']);
			if ($totalMax > 0) {
				$totalBar = '<img src="' . ProgressBar::getBar($totalTime / $totalMax * 100) . '" />';
			} else {
				$totalBar = '<img src="'.ProgressBar::getBar(0).'" />';
			}
			$_SESSION[__CLASS__]['totalMax'] = max($_SESSION[__CLASS__]['totalMax'], $totalTime);

			// db
			$dbMax = ifsetor($_SESSION[__CLASS__]['dbMax']);
			if ($dbMax > 0) {
				$dbBar = '<img src="'.ProgressBar::getBar($dbTime/$dbMax*100).'" />';
			} else {
				$db = class_exists('Config') ? Config::getInstance()->getDB() : NULL;
				$ql = $db ? $db->getQueryLog() : NULL;
				$dbBar = $ql ? sizeof($ql->queryLog) : gettype2($ql);
			}
			$_SESSION[__CLASS__]['dbMax'] = max($_SESSION[__CLASS__]['dbMax'], $dbTime);
		} else {
			$totalBar = 'no session';
			$totalMax = '';
			$dbTime = '';
			$dbBar = 'no session';
			$dbMax = '';
		}

		$peakMem = number_format(memory_get_peak_usage()/1024/1024, 3, '.', '');
		$maxMem = (new Bytes(ini_get('memory_limit')))->getBytes();
		$memUsage = memory_get_peak_usage() / $maxMem * 100;
		$memBar = '<img src="'.ProgressBar::getBar($memUsage).'" />';

		ob_start();
		require(__DIR__.'/FloatTime.phtml');
		$content = ob_get_clean();

		if ($this->withCSS) {
			$content .= '<style>' . file_get_contents(
					dirname(__FILE__) . '/../CSS/TaylorProfiler.css'
				) . '</style>';
		}
		return $content;
	}

	/**
	 * @return int|number|string
	 */
	private function getDBTime() {
		$dbTime = 0;
		$db = class_exists('Config') ? Config::getInstance()->getDB() : NULL;
		$ql = $db ? $db->getQueryLog() : NULL;
		if ($ql && is_array($ql)) {
			$dbTime = ArrayPlus::create($ql)->column('sumtime')->sum();
			$dbTime = number_format($dbTime, 3, '.', '');
		} elseif ($ql && is_object($ql)) {
			$dbTime = $ql->getDBTime();
			$dbTime = number_format($dbTime, 3, '.', '');
		} elseif (isset($db->queryTime)) {
			$dbTime = $db->queryTime;
			$dbTime = number_format($dbTime, 3, '.', '');
		}
		//debug($dbTime, gettype2($db), $db->queryLog, $db->queryTime);
		return $dbTime;
	}

}