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

	function __construct($percentDone = 0, $color = '43b6df') {
		$this->pbid = 'pb';
		$this->pbarid = 'progress-bar';
		$this->tbarid = 'transparent-bar';
		$this->textid = 'pb_text';
		$this->percentDone = $percentDone;
		$this->color = $color;
		$this->cli = Request::isCLI();
	}

	function render() {
		if (!$this->cli) {
			print($this->getContent());
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
				style="background-color: '.$this->color.'; width: '.$percentDone.';"></div>
				<div id="'.$this->tbarid.'" class="pb_after"></div>
			</div>
			<div style="clear: both;"></div>
		</div>'."\r\n";
		if (class_exists('Index')) {
			Index::getInstance()->addCSS('nadlib/CSS/ProgressBar.less');
		} else {
			$GLOBALS['HTMLHEADER']['nadlib/CSS/ProgressBar.less'] = '<link rel="stylesheet" href="nadlib/CSS/ProgressBar.less" />';
		}
		return $content;
	}

	function setProgressBarProgress($percentDone, $text = '') {
		$this->percentDone = $percentDone;
		$text = $text ? $text : number_format($this->percentDone, $this->decimals, '.', '').'%';
		if ($this->cli) {
			echo $text."\n";
		} else {
			print('
			<script type="text/javascript">
			if (document.getElementById("'.$this->pbarid.'")) {
				document.getElementById("'.$this->pbarid.'").style.width = "'.$percentDone.'%";');
			if ($percentDone == 100) {
				print('document.getElementById("'.$this->pbid.'").style.display = "none";');
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

	function flush() {
		print str_pad('', intval(ini_get('output_buffering')))."\n";
		//ob_end_flush();
		flush();
	}

	function getImage($p) {
		return '<div style="display: inline-block; width: 15em; text-align: right; wrap: nowrap;">'.number_format($p, 2).'&nbsp;%&nbsp;<img src="skin/bar.php?rating='.round($p).'" style="vertical-align: middle;" /></div>';
	}

	public function setTitle() {
		print '
		<script>
			document.title = "'.number_format($this->percentDone, 3, '.', '').'%";
		</script>';
	}

}
