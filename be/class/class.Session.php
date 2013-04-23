<?php

class Session extends AppControllerBE {

	function render() {
		$this->performAction();
		$content = '';
		foreach ($_SESSION as $key => $val) {
			$content .= '<h4>'.$key.' <a href="?c=Session&action=del&del='.$key.'">&times;</a></h3>'.getDebug($val);
		}
		return $content;
	}

	function delAction() {
		$del = $this->request->getTrim('del');
		unset($_SESSION[$del]);
		$this->request->redirect('?c='.$this->request->getRefererController());
	}

}
