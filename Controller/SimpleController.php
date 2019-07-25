<?php

use spidgorny\nadlib\HTTP\URL;

/**
 * Class SimpleController
 * @mixin HTML
 * @method error($content)
 * @method info($content)
 * @method success($content)
 */
abstract class SimpleController
{

	/**
	 * @var Index|\nadlib\IndexInterface
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

	/**
	 * Instance per class
	 * @var Controller[]
	 */
	protected static $instance = [];

	public $encloseTag = 'h2';

	public $log = [];

	/**
	 * @var HTML
	 */
	protected $html;

	public function __construct()
	{
		if (ifsetor($_REQUEST['d']) == 'log') {
			echo get_class($this) . '::' . __METHOD__ . BR;
		}
		$this->index = class_exists('Index', false)
			? Index::getInstance(false) : null;
		$this->request = Request::getInstance();
		$this->title = $this->title ? $this->title
			: last(trimExplode('\\', get_class($this)));
		//debug_pre_print_backtrace();
		$this->html = new HTML();
		self::$instance[get_class($this)] = $this;
	}

	public function __call($method, array $arguments)
	{
		if (method_exists($this->html, $method)) {
			return call_user_func_array($this->html->$method, $arguments);
		} else {
			throw new RuntimeException('Method '.$method.' not found in '.get_class($this));
		}
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

	public function render()
	{
		$content[] = $this->performAction();
		return $content;
	}

	/**
	 * This function prevents performAction() from doing nothing
	 * if there is a __CLASS__.phtml file in the same folder
	 * @return MarkdownView|string|View
	 */
	public function indexAction()
	{
		$content = $this->renderTemplate();
		$content = $this->html->div($content, str_replace('\\', '-', get_class($this)));
		return $content;
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

	public function __toString()
	{
		return $this->s($this->render());
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
		$h = $h ? $h : $this->encloseTag;
		$content = $this->s($content);
		if ($caption) {
			$content = [
				'caption' => $this->getCaption($caption, $h),
				$content
			];
		}
		$more['class'] = ifsetor($more['class'], 'padding clearfix');
		$more['class'] .= ' ' . get_class($this);
		//debug_pre_print_backtrace();
		//$more['style'] = "position: relative;";	// project specific
		$content = new HTMLTag('section', $more, $content, true);
		return $content;
	}

	public function getCaption($caption, $hTag)
	{
		return '<' . $h . '>' .
			$caption .
			'</' . $h . '>';
	}
	
	public function performAction($action = null)
	{
		$content = '';
		if ($this->request->isCLI()) {
			//debug($_SERVER['argv']);
			$reqAction = ifsetor($_SERVER['argv'][2]);    // it was 1
		} else {
			$reqAction = $this->request->getTrim('action');
		}
		//		debug($reqAction);
		$method = $action
			?: (!empty($reqAction) ? $reqAction : 'index');
		if ($method) {
			$method .= 'Action';        // ZendFramework style
			//			debug($method, method_exists($this, $method));

			$proxy = $this->request->getTrim('proxy');
			if ($proxy) {
				$proxy = new $proxy($this);
			} else {
				$proxy = $this;
			}

			if (method_exists($proxy, $method)) {
				if ($this->request->isCLI()) {
					$assoc = array_slice(ifsetor($_SERVER['argv'], []), 3);
					$content = call_user_func_array([$proxy, $method], $assoc);
				} else {
					$caller = new MarshalParams($proxy);
					$content = $caller->call($method);
				}
			} else {
				// other classes except main controller may result in multiple messages
//				Index::getInstance()->message('Action "'.$method.'" does not exist in class "'.get_class($this).'".');
			}
		}
		return $content;
	}

	public function s($something)
	{
		return MergedContent::mergeStringArrayRecursive($something);
	}

	public function log($action, ...$data)
	{
		if (is_array($data) && sizeof($data) == 1) {
			$data = $data[0];
		}
		$this->log[] = new LogEntry($action, $data);
	}


}
