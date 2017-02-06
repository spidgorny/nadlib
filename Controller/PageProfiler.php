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
		$index = Index::getInstance();
		if (DEVELOPMENT &&
			!$this->request->isAjax() &&
			!in_array(get_class($index->controller), array('Lesser')))
		{
			if (!$this->request->isCLI()) {
				$content .= '<div class="profiler noprint">';
				$content .= $this->getURL();
				$content .= $this->getGET();
				$content .= $this->getPOST();
				$content .= $this->getHeader();
				$content .= $this->getFooter();
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

	/**
	 * @return array
	 */
	private function getURL() {
		$url = clone $this->request->getURL();
		$url->makeRelative();
		$params = $url->getParams();
		$url->clearParams();
		$fullURL = $this->request->getLocation() . $url;
		$urlText = $this->request->getLocation() . ' ' . $url;
		$content = '<a href="' . $fullURL . '">' . $urlText . '</a>' . BR;
		return $content;
	}

	/**
	 * @return string
	 */
	private function getGET() {
		$url = $this->request->getURL();
		$params = $url->getParams();
		$content .= $this->html->h4('GET');
		$content .= $this->html->pre(json_encode($params, JSON_PRETTY_PRINT));
		return $content;
	}

	/**
	 * @return string
	 */
	private function getPOST() {
		$content .= $this->html->h4('POST');
		$content .= $this->html->pre(json_encode($_POST, JSON_PRETTY_PRINT));
		return $content;
	}

	/**
	 * @return string
	 */
	private function getHeader() {
		$index = Index::getInstance();
		$content .= $this->html->h4('Header');
		$header = json_encode($index->header, JSON_PRETTY_PRINT);
		$header = str_replace('\/', '/', $header);
		$content .= $this->html->pre($header);
		return $content;
	}

	/**
	 * @return string
	 */
	private function getFooter() {
		$index = Index::getInstance();
		$content .= $this->html->h4('Footer');
		$footer = json_encode($index->footer, JSON_PRETTY_PRINT);
		$footer = str_replace('\/', '/', $footer);
		$content .= $this->html->pre($footer);
		return $content;
	}

}
