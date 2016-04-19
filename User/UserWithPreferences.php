<?php

interface UserWithPreferences {

	function getPref($key);

	function setPref($key, $val);

}
