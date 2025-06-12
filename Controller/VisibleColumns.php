<?php

class VisibleColumns extends ArrayPlus
{
	
	/**
	 * If there are no data then all columns are visible
	 * @param string $key
	 * @return bool|null
	 */
	public function isVisible($key)
	{
		if ($this->count() === 0) {
			return true;
		}

		return ifsetor($this[$key]);
	}

	public function getData(): array
	{
		$data = parent::getData();
		$onlySet = array_filter($data);
		if ($onlySet === []) {    // all unset
			$data = array_fill_keys(array_keys($data), true); // all set
		}

		return $data;
	}

}
