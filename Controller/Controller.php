<?php

use spidgorny\nadlib\HTTP\URL;

/**
 * Class Controller - a base class for all front-facing pages.
 * Extend and implement your own render() function.
 * It should collect output into a string and return it.
 * Additional actions can be processed by calling
 * $this->performAction() from within render().
 * It checks for the &action= parameter and appends an 'Action' suffix to get the function name.
 *
 * Can be called from CLI with parameters e.g.
 * > php index.php SomeController -action cronjob
 * will call cronjobAction instead of default render()
 * @mixin Linker
 * @mixin HTML
 */
abstract class Controller extends SimpleController
{

	use HTMLHelper;

	/**
	 * accessible without login
	 * @var bool
	 */
	public static $public = false;

	/**
	 * @var bool
	 * use $this->preventDefault() to set
	 * Check manually in render()
	 */
	public $noRender = false;

	/**
	 * Will be taken as a <title> of the HTML table
	 * @var string
	 */
	public $title;

	/**
	 * @var SomeKindOfUser|null
	 */
	public $user;

	/**
	 * Allows selecting fullScreen layout of the template
	 *
	 * @var string|Wrap
	 */
	public $layout;

	/**
	 * Used by Collection to get the current sorting method.
	 * Ugly, please reprogram.
	 * @var string
	 */
	public $sortBy;

	/**
	 * @var Linker
	 */
	public $linker;

	/**
	 * @var DBLayer|DBLayerPDO|DBLayerSQLite|DBLayerBase|DBInterface
	 */
	protected DBInterface $db;
	protected float $lastMicrotime;
	protected bool $isDev = false;

	public function __construct()
	{
		parent::__construct();
		$this->isDev = getenv('DEVELOPMENT');
//		if (!$this->config && $this->index) {
//			$this->config = $this->index->getConfig();
//		}
//
//		if ($this->config) {
//			// move this into AppController
//			// some projects don't need DB or User
////			$this->db = $this->config->getDB();
////			$this->user = $this->config->getUser();
//			$this->config->mergeConfig($this);
//		}

		$this->linker = new Linker(get_class($this), $this->request);
		$this->linker->useRouter = $this->request->apacheModuleRewrite();

//		if (!$this->linker->useRouter) {
//			$this->linker->linkVars['c'] = get_class($this);
//		}
	}

	public static function link($text = null, array $params = [])
	{
		$self = static::class;
		return new HTMLTag('a', [
			'href' => $self::href($params)
		], $text ?: $self);
	}

	public static function href(array $params = [])
	{
		return stripNamespace(static::class) . static::buildQuery($params);
	}

	public static function buildQuery(array $params = [])
	{
		if ($params === []) {
			return '';
		}

		return '?' . http_build_query($params);
	}

	public function __call($method, array $arguments)
	{
		if (method_exists($this->linker, $method)) {
			return call_user_func_array([$this->linker, $method], $arguments);
		}

		if (method_exists($this->html, $method)) {
			return call_user_func_array([$this->html, $method], $arguments);
		}

		throw new RuntimeException('Method ' . $method . ' not found in ' . get_class($this));
	}

	/**
	 * @return array
	 * @deprecated
	 * @see slTable::showAssoc()
	 */
	public function getAssocTable(array $data)
	{
		$table = [];
		foreach ($data as $key => $val) {
			$table[] = ['key' => $key, 'val' => $val];
		}

		return $table;
	}

	public function encloseInFieldset($title, $content)
	{
		$title = $title instanceof HtmlString ? $title : htmlspecialchars($title);
		$content = $this->s($content);
		return '<fieldset><legend>' . $title . '</legend>' . $content . '</fieldset>';
	}

	public function wrapInDiv($content, $className = '')
	{
		$content = $this->s($content);
		return new HTMLTag('div', ['class' => $className], $content, true);
	}

