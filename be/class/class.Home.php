<?php

class Home extends AppController {

	function render() {
		$content = '';
		$content .= new Markdown('Home.text');

		return $content;
	}

}
