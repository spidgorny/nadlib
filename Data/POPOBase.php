<?php

/**
 * Class POPOBase - extends this class and add some properties
 * Then you can instantiate an object of your class and provide some
 * JSON data, it will extract and convert JSON data to POPO
 */
class POPOBase {

	/**
	 * @var \ReflectionClass
	 */
	protected $reflector;

	public $missingProperties = [];

	public function __construct($set)
	{
		$this->reflector = new ReflectionClass($this);
		foreach (get_object_vars($set) as $key => $val) {
			$this->$key = $this->transform($key, $val);
		}
	}

	public function transform($name, $value)
	{
		try {
			$prop = $this->reflector->getProperty($name);
			if ($prop) {
				$docText = $prop->getDocComment();
				$doc = new DocCommentParser($docText);
				$type = $doc->getFirstTagValue('var');
				//debug($docText, $type);
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
						$value = new DateTime($value);
						break;
					default:
						// inner subclasses
						if (class_exists($type)) {
							$value = new $type($value);
						}
				}
			}
		} catch (ReflectionException $e) {
			$this->missingProperties[$name] = TAB . 'public $'.$name.';';
		}
		return $value;
	}

}