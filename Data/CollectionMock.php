<?php

/**
 * Class IteratorMemberCollection
 * @deprecated
 * Fatal error: Class IteratorCollection cannot implement both Iterator and IteratorAggregate at the same time
 */
class CollectionMock
{

	var $members;

	public function __construct($pid = null, $where = array(), $order = '')
	{
	}

	public function objectify()
	{
	}

}
