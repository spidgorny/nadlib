<?php

class Search extends Controller
{

	function render()
	{
		$f = new HTMLForm();
		$f->text('Search for project name, game code, service request, etc.:');
		$f->input('s', $this->request->getTrim('s'));
		$f->submit('Search');
		return $f;
	}

}
