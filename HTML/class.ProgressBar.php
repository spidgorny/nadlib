<?php

class ProgressBar {

	var $percentDone = 0;

	var $pbid;
	var $pbarid;
	var $tbarid;
	var $textid;

	var $decimals = 1;

	protected $color = '#43b6df';
	public $cliBR = "\r";

	/**
	 * @var bool
	 */
	var $cli = false;

	/**
	 * Must be false in order to user new ProgressBar(...) inside strings.
	 * @var bool
	 * Destructor will set the progress bar to 100%
	 * if enabled.
	 */
	var $destruct100 = false;

	/**
	 * Should be undefined so that it can be detected once and then stored.
	 * Don't put default value here.
	 * @var int
	 */
	var $cliWidth = NULL;

	/**
	 * If supplied then use $pb->setIndex($i) to calculate percentage automatically
	 * @var int
	 */
	var $count = 0;

    /**
     * Force getCss() to NOT load from Index if Index exists
     * @var bool
     */
    var $useIndexCss = true;

	var $cssFile = 'ProgressBarSimple.less';

	/**
	 * @ param #2 $color = '#43b6df'
	 * @param int $percentDone
	 * @param int $count
	 */
	function __construct($percentDone = 0, $count = 0) {
		$this->setID('pb-'.uniqid());
		$this->pbarid = 'progress-bar';
		$this->tbarid = 'transparent-bar';
		$this->textid = 'pb_text';
		$this->percentDone = max(0, min($percentDone, 100));
		$this->count = $count;
		$this->cli = Request::isCLI();
	}

	/**
	 * AJAX request need to reaccess the main page ProgressBar
	 * @param $pbid
	 */
	public function setID($pbid) {
		$this->pbid = $pbid;
		$this->pbarid = 'progress-bar-'.$pbid;
		$this->tbarid = 'transparent-bar-'.$pbid;
		$this->textid = 'pb_text-'.$pbid;
	}

	function render() {
		if (!$this->cli) {
			ini_set('output_buffering', 0); // php_value output_buffering 0
			if (!headers_sent()) {
				header('Content-type: text/html; charset=utf-8');
			}
			$index = Index::getInstance();
			if (method_exists($index, 'renderHead')) {
				$index->renderHead();
			}
			if (!headers_sent()) {
				echo '<!DOCTYPE html>';
			}
            print($this->getCSS());
			print($this->getContent());
			$this->flush();
		}
	}

	/**
	 * pre-compiles LESS inline
	 * @return string
	 */
	function getCSS() {
		$less = AutoLoad::getInstance()->nadlibFromDocRoot.'CSS/'.$this->cssFile;
		if ($this->useIndexCss && class_exists('Index')) {
			//Index::getInstance()->header['ProgressBar'] = $this->getCSS();
			Index::getInstance()->addCSS($less);
			return ifsetor(Index::getInstance()->header[$less]);
		} elseif (ifsetor($GLOBALS['HTMLHEADER'])) {
			$GLOBALS['HTMLHEADER'][basename($this->cssFile)]
				= '<link rel="stylesheet" href="Lesser?css='.$less.'" />';
		} else if (class_exists('lessc')) {
			$l = new lessc();
			$css = $l->compileFile($less);
			return '<style>' . $css . '</style>';
		} else {
			return '<style>' . file_get_contents($less) . '</style>';  // wrong, but best we can do
		}
	}

	function __toString() {
		return $this->getContent();
	}

	function getContent() {
		$percentDone = floatval($this->percentDone);
		$percentDone = max(0, min(100, $percentDone));
		$percentDone = number_format($percentDone, $this->decimals, '.', '') .'%';
		//debug($this->percentDone, $percentDone);
		$content = '<div id="'.$this->pbid.'" class="pb_container">
			<div id="'.$this->textid.'" class="'.$this->textid.'">'.
			$percentDone.'</div>
			<div class="pb_bar">
				<div id="'.$this->pbarid.'" class="pb_before"
				style="background-color: '.$this->color.'; width: '.$percentDone.';"></div>
				<div id="'.$this->tbarid.'" class="pb_after"></div>
			</div>
			<div style="clear: both;"></div>
		</div>'."\r\n";
		$content .= $this->getCSS();
		return $content;
	}

	function setProgressBarProgress($percentDone, $text = '') {
		$this->percentDone = $percentDone;
		$text = $text
			?: number_format($this->percentDone, $this->decimals, '.', '').'%';
		if ($this->cli) {
			echo $this->cliBR . $text  . "\t".$this->getCLIbar(); // \r first to preserve errors
		} else {
			$this->setProgressBarJS($percentDone, $text);
		}
	}

	function setIndex($i, $always = false) {
		if ($this->count) {
			$percent = $i / $this->count * 100;
			$every = ceil($this->count / 1000); // 100% * 10 for each 0.1
			if ($every < 1 || !($i % $every) || $always) {
				$this->setProgressBarProgress($percent);
			}
		} else {
			throw new InvalidArgumentException(__CLASS__.'->count is not set');
		}
	}

	static function flush($ob_flush = false) {
		print str_pad('', intval(ini_get('output_buffering')), ' ')."\n";
		if ($ob_flush) {
			ob_end_flush();
		}
		flush();
	}

	function __destruct() {
		if ($this->destruct100) {
			$this->setProgressBarProgress(100);
		}
	}

