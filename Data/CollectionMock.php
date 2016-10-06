<?php

/**
 * Class IteratorMemberCollection
 * @deprecated
 * Fatal error: Class IteratorCollection cannot implement both Iterator and IteratorAggregate at the same time
 */
class CollectionMock {

	var $members;

	function __construct($pid = NULL, /*array/SQLWhere*/ $where = array(), $order = '') {

	}

	function objectify() {

	}

}
