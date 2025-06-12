<?php

namespace nadlib;

/**
 * Interface IndexInterface
 * @package nadlib
 */
interface IndexInterface
{

	public static function getInstance();

	public static function makeInstance();

	public function render();

	public function addJS($source);

	public function addCSS($source);

	public function message($text);

	public function error($text);

	public function success($text);

	public function addJQuery();

	public function addJQueryUI();

}
