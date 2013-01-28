<?php

/**
 * Specific app implementation of the login form.
 * This one renders nothing because the login form is embedded into the template.
 */
class LoginForm extends AjaxLogin {
//class LoginForm extends AppControllerBE {

	function __render() {
		return '';
	}

}
