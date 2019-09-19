<?php

class ErrorPage extends AppController
{

	function render()
	{
		debug($_REQUEST);
	}

}
