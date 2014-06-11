<?php

class Path {

	var $sPath;

	var $aPath;

	var $isAbsolute = false;

	function __construct($sPath) {
		$this->sPath = $sPath;
		$this->isAbsolute = startsWith($this->sPath, '/');
		$this->explode();
		$this->implode();   // to prevent '//'
	}

	/**
	 * @param $sPath
	 * @return Path
	 */
	static function make($sPath) {
		$new = new self($sPath);
		return $new;
	}

	/**
	 * Modifies the array path after string modification
	 */
	function explode() {
		$forwardSlash = str_replace('\\', '/', $this->sPath);
		$this->aPath = trimExplode('/', $forwardSlash);
	}

	/**
	 * Modifies the string path after array modification
	 */
	function implode() {
		$this->sPath = ($this->isAbsolute ? '/' : '').
			implode('/', $this->aPath);
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

	/**
	 * @param Path $plus
	 * @return $this
	 */
	function append(Path $plus) {
		$this->aPath = array_merge($this->aPath, $plus->aPath);
		$this->implode();
		return $this;
	}

	/**
	 * @param $plus
	 * @return $this
	 */
	function appendString($plus) {
		$pPlus = new Path($plus);
		$this->append($pPlus);
		return $this;
	}

	/**
	 * @param $plus string|Path
	 * @return bool
	 */
	function appendIfExists($plus) {
		$pPlus = $plus instanceof Path
			? $plus
			: new Path($plus);
		$new = clone $this;
		$new->append($pPlus);
		//debug($pPlus, $new, $new->exists());
		if ($new->exists()) {
			$this->aPath = $new->aPath;
			$this->sPath = $new->sPath;
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	function exists() {
		return is_dir($this->sPath);
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

	/**
	 * @return self
	 */
	public function up() {
		if ($this->aPath) {
			array_pop($this->aPath);
		} else {
			array_push($this->aPath, '..');
		}
		$this->implode();
		return $this;
	}

	/**
	 * @param $that
	 * @return self
	 */
	public function upIf($that) {
		if (end($this->aPath) == $that) {
			return $this->up();
		}
		return $this;
	}

	/**
	 * @param $minus
	 * @return $this
	 */
	function remove($minus) {
		$minus = $minus instanceof Path ? $minus : new Path($minus);
		foreach ($minus->aPath as $i => $sub) {
			if ($this->aPath[0] == $sub) {  // 0 because shift
				array_shift($this->aPath);
				$this->isAbsolute = false;
			} else {
				break;
			}
		}
		$this->implode();
		return $this;
	}

	function reverse() {
		if (!$this->isAbsolute && !empty($this->aPath)) {
            $this->aPath = array_fill(0, sizeof($this->aPath), '..');
			$this->implode();
		}
		return $this;
	}

}
