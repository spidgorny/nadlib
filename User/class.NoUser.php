<?php

/**
 * Some places require a user object which does nothing if you're not logged-in
 */

class NoUser extends UserBase
{

	function __construct()
	{
		parent::__construct(NULL);
	}

	function can()
	{
		return false;
	}

	function renderMessages()
	{
		return '';
	}

	function getPref($key)
	{
		return NULL;
	}

	function setPref($key, $val)
	{
		return NULL;
	}

	function getUnreadMessages()
	{
		return 0;
	}

}
