<?php

class HomeBE extends AppControllerBE {

	function render() {
		$content = '';
		$content .= new Markdown('Home.text');

		return $content;
	}

}
