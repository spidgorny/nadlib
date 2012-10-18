<?php

class Home extends Controller {

	function render() {
		$content = '';

		$f = new HTMLForm();
		$f->text('Search for project name, game code, service request, etc.:');
		$f->input('s', $this->request->getTrim('s'));
		$f->submit('Search');
		$content .= $f;

		$content .= new Markdown('Home.text');

		return $content;
	}

}
