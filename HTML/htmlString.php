<?php

/**
 * Use $content instanceof htmlString ? $content : htmlspecialchars($content);
 * Update: use htmlString:hsc($content)
 */
class htmlString
{

	protected $value = '';

	function __construct($input)
	{
		if (is_array($input)) {
			$input = implode(PHP_EOL, $input);
		}
		$this->value = $input . '';
	}

	function __toString()
	{
		return $this->value . '';
	}

	/**
	 * htmlspecialchars which knows about htmlString()
	 * @param $string
	 * @return string
	 */
	static function hsc($string)
	{
		if ($string instanceof htmlString) {
			return $string;
		} else {
			return htmlspecialchars($string);
		}
	}

	function replace($one, $two)
	{
		$new = new htmlString(
			str_replace($one, $two, $this->value));
		return $new;
	}

	function render()
	{
		return $this->__toString();
	}

}