	/**
	 * Wraps the content in a div/section with a header.
	 * The header is linkable.
	 * @param $content
	 * @param string $caption
	 * @param string|null $h
	 * @param array $more
	 * @return array|string|string[]|ToStringable
	 * @throws Exception
	 */
	public function encloseInAA($content, $caption = '', $h = null, array $more = [])
	{
		$h = $h ?: $this->encloseTag;
		$start = $this->lastMicrotime ?? $_SERVER['REQUEST_TIME_FLOAT'];
		$content = $this->s($content);
		if ($caption) {
			$duration = $this->isDev ? ' (' . number_format(microtime(true) - $start, 4) . ')' : '';
			$content = [
				'caption' => $this->getCaption($caption . $duration, $h),
				$content
			];
		}

		$more['class'] = ifsetor($more['class'], 'padding clearfix');
		$more['class'] .= ' ' . get_class($this);

		$this->lastMicrotime = microtime(true);
		return new HTMLTag('section', $more, $content, true);
	}

	/**
	 * @param string $caption
	 * @param string $hTag
	 * @return string
	 * @throws Exception
	 */
	public function getCaption($caption, $hTag = 'h3')
	{
//		$al = AutoLoad::getInstance();
		$slug = URL::friendlyURL($caption);
		return '
			<' . $hTag . ' id="' . $slug . '">' .
			$caption .
			'</' . $hTag . '>';
	}

	public function encloseInToggle($content, string $title, string $height = 'auto', $isOpen = null, string $tag = 'h3')
	{
		if ($content) {
			// buggy: prevents all clicks on the page in KA.de
			$nadlibPath = AutoLoad::getInstance()->nadlibFromDocRoot;
			$this->index->addJQuery();
			$this->index->addJS($nadlibPath . 'js/showHide.js');
			$this->index->addJS($nadlibPath . 'js/encloseInToggle.js');
			$id = uniqid('', true);

			$content = '<div class="encloseIn">
				<' . $tag . '>
					<a class="show_hide" href="#" rel="#' . $id . '">
						<span>' . ($isOpen ? '&#x25BC;' : '&#x25BA;') . '</span>
						' . $title . '
					</a>
				</' . $tag . '>
				<div id="' . $id . '"
					class="toggleDiv"
					style="max-height: ' . $height . '; overflow: auto;
					' . ($isOpen ? '' : 'display: none;') . '">' . $content . '</div>
			</div>';
		}

		return $content;
	}

	public function preventDefault(): void
	{
		$this->noRender = true;
	}

	/**
	 * Commented to allow get_class_methods() to return false
	 * @return string
	 */
	//function getMenuSuffix() {
	//	return '';
	//}
	/**
	 * Uses float: left;
	 * @params array[string]
	 * @return mixed|string
	 */
	public function inColumns()
	{
		return call_user_func_array(static::inColumnsHTML5(...), func_get_args());
	}

	public function inColumnsHTML5(...$elements)
	{
		$this->index->addCSS(AutoLoad::getInstance()->nadlibFromDocRoot . 'CSS/display-box.css');
		$content = '';
		foreach ($elements as $html) {
			$html = $this->s($html);
			$content .= '<div class="flex-box flex-equal">' . $html . '</div>';
		}

		return '<div class="display-box">' . $content . '</div>';
	}

	/**
	 * Commented to allow get_class_methods() to return false
	 * @return string
	 */
	//function getMenuSuffix() {
	//	return '';
	//}


	public function inEqualColumnsHTML5(...$elements)
	{
		$this->index->addCSS(AutoLoad::getInstance()->nadlibFromDocRoot . 'CSS/display-box.css');
		$content = '';
		foreach ($elements as $html) {
			$html = $this->s($html);
			$content .= '<div class="flex-box flex-equal">' . $html . '</div>';
		}

		return '<div class="display-box equal">' . $content . '</div>';
	}

	public function encloseInTableHTML3(array $cells, array $more = [], array $colMore = [])
	{
		if ($more === []) {
			$more['class'] = "encloseInTable";
		}

		$content[] = '<table ' . HTMLTag::renderAttr($more) . '>';
		$content[] = '<tr>';
		foreach ($cells as $i => $info) {
			$content[] = '<td valign="top" ' . HTMLTag::renderAttr(ifsetor($colMore[$i], [])) . '>';
			$content[] = $this->s($info);
			$content[] = '</td>';
		}

		$content[] = '</tr>';
		$content[] = '</table>';
		return $content;
	}

