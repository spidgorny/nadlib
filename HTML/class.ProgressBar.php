<?php

class ProgressBar
{
	var $percentDone = 0;
	var $pbid;
	var $pbarid;
	var $tbarid;
	var $textid;
	var $decimals = 1;
	protected $color = '#43b6df';

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
	 * @ param #2 $color = '#43b6df'
	 * @param int $percentDone
	 * @param int $count
	 */
	function __construct($percentDone = 0, $count = 0)
	{
		$this->setID('pb-' . uniqid());
		$this->pbarid = 'progress-bar';
		$this->tbarid = 'transparent-bar';
		$this->textid = 'pb_text';
		$this->percentDone = $percentDone;
		$this->count = $count;
		$this->cli = Request::isCLI();
	}

	/**
	 * AJAX request need to reaccess the main page ProgressBar
	 * @param $pbid
	 */
	public function setID($pbid)
	{
		$this->pbid = $pbid;
		$this->pbarid = 'progress-bar-' . $pbid;
		$this->tbarid = 'transparent-bar-' . $pbid;
		$this->textid = 'pb_text-' . $pbid;
	}

	function render()
	{
		if (!$this->cli) {
			if (!headers_sent()) {
				header('Content-type: text/html; charset=utf-8');
			}
			print($this->getContent());
			print $this->getCSS();
			$this->flush();
		}
	}

	/**
	 * pre-compiles LESS inline
	 * @return string
	 */
	function getCSS()
	{
		$less = AutoLoad::getInstance()->nadlibFromDocRoot . 'CSS/ProgressBar.less';
		if (class_exists('Index')) {
			//Index::getInstance()->header['ProgressBar'] = $this->getCSS();
			Index::getInstance()->addCSS($less);
		} elseif ($GLOBALS['HTMLHEADER']) {
			$GLOBALS['HTMLHEADER']['ProgressBar.less']
				= '<link rel="stylesheet" href="' . $less . '" />';
		} else {
			$l = new lessc();
			$css = $l->compileFile($less);
			return '<style>' . $css . '</style>';
		}
	}

	function __toString()
	{
		return $this->getContent();
	}

	function getContent()
	{
		$this->percentDone = floatval($this->percentDone);
		$percentDone = number_format($this->percentDone, $this->decimals, '.', '') . '%';
		$content = '<div id="' . $this->pbid . '" class="pb_container">
			<div id="' . $this->textid . '" class="' . $this->textid . '">' . $percentDone . '</div>
			<div class="pb_bar">
				<div id="' . $this->pbarid . '" class="pb_before"
				style="background-color: ' . $this->color . '; width: ' . $percentDone . ';"></div>
				<div id="' . $this->tbarid . '" class="pb_after"></div>
			</div>
			<div style="clear: both;"></div>
		</div>' . "\r\n";
		$content .= $this->getCSS();
		return $content;
	}

	function setProgressBarProgress($percentDone, $text = '')
	{
		$this->percentDone = $percentDone;
		$text = $text ? $text : number_format($this->percentDone, $this->decimals, '.', '') . '%';
		if ($this->cli) {
			echo "\r" . $text . "\t" . $this->getCLIbar(); // \r first to preserve errors
		} else {
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

	function setIndex($i, $always = false)
	{
		$percent = $i / $this->count * 100;
		$every = $this->count / 1000;   // 100% * 10 for each 0.1
		if (!($i % $every) || $always) {
			$this->setProgressBarProgress($percent);
		}
	}

	function flush($ob_flush = false)
	{
		print str_pad('', intval(ini_get('output_buffering'))) . "\n";
		if ($ob_flush) {
			ob_end_flush();
		}
		flush();
	}

	function __destruct()
	{
		if ($this->destruct100) {
			$this->setProgressBarProgress(100);
		}
	}

	function getImage($p, $css = 'display: inline-block; width: 100%; text-align: center; white-space: nowrap;')
	{
		$prefix = AutoLoad::getInstance()->nadlibFromDocRoot;
		return new htmlString('<div style="' . $css . '">' .
			number_format($p, $this->decimals) . '&nbsp;%&nbsp;
			<img src="' . $prefix . 'bar.php?rating=' . round($p) . '" style="vertical-align: middle;" />
		</div>');
	}

	function getBackground($p, $width = '100px')
	{
		return '<div style="
			display: inline-block;
			width: ' . $width . ';
			text-align: center;
			wrap: nowrap;
			background: url(vendor/spidgorny/nadlib/bar.php?rating=' . round($p) . '&height=14&width=' . intval($width) . ') no-repeat;">' . number_format($p, $this->decimals) . '%</div>';
	}

	public function setTitle()
	{
		print '
		<script>
			document.title = "' . number_format($this->percentDone, 3, '.', '') . '%";
		</script>';
	}

	public function hide()
	{
		echo '<script>
			var el = document.getElementById("' . $this->pbid . '");
			el.parentNode.removeChild(el);
		</script>';
	}

	function getCLIbar()
	{
		$content = '';
		if (!$this->cliWidth) {
			$this->cliWidth = intval(round($this->getTerminalWidth() / 2));
		}
		if ($this->cliWidth > 0) {  // otherwise cronjob
			$chars = round(abs($this->percentDone) / 100 * $this->cliWidth);
			$chars = min($this->cliWidth, $chars);
			$space = max(0, $this->cliWidth - $chars);
			$content = '[' . str_repeat('#', $chars) . str_repeat(' ', $space) . ']';
		}
		return $content;
	}

	function getTerminalWidth()
	{
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
	function getTerminalSizeOnWindows()
	{
		$output = array();
		$size = array('width' => 0, 'height' => 0);
		exec('mode', $output);
		foreach ($output as $line) {
			$matches = array();
			$w = preg_match('/^\s*columns\:?\s*(\d+)\s*$/i', $line, $matches);
			if ($w) {
				$size['width'] = intval($matches[1]);
			} else {
				$h = preg_match('/^\s*lines\:?\s*(\d+)\s*$/i', $line, $matches);
				if ($h) {
					$size['height'] = intval($matches[1]);
				}
			}
			if ($size['width'] AND $size['height']) {
				break;
			}
		}
		return $size;
	}

	function getTerminalSizeOnLinux()
	{
		$size = array_combine(
			array('width', 'height'),
			array(exec('tput cols'), exec('tput lines'))
		);
		return $size;
	}

}
