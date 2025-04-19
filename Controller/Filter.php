<?php

namespace nadlib\Controller;


use ArrayIterator;
use ArrayObject;
use Iterator;
use Traversable;

class Filter extends ArrayObject
{

	protected $_set = [];

	protected $_request = [];

	protected $_preferences = [];

	protected $_default = [];

	public function __construct(array $input = [])
	{
		parent::__construct($input, ArrayObject::ARRAY_AS_PROPS);
		$this->setRequest($input);
	}

	public function setPreferences(array $_preferences = null): void
	{
		if ($_preferences) {
			$this->_preferences = $_preferences;
		}
	}

	public function setRequest(array $_request): void
	{
		$this->_request = $_request;
	}

	public function setDefault(array $_default): void
	{
		$this->_default = $_default;
	}

	public function get($index): mixed
	{
		return $this->offsetGet($index);
	}

	public function getArray($index): array
	{
		$value = $this->offsetGet($index);
		return (array)$value;
	}

	public function set($index, $newval): void
	{
		$this->offsetSet($index, $newval);
	}

	public function offsetSet($index, $newval): void
	{
//		debug(__METHOD__, $index, $newval);
		$this->_set[$index] = $newval;
	}

	public function offsetGet($index): mixed
	{
		if (isset($this->_set[$index])) {
			return $this->_set[$index];
		}

		if (isset($this->_request[$index])) {
			return $this->_request[$index];
		}

		if (isset($this->_preferences[$index])) {
			return $this->_preferences[$index];
		}

		if (isset($this->_default[$index])) {
			return $this->_default[$index];
		}
        
		return null;
	}

	public function offsetExists($index): bool
	{
		return $this->offsetGet($index) != '';
	}

	public function getArrayCopy(): array
	{
		// first array has priority (only append new)
		return $this->_set +
			$this->_request +
			$this->_preferences +
			$this->_default;
	}

	public function getIterator(): Iterator
	{
		return new ArrayIterator($this->getArrayCopy());
	}

	public function clear(): void
	{
		$this->_set = [];
		$this->_request = [];
		$this->_preferences = [];
		$this->_default = [];    // maybe it should remain?
	}

	public function getDebug(): array
	{
		return [
			'set' => $this->_set,
			'request' => $this->_request,
			'preferences' => $this->_preferences,
			'default' => $this->_default,
		];
	}

	public function __debugInfo(): array
	{
		return $this->getDebug();
	}

	public function ensure($field, array $allowedOptions, $default = null): void
	{
		$value = $this[$field];
		if ($value) {
			if (!ifsetor($allowedOptions[$value])) {
				$default = $default ?: first(array_keys($allowedOptions));
				$this->set($field, $default);
			}
		} else {
			// if it's not set then fill default anyway
			$default = $default ?: first(array_keys($allowedOptions));
			$this->set($field, $default);
		}
	}

}
