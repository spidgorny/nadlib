<?php

/**
 * Class ACL
 * Convert your A && B && C && D into
$canMakeSystem = ACL::make(
	$column == 'description',
	$isTranslator,
	$this->canAddNotes(),
	$translatorOfThatLanguage
)->getAND();
 * Then you can debug this conditions
 */
class ACL {

	/**
	 * @var boolean[]
	 */
	var $andConditions = array();

	/**
	 * @var ReflectionParameter[]
	 */
	var $strConditions = array();

	/**
	 * @var array
	 */
	var $callStack = array();

	/**
	 * Corresponding source code
	 * @var array
	 */
	var $source = array();

	function __construct(array $params) {
		$this->andConditions = $params;

		$refFunc = new ReflectionMethod($this, __FUNCTION__);
		$this->strConditions = $refFunc->getParameters();

		//$this->callStack = get_call_stack();    // only Zend Debugger
		//debug($this->callStack);

		$bt = debug_backtrace(false);
		$this->callStack = $bt[1];
		$this->callStack['function'] = $bt[2]['function'];
		//debug($this->callStack);

		$source = file($this->callStack['file']);
		for ($i = -sizeof($this->andConditions)-1; $i < -1; $i++) {
			$this->source[] = trim($source[$this->callStack['line']+$i]);
		}
	}

	/**
	 * @return ACL
	 */
	static function make() {
		$params = func_get_args();
		$acl = new ACL($params);
		return $acl;
	}

	function getAND() {
		if ($_REQUEST['acl']) {
			$this->debug();
		}
		$and = true;
		foreach ($this->andConditions as $cond) {
			$and = $and && $cond;
		}
		return $and;
	}

	function debug() {
		$table = array();
		foreach ($this->andConditions as $i => $_) {
			$table[] = array(
				'source' => $this->source[$i],
				'value' => $this->andConditions[$i],
			);
		}
		echo '<div style="background: #EEEEEE; border: solid 1px silver;">',
 			'<div style="background: silver;">', basename($this->callStack['file']),
			'#'.$this->callStack['line'],
			' ', $this->callStack['function'], '()</div>',
			new slTable($table),
		'</div>';
	}

}
