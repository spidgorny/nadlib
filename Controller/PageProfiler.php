<?php

class PageProfiler {

	/**
	 * @var Request
	 */
	var $request;

	/**
	 * @var HTML
	 */
	var $html;

	function __construct()
	{
		$this->request = Request::getInstance();
		$this->html = new HTML();
	}

	function render() {
		$content = '';
		if (DEVELOPMENT &&
			!$this->request->isAjax() &&
			!in_array(get_class($this->controller), array('Lesser')))
		{
			if (!$this->request->isCLI()) {
				$content .= '<div class="profiler noprint">';
				$url = $this->request->getURL();
				$url->makeRelative();
				$params = $url->getParams();
				$url->clearParams();
				$fullURL = $this->request->getLocation(). $url;
				$urlText = $this->request->getLocation().' '. $url;
				$content .= '<a href="'. $fullURL .'">'. $urlText .'</a>'.BR;
				if ($params) {
					$content .= $this->html->pre(json_encode($params, JSON_PRETTY_PRINT));
				}

				$content .= $this->html->s(OODBase::getCacheStatsTable());

				/** @var $profiler TaylorProfiler */
				$profiler = TaylorProfiler::getInstance();
				if ($profiler) {
					$content .= $profiler->printTimers(true);
					$content .= TaylorProfiler::dumpQueries();
					//$content .= $profiler->printTrace(true);
					//$content .= $profiler->analyzeTraceForLeak();
				}

				$content .= '</div>';

				$ft = new FloatTime(true);
				$content .= $ft->render();
			}
		}
		return $content;
	}

}
