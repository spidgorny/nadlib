<?php

class PHPInfo extends AppControllerBE
{

	public $layout = 'none';

	function render()
	{
		ob_start();
		phpinfo();
		$content = ob_get_clean();
		$content = preg_replace('/<style type="text\/css">(.*)<\/style>/s', '', $content);
		return $content;
	}

}
