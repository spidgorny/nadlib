<?php

trait FieldAccessTrait
{

	public $id;

	public $data = [];

	protected $titleColumn = 'name';

	public function getObjectInfo(): string
	{
		return get_class($this) . ': "' . $this->getName() . '" (id:' . $this->id . ' ' . $this->getHash() . ')';
	}

	public function getName()
	{
		if (is_array($this->titleColumn)) {
			return array_reduce($this->titleColumn, function (?string $initial, $key): string {
				return ($initial
						? $initial . ' - '
						: '')
					. ifsetor($this->data[$key]);
			}, '');
		}

		return ifsetor($this->data[$this->titleColumn], $this->id);
	}

	public function getHash($length = null): string
	{
		$hash = spl_object_hash($this);
		if ($length) {
			$hash = sha1($hash);
			$hash = substr($hash, 0, $length);
		}

		return '#' . $hash;
	}

	public function getJson(): array
	{
		return [
			'class' => get_class($this),
			'data' => $this->data,
		];
	}

	public function getNameLink(): string|\ToStringable
	{
		return new HTMLTag('a', [
			'href' => $this->getSingleLink(),
		], $this->getName());
	}

	public function getSingleLink(): string
	{
		return get_class($this) . '/' . $this->id;
	}

	public function getBool($key)
	{
		$value = $this->data[$key] ?? null;
		//debug($value, $this->lastSelectQuery);
		if (is_bool($value)) {
			return $value;
		}

		if (is_int($value)) {
			return $value !== 0;
		}

		if (is_numeric($value)) {
			return intval($value) !== 0;
		}

		if (is_string($value)) {
			return $value && $value[0] === 't';
		}

//		throw new InvalidArgumentException(__METHOD__.' ['.$value.']');
		return false;
	}

	public function oid(): string
	{
		return get_class($this) . '-' . $this->getID() . '-' . substr(md5($this->hash()), 0, 8);
	}

	public function getID(): int
	{
		return (int)$this->id;
	}

	public function hash(): string
	{
		return spl_object_hash($this);
	}

	/**
     * @param $name
     * @throws ReflectionException
     */
    public function getVarType($name): string
	{
		$r = new ReflectionClass($this);
		$p = $r->getProperty($name);
		$modifiers = $p->getModifiers();
		$aModStr = Reflection::getModifierNames($modifiers);
		$content = '@' . implode(' @', $aModStr);
		$content .= ' ' . gettype($this->$name);
		switch (gettype($this->$name)) {
			case 'array':
				$content .= '[' . count($this->$name) . ']';
				break;
			case 'object':
				$content .= ' ' . get_class($this->$name);
				break;
		}

		return $content;
	}

	public function offsetExists($offset): bool
	{
		return isset($this->data[$offset]);
	}

	public function offsetGet($offset): mixed
	{
		return $this->get($offset);
	}

	public function get($name)
	{
		return ifsetor($this->data[$name]);
	}

	public function offsetSet($offset, $value): void
	{
		$this->data[$offset] = $value;
	}

	public function offsetUnset($offset): void
	{
		unset($this->data[$offset]);
	}

}
