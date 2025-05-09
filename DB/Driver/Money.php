<?php

class Money
{

	protected float $value;

	public static function fromPostgres($source = '$1,234.56')
	{
		$source = str_replace('$', '', $source);
		$source = str_replace(',', '', $source);
		$float = floatval($source);
		return new Money($float);
	}

	public function __construct(float $value)
	{
		$this->value = $value;
	}

	/**
	 * http://www.postgresql.org/docs/9.3/static/datatype-money.html
	 * @param string $source
	 * @return float
	 */
	public function getFloat()
	{
		return $this->value;
	}

}
