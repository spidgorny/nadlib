<?php

class Session extends AppControllerBE {

	function __construct() {
		parent::__construct();
		ksort($_SESSION);
		//debug(AutoLoad::getInstance()->debug());
		$this->index->addJS(AutoLoad::getInstance()->nadlibFromDocRoot.'js/keepScrollPosition.js');
	}

	function render() {
		$this->performAction();
		$content = '';
		foreach ($_SESSION as $key => $val) {
			$content .= '<h4>
				<a name="'.$key.'">
					'.$key.'
				</a>
				<a href="'.$this->getURL(array(
					'c' => 'Session',
					'action' => 'del',
					'del' => $key,
				)).'">&times;</a>
			</h4>'.
			getDebug($val);
		}
		return $content;
	}

	function delAction() {
		$del = $this->request->getTrim('del');
		unset($_SESSION[$del]);
		$this->index->message('Deleted '.$del);
		$this->index->saveMessages();
		$this->request->redirect('?c='.$this->request->getRefererController());
	}

	function sidebar() {
		$keys = array_keys($_SESSION);
		foreach ($keys as &$key) {
			$key = '<a href="#'.$key.'">'.$key.'</a>';
		}
		$content = implode('<br />', $keys);
		return $content;
	}

}
