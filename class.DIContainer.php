<?php

class DIContainer {
  protected $values = array();

  function __set($id, $value)
  {
    $this->values[$id] = $value;
  }

  function __get($id)
  {
    if (!isset($this->values[$id]))
    {
      throw new InvalidArgumentException(sprintf('Value "%s" is not defined.', $id));
    }

	//debug(is_callable($this->values[$id]), is_object($this->values[$id]));
    return is_callable($this->values[$id]) && !is_object($this->values[$id]) ? $this->values[$id]($this) : $this->values[$id];
  }

function asShared($callable)
  {
    return function ($c) use ($callable)
    {
      static $object;

      if (is_null($object))
      {
        $object = $callable($c);
      }

      return $object;
    };
  }

}
