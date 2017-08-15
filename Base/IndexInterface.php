<?php

namespace nadlib;

interface IndexInterface {

	static function getInstance($createNew = false);

	function render();

	function addJS($source);

}
