<?php

class NADLib
{

	/**
	 * @return string first 7 chars of md5(spl_object_hash())
	 * @param object $o
	 */
	public static function hash($o)
	{
		return substr(md5(spl_object_hash($o)), 0, 6);
	}

}
