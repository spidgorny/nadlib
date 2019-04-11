<?php

namespace nadlib;

/**
 * Interface IndexInterface
 * @package nadlib
 * @mixin
 */
interface IndexInterface
{

	static function getInstance($createNew = false);

	function render();

	function addJS($source);

	function message($text);
	function error($text);
	function success($text);

}
