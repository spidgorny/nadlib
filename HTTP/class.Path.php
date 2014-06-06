<?php

class Path {

	var $sPath;

	var $aPath;

	function __construct($sPath) {
		$this->sPath = $sPath;
		$this->explode();
		$this->implode();   // to prevent '//'
	}

	function explode() {
		$forwardSlash = str_replace('\\', '/', $this->sPath);
		$this->aPath = trimExplode('/', $forwardSlash);
	}

	function implode() {
		$this->sPath = implode('/', $this->aPath);
	}

	function __toString() {
		return $this->getCapped();
	}

	/**
	 * @param $dirname
	 * @return bool
	 */
	function contains($dirname) {
		return in_array($dirname, $this->aPath);
	}

	function append(Path $plus) {
		$this->aPath += $plus->aPath;
		$this->implode();
	}

	function trim() {
		array_pop($this->aPath);
		$this->implode();
	}

	function trimIf($dirname) {
		if (end($this->aPath) == $dirname) {
			$this->trim();
		}
	}

	function getUncapped() {
		return $this->sPath;
	}

	function getCapped() {
		return $this->sPath.'/';
	}

}
