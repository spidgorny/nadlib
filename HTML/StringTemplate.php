<?php

/**
 * Class StringTemplate - renders PHP template with {$variable} substitution
 */
class StringTemplate {
	public $filename;
	protected $content;
	protected $lines;
	protected $caller;

	function __construct($file, $self = NULL) {
		$this->filename = $file;
		$filepath = 'template/'.$file;
		if (!file_exists($filepath)) {
			throw new Exception($filepath.' does not exist.');
		}
		$this->content = file_get_contents($filepath);
		$this->caller = $self;
	}

	/**
	 * Made in order to extract the subject line from the template
	 *
	 * @param int $line
	 * @return string
	 */
	function getLine($line) {
		$this->lines = explode("\n", $this->content);
		$content = $this->lines[$line];
		unset($this->lines[$line]);
		return $content;
	}

	/**
	 * In relation to the getLine();
	 *
	 * @return string
	 */
	function getRest() {
		return implode("\n", $this->lines);
	}

	function render() {
		return eval("return<<<END\n".$this->content."\nEND;\n"); // space is important
	}

	function  __toString() {
		return $this->render();
	}

	function __call($func, array $args) {
		$method = [$this->caller, $func];
		if (!is_callable($method) || !method_exists($this->caller, $func)) {
			$method = ['Controller', $func];
		}
		return call_user_func_array($method, $args);
	}

	function &__get($var) {
		return $this->caller->$var;
	}

}
