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

	/**
	 * @phpstan-consistent-constructor
	 */
	public function __construct()
	{
		$this->request = Request::getInstance();
		$this->html = new HTML();
	}

	/**
	 * @throws Exception
	 */
	public function render(): array
	{
		$content = [];
		if ($this->canOutput()) {
			$content[] = '<div class="profiler noprint">';
			$content[] = $this->getURL();
			$content[] = $this->getGET();
			$content[] = $this->getPOST();
			$content[] = $this->getHeader();
			$content[] = $this->getFooter();
			$content[] = $this->getSession();
			$content[] = $this->html->s(OODBase::getCacheStatsTable());
			$content[] = $this->getTaylorProfiler();
			$content[] = '</div>';

			$ft = new FloatTime(true);
			$content[] = $ft->render();
		}

		return $content;
	}

	public function canOutput(): bool
	{
		if (class_exists('Index')) {
			$index = Index::getInstance();
			$exceptions = $index->controller ? get_class($index->controller) : null == 'Lesser';
		} else {
			$exceptions = false;
		}

		$debug_page = isset($_COOKIE['debug_page'])
			? $_COOKIE['debug_page']
			: ifsetor($_COOKIE['debug']);

		return DEVELOPMENT
			&& !$this->request->isAjax()
			&& !$exceptions
			&& !$this->request->isCLI()
			&& $debug_page;
	}

	protected function getURL(): string
	{
		$url = clone $this->request->getURL();
		$url->makeRelative();

		$url->getParams();
		$url->clearParams();

		$fullURL = $this->request->getLocation() . $url;
		$urlText = $this->request->getLocation() . ' ' . $url;
		return '<a href="' . $fullURL . '">' . $urlText . '</a>' . BR;
	}

	protected function getGET(): string
	{
		$content = '';
		$url = $this->request->getURL();
		$params = $url->getParams();
		$content .= $this->html->h4('GET');
		return $content . $this->html->pre(json_encode($params, JSON_PRETTY_PRINT));
	}

	protected function getPOST(): string
	{
		$content = '';
		$content .= $this->html->h4('POST');
		return $content . $this->html->pre(json_encode($_POST, JSON_PRETTY_PRINT));
	}

	/**
	 * @throws Exception
	 */
	protected function getHeader(): string
	{
		$content = '';
		$content .= $this->html->h4('Header');

		$header = '';

		$header = str_replace('\/', '/', $header);
		$header = str_replace('\"', '"', $header);
		return $content . $this->html->pre($header);
	}

	/**
	 * @throws Exception
	 */
	protected function getFooter(): string
	{
		$content = '';
		$content .= $this->html->h4('Footer');
//		$footer = json_encode($index->footer, JSON_PRETTY_PRINT);
		$footer = '';
		$footer = str_replace('\/', '/', $footer);
		$footer = str_replace('\"', '"', $footer);
		return $content . $this->html->pre($footer);
	}

	protected function getSession(): string
	{
		$content = '';
		$content .= $this->html->h4('Session');
		$session = json_encode($_SESSION, JSON_PRETTY_PRINT);
		$session = str_replace('\/', '/', $session);
		return $content . $this->html->pre($session);
	}

	/**
	 * @return mixed[]
	 */
	protected function getTaylorProfiler(): array
	{
		$content = [];
		/** @var $profiler TaylorProfiler */
		$profiler = TaylorProfiler::getInstance();
		if ($profiler) {
			$content[] = $profiler->printTimers(true);
			$content[] = TaylorProfiler::dumpQueries();
			//$content[] = $profiler->printTrace(true);
			//$content[] = $profiler->analyzeTraceForLeak();
		}

		return $content;
	}

}
