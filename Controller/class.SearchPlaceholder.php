<?php

class SearchPlaceholder extends AppController {

	var $ajaxLinks = array(
		'?class=SearchPlaceholder&action=sleep&time=1',
		'?class=SearchPlaceholder&action=sleep&time=2',
		'?class=SearchPlaceholder&action=sleep&time=3',
		'?class=SearchPlaceholder&action=sleep&time=4',
		'?class=SearchPlaceholder&action=sleep&time=5',
		'?class=SearchPlaceholder&action=sleep&time=6',
		'?class=SearchPlaceholder&action=sleep&time=7',
		'?class=SearchPlaceholder&action=sleep&time=8',
		'?class=SearchPlaceholder&action=sleep&time=9',
		'?class=SearchPlaceholder&action=sleep&time=10',
	);

	function render() {
		$content = $this->performAction();
		if (!$content) {
			$content .= $this->renderProgressBar();
		}
		return $content;
	}

	function renderProgressBar() {
		$pb = new ProgressBar();
		$pb->destruct100 = false;
		$content = $pb->getContent();

		foreach ($this->ajaxLinks as &$link) {
			$link .= '&pbid='.$pb->pbid;
		}

		$content .= '<script> var ajaxLinks = '.json_encode($this->ajaxLinks).'; </script>';
		$content .= '<div id="SearchPlaceholder"></div>';
		$this->index->addJQuery();
		$this->index->footer[] = '<script> jQuery.noConflict(); </script>';
		$this->index->addJS('vendor/spidgorny/nadlib/js/SearchPlaceholder.js');
		return $content;
	}

	function sleepAction() {
		$content = 'asd';
		sleep(1);

		$time = $this->request->getInt('time');
		$pb = new ProgressBar();
		$pb->destruct100 = false;
		$pbid = $this->request->getTrim('pbid');
		$pb->setID($pbid);

		$pb->setProgressBarProgress(100 * $time/sizeof($this->ajaxLinks));
		if ($time == sizeof($this->ajaxLinks)) {
			$pb->hide();
		}

		return $content;
	}

}
