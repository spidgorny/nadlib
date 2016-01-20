<?php

class Session {

	static function isActive() {
		//debug(session_id(), !!session_id(), session_status(), $_SESSION['FloatTime']);
		if (function_exists('session_status')) {
			// somehow PHP_SESSION_NONE is the status when $_SESSION var exists
			return in_array(session_status(), [PHP_SESSION_ACTIVE, PHP_SESSION_NONE]);
		} else {
			return !!session_id();
		}
	}

}
