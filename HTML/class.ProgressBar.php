<?php

class ProgressBar {
	var $percentDone = 0;
	var $pbid;
	var $pbarid;
	var $tbarid;
	var $textid;
	var $decimals = 1;
	var $cli = false;
	var $destruct100 = true;

	function __construct($percentDone = 0) {
		$this->pbid = 'pb-'.uniqid();
		$this->pbarid = 'progress-bar-'.$this->pbid;
		$this->tbarid = 'transparent-bar-'.$this->pbid;
		$this->textid = 'pb_text-'.$this->pbid;
		$this->percentDone = $percentDone;
		$this->cli = Request::isCLI();
	}

	function render() {
		if (!$this->cli) {
			print($this->getContent());
			$l = new lessc();
			$css = $l->compileFile(dirname(__FILE__).'/../CSS/ProgressBar.less');
			print '<style>'.$css.'</style>';
			$this->flush();
		}
	}

	function __toString() {
		return $this->getContent();
	}

	function getContent() {
		$this->percentDone = floatval($this->percentDone);
		$percentDone = number_format($this->percentDone, $this->decimals, '.', '') .'%';
		$content = '<div id="'.$this->pbid.'" class="pb_container">
			<div id="'.$this->textid.'" class="'.$this->textid.'">'.$percentDone.'</div>
			<div class="pb_bar">
				<div id="'.$this->pbarid.'" class="pb_before"
				style="width: '.$percentDone.';"></div>
				<div id="'.$this->tbarid.'" class="pb_after"></div>
			</div>
			<div style="clear: both;"></div>
		</div>'."\r\n";
		if (class_exists('Index')) {
			Index::getInstance()->addCSS('nadlib/CSS/ProgressBar.less');
		} else {
			$content .= '<link rel="stylesheet" href="nadlib/CSS/ProgressBar.less" />';
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
				document.getElementById("'.$this->pbarid.'").style.width = "'.$percentDone.'%";');
			if ($percentDone == 100) {
				print('document.getElementById("'.$this->tbarid.'").style.display = "none";');
			} else {
				print('document.getElementById("'.$this->tbarid.'").style.width = "'.(100-$percentDone).'%";');
			}
			if ($text) {
				print('document.getElementById("'.$this->textid.'").innerHTML = "'.htmlspecialchars(str_replace("\n", '\n', $text)).'";');
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

	function getImage($p) {
		return '<div style="display: inline-block; width: 100%; text-align: center; wrap: nowrap;">'.
			number_format($p, $this->decimals).'&nbsp;%&nbsp;
			<img src="nadlib/bar.php?rating='.round($p).'" style="vertical-align: middle;" />
		</div>';
	}

	function getBackground($p, $width = '100px') {
		return '<div style="
			display: inline-block;
			width: '.$width.';
			text-align: center;
			wrap: nowrap;
			background: url(nadlib/bar.php?rating='.round($p).'&height=14&width='.intval($width).') no-repeat;">'.number_format($p, $this->decimals).'%</div>';
	}

	public function setTitle() {
		print '
		<script>
			document.title = "'.number_format($this->percentDone, 3, '.', '').'%";
		</script>';
	}

}
