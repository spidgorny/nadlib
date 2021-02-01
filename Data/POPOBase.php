<?php

/**
 * Class POPOBase - extends this class and add some properties
 * Then you can instantiate an object of your class and provide some
 * JSON data, it will extract and convert JSON data to POPO
 */
class POPOBase
{

	/**
	 * @var \ReflectionClass
	 */
	protected $reflector;

	public $_missingProperties = [];

	public function __construct($set)
	{
		$this->reflector = new ReflectionClass($this);
		if (is_object($set)) {
			foreach (get_object_vars($set) as $key => $val) {
				$this->$key = $this->transform($key, $val);
			}
		} elseif (is_array($set)) {
			foreach ($set as $key => $val) {
				$this->$key = $this->transform($key, $val);
			}
		}
	}

	public function transform($name, $value)
	{
		try {
			$prop = $this->reflector->getProperty($name);
			if ($prop) {
				$type = $prop->getType();
				if (!$type) {
					$docText = $prop->getDocComment();
					$doc = new DocCommentParser($docText);
					$type = $doc->getFirstTagValue('var');
//					llog($docText, $type, $value);
				}
				llog($name, $type.'', $value);
				switch ($type) {
					case 'int':
						$value = intval($value);
						break;
					case 'integer':
						$value = intval($value);
						break;
					case 'string':
						$value = (string)($value);
						break;
					case 'bool':
						$value = boolval($value);
						break;
					case 'boolean':
						$value = boolval($value);
						break;
					case 'float':
						$value = floatval($value);
						break;
					case 'DateTime':
					case '\DateTime':
						if (is_object($value)) {
							$value = new DateTime($value->date);
						} elseif ($value) {
							$value = new DateTime($value);
						}
						break;
					case 'DateTimeImmutable':
						if (is_object($value)) {
							$value = new DateTimeImmutable($value->date);
						} elseif ($value) {
							$value = new DateTimeImmutable($value);
						}
						break;
					default:
						// inner subclasses
						if (class_exists($type)) {
							$value = new $type($value);
						}
				}
			}
		} catch (ReflectionException $e) {
			$this->_missingProperties[$name] = TAB . 'public $' . $name . ';';
		}
		return $value;
	}

	public function toJson()
	{
		return json_encode($this, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
	}

}
