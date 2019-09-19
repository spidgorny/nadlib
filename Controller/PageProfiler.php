<?php

class PageProfiler
{

	/**
	 * @var Request
	 */
	public $request;

	/**
	 * @var HTML
	 */
	public $html;

	function __construct()
	{
		$this->request = Request::getInstance();
		$this->html = new HTML();
	}

	function render()
	{
		$content = '';
		$index = Index::getInstance();
		$exceptions = in_array(get_class($index->controller), array('Lesser'));
		$debug_page = isset($_COOKIE['debug_page'])
			? $_COOKIE['debug_page']
			: ifsetor($_COOKIE['debug']);
		if (DEVELOPMENT
			&& !$this->request->isAjax()
			&& !$exceptions
			&& !$this->request->isCLI()
			&& $debug_page
		) {
			$content .= '<div class="profiler noprint">';
			$content .= $this->getURL();
			$content .= $this->getGET();
			$content .= $this->getPOST();
			$content .= $this->getHeader();
			$content .= $this->getFooter();
			$content .= $this->getSession();
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
		return $content;
	}

	/**
	 * @return string
	 */
	private function getURL()
	{
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
	private function getGET()
	{
		$content = '';
		$url = $this->request->getURL();
		$params = $url->getParams();
		$content .= $this->html->h4('GET');
		$content .= $this->html->pre(json_encode($params, JSON_PRETTY_PRINT));
		return $content;
	}

	/**
	 * @return string
	 */
	private function getPOST()
	{
		$content = '';
		$content .= $this->html->h4('POST');
		$content .= $this->html->pre(json_encode($_POST, JSON_PRETTY_PRINT));
		return $content;
	}

	/**
	 * @return string
	 */
	private function getHeader()
	{
		$content = '';
		$index = Index::getInstance();
		$content .= $this->html->h4('Header');
		$header = json_encode($index->header, JSON_PRETTY_PRINT);
		$header = str_replace('\/', '/', $header);
		$header = str_replace('\"', '"', $header);
		$content .= $this->html->pre($header);
		return $content;
	}

	/**
	 * @return string
	 */
	private function getFooter()
	{
		$content = '';
		$index = Index::getInstance();
		$content .= $this->html->h4('Footer');
		$footer = json_encode($index->footer, JSON_PRETTY_PRINT);
		$footer = str_replace('\/', '/', $footer);
		$footer = str_replace('\"', '"', $footer);
		$content .= $this->html->pre($footer);
		return $content;
	}

	/**
	 * @return string
	 */
	private function getSession()
	{
		$content = '';
		$content .= $this->html->h4('Session');
		$session = json_encode($_SESSION, JSON_PRETTY_PRINT);
		$session = str_replace('\/', '/', $session);
		$content .= $this->html->pre($session);
		return $content;
	}

}
