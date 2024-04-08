<?php

/**
 * Use $content instanceof htmlString ? $content : htmlspecialchars($content);
 * Update: use htmlString:hsc($content)
 */
class HtmlString implements ToStringable
{

	protected $value = '';

	public function __construct($input)
	{
		if (is_array($input)) {
			$input = implode(PHP_EOL, $input);
		}
		$this->value = $input . '';
	}

	public function __toString()
	{
		return $this->value . '';
	}

	/**
	 * htmlspecialchars which knows about htmlString()
	 * @param string $string
	 * @return string|HtmlString
	 */
	public static function hsc($string)
	{
		if ($string instanceof self) {
			return $string;
		}

		return htmlspecialchars($string);
	}

	public function replace($one, $two)
	{
		$new = new HtmlString(
			str_replace($one, $two, $this->value));
		return $new;
	}

	public function render()
	{
		return $this->__toString();
	}

	public function cli()
	{
		return trim(strip_tags($this->render()));
	}

}
