<?php

class Cookies extends AppControllerBE {

	function render() {
		$this->performAction();
		$content = '';
		foreach ($_COOKIE as $key => $val) {
			$content .= '<h4>'.$key.' <a href="?c=Cookies&action=del&del='.$key.'">&times;</a></h3>'.getDebug($val);
		}
		return $content;
	}

	function delAction() {
		$del = $this->request->getTrim('del');
		unset($_COOKIE[$del]);
		$this->request->redirect('?c='.$this->request->getRefererController());
	}

	function sidebar() {
		$f = new HTMLFormTable([
			'key' => [
				'label' => 'Cookie name',
			],
			'val' => [
				'label' => 'Value',
			],
			'action' => [
				'type' => 'hidden',
				'value' => 'add',
			],
			'submit' => [
				'type' => 'submit',
				'value' => 'Create cookie',
			]
		]);
		$f->defaultBR = true;
		return $f;
	}

	function addAction() {
		$key = $this->request->getTrim('key');
		$val = $this->request->getTrim('val');
		setcookie($key, $val, time()+365*24*60*60, '/');
		$_COOKIE[$key] = $val;
	}

}
