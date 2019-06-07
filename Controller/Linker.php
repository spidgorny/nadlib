<?php

use spidgorny\nadlib\HTTP\URL;

class Linker
{

	/**
	 * Will be set according to mod_rewrite
	 * Override in __construct()
	 * @public to be accessed from Menu
	 * @var bool
	 */
	public $useRouter = false;

	public $linkVars = [];

	/**
	 * @var Request
	 */
	public $request;

	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * @param array|string $params
	 * @param null $prefix
	 * @return URL
	 * @public for View::link
	 * @use getURL()
	 */
	public function makeURL(array $params, $prefix = null)
	{
		if (!$prefix && $this->useRouter) { // default value is = mod_rewrite
			$class = ifsetor($params['c']);
			if ($class && !$prefix) {
				unset($params['c']);    // RealURL
				$prefix = $class;
			} else {
				$class = null;
			}
		} else {
			$class = null;
			// this is the only way to supply controller
			//unset($params['c']);
		}

		$location = $this->request->getLocation();
		$url = new URL($prefix
			? $location . $prefix
			: $location, $params);
		$path = $url->getPath();
		if ($this->useRouter && $class) {
			$path->setFile($class);
			$path->setAsFile();
		}
		//debug($prefix, get_class($path));
		$url->setPath($path);
		nodebug([
			'method' => __METHOD__,
			'params' => $params,
			'prefix' => $prefix,
			'useRouter' => $this->useRouter,
			'class' => $class,
			'class($url)' => get_class($url),
			'class($path)' => get_class($path),
			'$this->linkVars' => $this->linkVars,
			'return' => $url . '',
			'location' => $location . '',
		]);
		return $url;
	}

	/**
	 * Only appends $this->linkVars to the URL.
	 * Use this one if your linkVars is defined.
	 * @param array $params
	 * @param string $page
	 * @return URL
	 */
	public function makeRelURL(array $params = [], $page = null)
	{
		return $this->makeURL(
			$params                           // 1st priority
			+ $this->getURL()->getParams()            // 2nd priority
			+ $this->linkVars,
			$page
		);                // 3rd priority
	}

	/**
	 * Returns '<a href="$page?$params" $more">$text</a>
	 * @param string $text
	 * @param array $params
	 * @param string $page
	 * @param array $more
	 * @param bool $isHTML
	 * @return HTMLTag
	 */
	public function makeLink($text, array $params, $page = '', array $more = [], $isHTML = false)
	{
		//debug($text, $params, $page, $more, $isHTML);
		$content = new HTMLTag('a', [
				'href' => $this->makeURL($params, $page),
			] + $more, $text, $isHTML);
		return $content;
	}

	public function makeAjaxLink($text, array $params, $div, $jsPlus = '', $aMore = [], $prefix = '')
	{
		$url = $this->makeURL($params, $prefix);
		$link = new HTMLTag('a', $aMore + [
				'href' => $url,
				'onclick' => '
			jQuery(\'#' . $div . '\').load(\'' . $url . '\');
			return false;
			' . $jsPlus,
			], $text, true);
		return $link;
	}

	/**
	 * @see makeRelURL
	 * @param array $params
	 * @return URL
	 * @throws Exception
	 */
	public function adjustURL(array $params)
	{
		return URL::getCurrent()->addParams([
				'c' => get_class(Index::getInstance()->controller),
			] + $params);
	}

	/**
	 * Just appends $this->linkVars
	 * @param string $text
	 * @param array $params
	 * @param string $page
	 * @return HTMLTag
	 */
	public function makeRelLink($text, array $params, $page = '?')
	{
		return new HTMLTag('a', [
			'href' => $this->makeRelURL($params, $page)
		], $text);
	}

	public function linkToAction($action = '', array $params = [], $controller = null)
	{
		if (!$controller) {
			$controller = get_class($this);
		}
		$params = [
				'c' => $controller,
			] + $params;
		if ($action) {
			$params += [
				'action' => $action,
			];
		}
		return $this->makeURL($params);
	}

	public function linkPage($className)
	{
		$obj = new $className();
		$title = $obj->title;
		$html = new HTML();
		return $html->a($className, $title);
	}

	public function makeActionURL($action = '', array $params = [], $path = '')
	{
		$urlParams = [
				'c' => get_class($this),
				'action' => $action,
			] + $params;
		$urlParams = array_filter($urlParams);
		return $this->makeURL($urlParams, $path);
	}

}
