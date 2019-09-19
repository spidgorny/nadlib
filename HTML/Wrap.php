<?php

class Wrap
{
	protected $wrap1, $wrap2;

	function __construct($strWrap, $arrWrap2 = NULL)
	{
		if ($arrWrap2) {
			$this->wrap1 = $strWrap;
			$this->wrap2 = $arrWrap2;
		} else {
			$parts = explode('|', $strWrap);
			if (sizeof($parts) == 2) {
				list($this->wrap1, $this->wrap2) = $parts;
			} else {
				throw new InvalidArgumentException(__METHOD__);
			}
		}
	}

	function __toString()
	{
		return 'Wrap Object (' . strlen($this->wrap1) . ', ' . strlen($this->wrap2) . ')';
	}

	function debug()
	{
		return $this->__toString();
	}

	function wrap($str)
	{
		$str = MergedContent::mergeStringArrayRecursive($str);
		return $this->wrap1 . $str . $this->wrap2;
	}

	/**
	 * @param $w1
	 * @param $w2
	 * @return Wrap
	 */
	static function make($w1, $w2 = NULL)
	{
		return new self($w1, $w2);
	}

}
