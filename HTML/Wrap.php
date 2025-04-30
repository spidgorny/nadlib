<?php

class Wrap
{
	protected $wrap1;

	protected $wrap2;

	public function __construct($strWrap, $arrWrap2 = null)
	{
		if ($arrWrap2) {
			$this->wrap1 = $strWrap;
			$this->wrap2 = $arrWrap2;
		} else {
			$parts = explode('|', $strWrap);
			if (count($parts) == 2) {
				list($this->wrap1, $this->wrap2) = $parts;
			} else {
				throw new InvalidArgumentException(__METHOD__);
			}
		}
	}

	public function __toString(): string
	{
		return 'Wrap Object (' . strlen($this->wrap1) . ', ' . strlen($this->wrap2) . ')';
	}

	public function debug(): string
	{
		return $this->__toString();
	}

	public function wrap($str): string
	{
		$str = MergedContent::mergeStringArrayRecursive($str);
		return $this->wrap1 . $str . $this->wrap2;
	}

	/**
	 * @param $w1
	 * @param $w2
	 */
	public static function make($w1, $w2 = null): self
	{
		return new self($w1, $w2);
	}

}