	static function getImageWithText($p, $css = 'display: inline-block; width: 100%; text-align: center; white-space: nowrap;', $append = '') {
		return new htmlString('<div style="'.$css.'">'.
			number_format($p, 2).'&nbsp;%&nbsp;
			'.self::getImage($p, $append).'
		</div>');
	}

	static function getImage($p, $append = '') {
		$prefix = AutoLoad::getInstance()->nadlibFromDocRoot;
		// absolute URL to work even before <base href> is defined
		$prefix = Request::getInstance()->getLocation() . $prefix;
		return '<img src="'.$prefix.'bar.php?rating='.round($p).$append.'"
		style="vertical-align: middle;"
		title="'.number_format($p, 2).'%"
		width="100"
		height="15" />';
	}

	static function getBar($p, $append = '') {
		$prefix = AutoLoad::getInstance()->nadlibFromDocRoot;
		return $prefix . 'bar.php?rating=' . round($p) . $append;
	}

	static function getBackground($p, $width = '100px') {
		$prefix = AutoLoad::getInstance()->nadlibFromDocRoot;
		return '<div style="
			display: inline-block;
			width: '.$width.';
			text-align: center;
			wrap: nowrap;
			background: url('.$prefix.'bar.php?rating='.round($p).'&height=14&width='.intval($width).') no-repeat;">'.number_format($p, 2).'%</div>';
	}

	public function setTitle() {
		print '
		<script>
			document.title = "'.number_format($this->percentDone, 3, '.', '').'%";
		</script>';
	}

	public function hide() {
		echo '<script>
			var el = document.getElementById("'.$this->pbid.'");
			el.parentNode.removeChild(el);
		</script>';
	}

	function getCLIbar() {
		$content = '';
		if (!$this->cliWidth) {
			$this->cliWidth = intval(round($this->getTerminalWidth() / 2));
		}
		if ($this->cliWidth > 0) {  // otherwise cronjob
			$chars = round(abs($this->percentDone) / 100 * $this->cliWidth);
			$chars = min($this->cliWidth, $chars);
			$space = max(0, $this->cliWidth - $chars);
			$content = '['.str_repeat('#', $chars).str_repeat(' ', $space).']';
		}
		return $content;
	}

	function getTerminalWidth() {
		if (Request::isWindows()) {
			$both = $this->getTerminalSizeOnWindows();
			$width = $both['width'];
		} else if (!Request::isCron()) {
			$both = $this->getTerminalSizeOnLinux();
			$width = $both['width'];
		} else {
			$width = -1;        // cronjob
		}
		return $width;
	}

	/**
	 * http://stackoverflow.com/questions/263890/how-do-i-find-the-width-height-of-a-terminal-window
	 * @return array
	 */
	function getTerminalSizeOnWindows() {
		$output = array();
		$size = array('width'=>0,'height'=>0);
		exec('mode',$output);
		foreach ($output as $line) {
			$matches = array();
			$w = preg_match('/^\s*columns\:?\s*(\d+)\s*$/i',$line,$matches);
			if($w) {
				$size['width'] = intval($matches[1]);
			} else {
				$h = preg_match('/^\s*lines\:?\s*(\d+)\s*$/i',$line,$matches);
				if($h) {
					$size['height'] = intval($matches[1]);
				}
			}
			if($size['width'] AND $size['height']) {
				break;
			}
		}
		return $size;
	}

	function getTerminalSizeOnLinux() {
		$size = array_combine(
			array('width', 'height'),
			array(exec('tput cols'), exec('tput lines'))
		);
		return $size;
	}

	public function startSSE($url) {
		Index::getInstance()->addJS(AutoLoad::getInstance()->nadlibFromDocRoot.'js/sse.js');
		return '<div class="sse" id="sseTarget"
		href="'.htmlspecialchars($url).'">'.$this->getContent().'</div>';
	}

	public function setIndexSSE($index) {
		if (!headers_sent()) {
			if (true || Request::getInstance()->isAjax()) {
				header('Content-Type: text/event-stream');
				header('Cache-Control: no-cache');
			} else {    // debug
				header('Content-Type: text/plain');
				header('Cache-Control: no-cache');
			}
		}
		//echo 'event: status', "\n\n";
		echo 'data: '.json_encode(array(
				'current' => $index,
				'total' => $this->count,
		)), "\n\n";
		if (ob_get_status()) {
			ob_end_flush();
		}
		flush();
	}

	function done($content) {
		echo 'data: ', json_encode(array('complete' => $content)), "\n\n";
	}

	/**
	 * @param $percentDone
	 * @param $text
	 */
	private function setProgressBarJS($percentDone, $text) {
		print('
			<script type="text/javascript">
			if (document.getElementById("' . $this->pbarid . '")) {
				document.getElementById("' . $this->pbarid . '").style.width = "' . $percentDone . '%";' . "\n");
		if ($percentDone == 100) {
			print('document.getElementById("' . $this->tbarid . '").style.display = "none";' . "\n");
		} else {
			print('document.getElementById("' . $this->tbarid . '").style.width = "' . (100 - $percentDone) . '%";' . "\n");
		}
		if ($text) {
			print('document.getElementById("' . $this->textid . '").innerHTML = "' . htmlspecialchars(str_replace("\n", '\n', $text)) . '";' . "\n");
		}
		print('}</script>' . "\n");
		$this->flush();
	}

}
