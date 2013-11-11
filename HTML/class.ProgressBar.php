<?php

class ProgressBar {
	var $percentDone = 0;
	var $pbid;
	var $pbarid;
	var $tbarid;
	var $textid;
	var $decimals = 1;
	protected $color = '#43b6df';
	var $cli = false;
	var $destruct100 = true;

	function __construct($percentDone = 0, $color = '43b6df') {
		$this->setID('pb-'.uniqid());
		$this->pbarid = 'progress-bar';
		$this->tbarid = 'transparent-bar';
		$this->textid = 'pb_text';
		$this->percentDone = $percentDone;
		$this->color = $color;
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
			if (!headers_sent()) {
				header('Content-type: text/html; charset=utf-8');
			}
			print($this->getContent());
			print $this->getCSS();
			$this->flush();
		}
	}

	function getCSS() {
		$l = new lessc();
		$css = $l->compileFile(dirname(__FILE__).'/../CSS/ProgressBar.less');
		return '<style>'.$css.'</style>';
	}

	function __toString() {
		return $this->getContent();
	}

	function getContent() {
		Index::getInstance()->header['ProgressBar'] = $this->getCSS();
		$this->percentDone = floatval($this->percentDone);
		$percentDone = number_format($this->percentDone, $this->decimals, '.', '') .'%';
		$content = '<div id="'.$this->pbid.'" class="pb_container">
			<div id="'.$this->textid.'" class="'.$this->textid.'">'.$percentDone.'</div>
			<div class="pb_bar">
				<div id="'.$this->pbarid.'" class="pb_before"
				style="background-color: '.$this->color.'; width: '.$percentDone.';"></div>
				<div id="'.$this->tbarid.'" class="pb_after"></div>
			</div>
			<div style="clear: both;"></div>
		</div>'."\r\n";
		if (class_exists('Index')) {
			Index::getInstance()->addCSS('vendor/spidgorny/nadlib/CSS/ProgressBar.less');
		} elseif ($GLOBALS['HTMLHEADER']) {
			$GLOBALS['HTMLHEADER']['ProgressBar.less']
				= '<link rel="stylesheet" href="vendor/spidgorny/nadlib/CSS/ProgressBar.less" />';
		} else {
			$content .= '<link rel="stylesheet" href="vendor/spidgorny/nadlib/CSS/ProgressBar.less" />';
		}
		return $content;
	}

	function setProgressBarProgress($percentDone, $text = '') {
		$this->percentDone = $percentDone;
		$text = $text ? $text : number_format($this->percentDone, $this->decimals, '.', '').'%';
		if ($this->cli) {
			echo ($text ? $text : $percentDone)."\n";
		} else {
			print('
			<script type="text/javascript">
			if (document.getElementById("'.$this->pbarid.'")) {
				document.getElementById("'.$this->pbarid.'").style.width = "'.$percentDone.'%";'."\n");
			if ($percentDone == 100) {
				print('document.getElementById("'.$this->tbarid.'").style.display = "none";'."\n");
			} else {
				print('document.getElementById("'.$this->tbarid.'").style.width = "'.(100-$percentDone).'%";'."\n");
			}
			if ($text) {
				print('document.getElementById("'.$this->textid.'").innerHTML = "'.htmlspecialchars(str_replace("\n", '\n', $text)).'";'."\n");
			}
			print('}</script>'."\n");
			$this->flush();
		}
	}

	function flush($ob_flush = false) {
		print str_pad('', intval(ini_get('output_buffering')))."\n";
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

	function getImage($p, $display = 'inline-block') {
		$prefix = '';
		if (Index::getInstance() instanceof IndexBE) {
			//$prefix = '../../../../';
			// just use base href instead
		}
		return new htmlString('<div style="display: '.$display.'; width: 100%; text-align: center; white-space: nowrap;">'.
			number_format($p, $this->decimals).'&nbsp;%&nbsp;
			<img src="'.$prefix.'vendor/spidgorny/nadlib/bar.php?rating='.round($p).'" style="vertical-align: middle;" />
		</div>');
	}

	function getBackground($p, $width = '100px') {
		return '<div style="
			display: inline-block;
			width: '.$width.';
			text-align: center;
			wrap: nowrap;
			background: url(vendor/spidgorny/nadlib/bar.php?rating='.round($p).'&height=14&width='.intval($width).') no-repeat;">'.number_format($p, $this->decimals).'%</div>';
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

}
