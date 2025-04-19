<?php

class NADLib
{

	/**
	 * @param object $o
	 * @return string first 7 chars of md5(spl_object_hash())
	 */
	public static function hash($o): string
	{
		return substr(md5(spl_object_hash($o)), 0, 6);
	}

}
