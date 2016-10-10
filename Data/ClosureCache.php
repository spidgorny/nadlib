<?php

class ClosureCache {

	static $closures;

	/**
	 * @var callable
	 */
	var $function;

	var $result;

	protected function __construct(callable $function) {
		$this->function = $function;
	}

	function get() {
		if (!$this->result) {
			$this->result = call_user_func($this->function);
		}
		return $this->result;
	}

	function __toString() {
		return $this->get().'';
	}

	function __invoke() {
		return $this->get();
	}

	static function getInstance($key, callable $function) {
		$hash = md5(json_encode($key));
		if (isset(self::$closures[$hash])) {
			return self::$closures[$hash];
		} else {
			$new = new self($function);
			self::$closures[$hash] = $new;
			return $new;
		}
	}

}
