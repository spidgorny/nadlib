<?php

class Bytes
{

	public $value;

	public $suffix = [
		'b' => 'b',
		'k' => 'kb',
		'm' => 'mb',
		'g' => 'gb',
		't' => 'tb',
		'p' => 'pb',
	];

	public $precision = 3;

	public static function create($bytes): self
	{
		return new self($bytes);
	}

	public static function fromString(string $size): self
	{
		return new self(self::return_bytes($size));
	}

	/**
	 * Bytes constructor.
	 * @param string|int $bytes 10MB, 1.5GB
	 */
	public function __construct($bytes)
	{
		$iBytes = (string)(float)$bytes;
		$sBytes = $bytes;
		//echo $bytes, TAB, $iBytes, TAB, $sBytes, BR, $sBytes === $iBytes, BR;
		$this->value = $sBytes === $iBytes ? $bytes : $this->return_bytes($bytes);
	}

	/**
     * http://stackoverflow.com/a/1336624
     * @param string $val
     */
    public static function return_bytes($val): string|int
	{
		$val = trim($val);
		if (strlen($val) !== 0) {
			$last = strtolower($val[strlen($val) - 1]);
			$val = intval($val);
			switch ($last) {
				// The 'G' modifier is available since PHP 5.1.0
				case 'g':
					$val *= 1024 * 1024 * 1024;
					break;
				case 'm':
					$val *= 1024 * 1024;
					break;
				case 'k':
					$val *= 1024;
					break;
			}
		}

		return $val;
	}

	public function __toString(): string
	{
		return $this->renderDynamic();
	}

	public function renderDynamic(): string
	{
		if ($this->value < 1024) {
			return $this->value . $this->suffix['b'];
		} elseif ($this->value > 1024 * 1024 * 1024) {
			return round($this->value / 1024 / 1024 / 1024, $this->precision) . $this->suffix['g'];
		} elseif ($this->value > 1024 * 1024) {
			return round($this->value / 1024 / 1024, $this->precision) . $this->suffix['m'];
		} elseif ($this->value > 1024) {
			return round($this->value / 1024, $this->precision) . $this->suffix['k'];
		}

		return '?';
	}

	public function getBytes()
	{
		return $this->value;
	}

	public function getKB(): int|string
	{
		$val = $this->value / 1024;
		if (is_int($val)) {
			return $val;
		}

		return number_format($val, 3, '.', '');
	}

	public function getMB(): int|string
	{
		$val = $this->value / 1024 / 1024;
		if (is_int($val)) {
			return $val;
		}

		return number_format($val, 3, '.', '');
	}

	public function getGB(): int|string
	{
		$val = $this->value / 1024 / 1024 / 1024;
		if (is_int($val)) {
			return $val;
		}

		return number_format($val, 3, '.', '');
	}

}