	/**
	 * Wraps all elements in <div class="column">|</div>
	 * Use HTMLTag to do manual wrapping
	 * @return string
	 */
	public function encloseInTable(...$elements)
	{
		$this->index->addCSS(AutoLoad::getInstance()->nadlibFromDocRoot . 'CSS/columnContainer.less');
		$content = '<div class="columnContainer">';
		foreach ($elements as &$el) {
			if (!$el instanceof HTMLTag) {
				$el = $this->s($el);
				$el = new HTMLTag('div', [
					'class' => 'column',
				], $el, true);
			}
		}

		$content .= implode("\n", $elements);
		return $content . '</div>';
	}

	public function encloseInRow(array $elements = [], array $attrs = [])
	{
		$this->index->addCSS(AutoLoad::getInstance()->nadlibFromDocRoot . 'CSS/columnContainer.less');
		$content = '<div class="columnContainer">';
		foreach ($elements as $i => &$el) {
			if (!$el instanceof HTMLTag) {
				$el = $this->s($el);
				$el = new HTMLTag('div', [
						'class' => 'column',
					] + ifsetor($attrs[$i]), $el, true);
			}
		}

		$content .= implode("\n", $elements);
		return $content . '</div>';
	}

	/**
	 * @return string|string[]|HTMLForm|ToStringable|array<mixed>
	 */
	public function sidebar()
	{
		return '';
	}

	/**
	 * Returns content wrapped in bootstrap .row .col-md-3/4/5 columns
	 * @return string
	 */
	public function inTable(array $parts, array $widths = [])
	{
		$size = count($parts);
		$equal = round(12 / $size);
		$content = '<div class="row">';
		foreach ($parts as $i => $c) {
			$c = $this->s($c);
			$x = ifsetor($widths[$i], $equal);
			$content .= '<div class="col-md-' . $x . '">' . $c . '</div>';
		}

		return $content . '</div>';
	}

	public function attr($s)
	{
		if (is_array($s)) {
			$content = [];
			foreach ($s as $k => $v) {
				$content[] = $k . '="' . $this->attr($v) . '"';
			}

			$content = implode(' ', $content);
		} else {
			$content = htmlspecialchars($s, ENT_QUOTES);
		}

		return $content;
	}

	public function noRender(): void
	{
		$this->noRender = true;
		$this->request->set('ajax', 1);
	}

	public function script($file): string
	{
		$mtime = filemtime($file);
		$file .= '?' . $mtime;
		return '<script src="' . $file . '" type="text/javascript"></script>';
	}

	public function log($action, ...$data): void
	{
		llog($action, ...$data);
//		$this->log[] = new LogEntry($action, $data);
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function getCaptionWithHashLink(string $caption, string $h)
	{
		AutoLoad::getInstance();
		// optional, use it in a project
//		Index::getInstance()->addCSS($al->nadlibFromDocRoot . 'CSS/header-link.less');
		$slug = $this->request->getURL() . URL::friendlyURL($caption);
		$link = '<a class="header-link" href="#' . $slug . '">
				<i class="fa fa-link"></i>
			</a>';
		return '<a name="' . URL::friendlyURL($caption) . '"></a>
			<' . $h . ' id="' . $slug . '">' .
			$link . $caption .
			'</' . $h . '>';
	}

	public function makeNewOf($className, $id)
	{
		return new $className($id, $this->db);
	}

	public function getInstanceOf($className, $id)
	{
		return $className::getInstance($id, $this->db);
	}

	public function setDB(DBInterface $db): void
	{
		$this->db = $db;
	}

	/**
	 * http://stackoverflow.com/questions/19901850/how-do-i-get-an-objects-unqualified-short-class-name
	 * @return string
	 */
	public function self()
	{
		return substr(strrchr(get_class($this), '\\'), 1);
	}

}
