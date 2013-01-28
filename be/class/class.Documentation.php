<?php

class Documentation extends AppControllerBE {

	function render() {
		$rc = new ReflectionClass('HTMLForm');
		$rf = $rc->getMethod('renderSelectionOptions');
		$content = getDebug($rf->getDocComment());
		return $content;
	}

}
