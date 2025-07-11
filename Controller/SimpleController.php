<?php

use nadlib\IndexInterface;
use spidgorny\nadlib\HTTP\URL;

/**
 * Class SimpleController
 * @mixin HTML
 * @method error($content, $httpCode = 500)
 * @method info($content)
 * @method success($content)
 */
abstract class SimpleController
{

	/**
	 * Instance per class
	 * @var Controller[]
	 */
	protected static $instance = [];
	/**
	 * @var Index|IndexInterface
	 */
	public $index;
	/**
	 * @var Request
	 * @public for injecting something in PHPUnit
	 */
	public $request;
	/**
	 * Will be taken as a <title> of the HTML table
	 * @var string
	 */
	public $title;
	public $encloseTag = 'h2';

	public $log = [];

	/**
	 * @var HTML
	 */
	protected $html;

	public function __construct()
	{
		if (ifsetor($_REQUEST['d']) === 'log') {
			echo get_class($this) . '::' . __METHOD__ . BR;
		}
		$this->index = class_exists('Index', false)
			? Index::getInstance() : null;
		$this->request = Request::getInstance();
		$this->title = $this->title ?: last(trimExplode('\\', get_class($this)));
		//debug_pre_print_backtrace();
		$this->html = new HTML();
		self::$instance[get_class($this)] = $this;
	}

	public static function getInstance()
	{
		$static = get_called_class();
		//if ($static == 'Controller') throw new Exception('Unable to create Controller instance');
		$isset = isset(self::$instance[$static]);
		//debug(array_keys(self::$instance), $static, $isset);
		if ($isset) {
			$result = self::$instance[$static];
		} else {
			$index = Index::getInstance();
			if ($index->controller instanceof $static) {
				$result = $index->getController();
			} else {
				$result = new $static;
			}
		}
		//debug($isset, get_class($index), get_class($result));
		return $result;
	}

	public function __call($method, array $arguments)
	{
		if (method_exists($this->html, $method)) {
			return call_user_func_array($this->html->$method, $arguments);
		}

		throw new RuntimeException('Method ' . $method . ' not found in ' . get_class($this));
	}

	/**
	 * Combines params with $this->linkVars
	 * Use makeURL() for old functionality
	 * @param array $params
	 * @param null $prefix
	 * @return URL
	 */
	public function getURL(array $params = [], $prefix = null)
	{
		if ($params || $prefix) {
			throw new InvalidArgumentException('Use makeURL() instead of ' . __METHOD__);
		}
		//		$params = $params + $this->linkVars;
		//		debug($params);
		//		return $this->makeURL($params, $prefix);
		return ClosureCache::getInstance(spl_object_hash($this), function () {
			return new URL();
		})->get();
	}

	/*function redirect($url) {
		if (DEVELOPMENT) {
			return '<script>
				setTimeout(function() {
					document.location.replace("'.str_replace('"', '&quot;', $url).'");
				}, 5000);
			</script>';
		} else {
			return '<script> document.location.replace("'.str_replace('"', '&quot;', $url).'"); </script>';
		}
	}*/

	/**
	 * This function prevents performAction() from doing nothing
	 * if there is a __CLASS__.phtml file in the same folder
	 * @return MarkdownView|string|View|string[]|null|void
	 */
	public function indexAction()
	{
		$content = $this->renderTemplate();
		$title = str_replace('\\', '-', get_class($this));
		return $this->html->div($content, $title);
	}

	public function renderTemplate()
	{
		$filePHTML = get_class($this) . '.phtml';
		$fileMD = get_class($this) . '.md';

		$reflector = new ReflectionClass(get_class($this));
		$classDir = dirname($reflector->getFileName());
		if (file_exists('template/' . $filePHTML)) {
			$content = new View($filePHTML, $this);
		} elseif (file_exists('template/' . $fileMD)) {
			$content = new MarkdownView($fileMD, $this);
		} elseif (file_exists($classDir . '/' . $filePHTML)) {
			$content = new View($classDir . '/' . $filePHTML, $this);
		} elseif (file_exists($classDir . '/' . $fileMD)) {
			$content = new MarkdownView($classDir . '/' . $fileMD, $this);
		} else {
			$content = '';
		}

		//		debug($filePHTML, $fileMD);

		return is_object($content)
			? $content->render()
			: $content;
	}

	public function render()
	{
		$content[__METHOD__] = $this->performAction();
		return $content;
	}

	/**
	 * Will call indexAction() method if no $action provided
	 * @param string|null $action
	 * @return false|mixed|string
	 * @throws ReflectionException
	 * @throws Exception404
	 */
	public function performAction($action = null)
	{
		$method = $action ?: $this->detectAction();
		if (!$method) {
			throw new Exception404('No action provided');
		}

		$method .= 'Action';        // ZendFramework style
//		if ($method !== 'updateNotificationCounterAction') {
//			llog(get_class($this), $method, method_exists($this, $method));
//		}

		$proxy = $this;
		// used to call an $action on PrepareGive, PrepareBurn instead of direct PrepareRequest class
		$proxyClassName = $this->request->getTrim('proxy');
		if ($proxyClassName) {
			if (get_class($this) === $this->request->getTrim('proxyOf')) {
				$proxy = new $proxyClassName($this);
			}
		}

//		llog('SimpleController->performAction', [
//			'class' => get_class($this),
//			'action' => $method,
//			'exists' => method_exists($proxy, $method)
//		]);
		if (!method_exists($proxy, $method)) {
			llog($method, 'does not exist in', get_class($this));
			// other classes except main controller may result in multiple messages
//				Index::getInstance()->message('Action "'.$method.'" does not exist in class "'.get_class($this).'".');
			throw new Exception404('Action "' . $method . '" does not exist in class "' . get_class($this) . '".');
		}

		if (Request::isCLI()) {
			$assoc = array_slice(ifsetor($_SERVER['argv'], []), 3);
			return call_user_func_array([$proxy, $method], $assoc);
		}

		$this->request->un_set('action');
		$caller = new MarshalParams($proxy);
		return $caller->call($method);
	}

	public function detectAction()
	{
		if (Request::isCLI()) {
			//debug($_SERVER['argv']);
//			llog('iscli', true);
			return ifsetor($_SERVER['argv'][2]);    // it was 1
		}

		$action = $this->request->getTrim('action');
		return $action ?: 'index';
	}

	public function __toString()
	{
		return $this->s($this->render());
	}

	public function s($something)
	{
		return MergedContent::mergeStringArrayRecursive($something);
	}

	/**
	 * Wraps the content in a div/section with a header.
	 * The header is linkable.
	 * @param string|array|ToStringable $content
	 * @param string $caption
	 * @param string $h
	 * @param array $more
	 * @return ToStringable
	 * @throws Exception
	 */
	public function encloseInAA($content, $caption = '', $h = null, array $more = [])
	{
		$h = $h ?: $this->encloseTag;
		$content = $this->s($content);
		if ($caption) {
			$content = [
				'caption' => $this->getCaption($caption, $h),
				$content
			];
		}
		$more['class'] = ifsetor($more['class'], 'padding clearfix');
		$more['class'] .= ' ' . get_class($this);
		return new HTMLTag('section', $more, $content, true);
	}

	public function getCaption($caption, $hTag)
	{
		return '<' . $hTag . '>' .
			$caption .
			'</' . $hTag . '>';
	}

	public function log($action, ...$data)
	{
		llog($action, ...$data);
		if (is_array($data) && count($data) === 1) {
			$data = $data[0];
		}
		$this->log[] = new LogEntry($action, $data);
	}

}
