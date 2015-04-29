<?php

class IniCheck extends AppControllerBE {

	function render() {
		$content = array();
		$iniFile = AutoLoad::getInstance()->appRoot.'php.ini';
		$iniData = parse_ini_file($iniFile, true);  // sections
		foreach ($iniData as $section => $subSection) {
			$content[] = '<h1>'.$section.'</h1>';
			$content[] = $this->showSection($subSection);
		}
		//$content[] = getDebug(get_loaded_extensions());
		return $content;
	}

	function showSection(array $iniData) {
		foreach ($iniData as $key => $val) {
			if ($key == 'extension') {
				foreach ($val as $ex) {
					$ex = str_replace('.so', '', $ex);
					$ex = str_replace('.dll', '', $ex);
					$ex = str_replace('php_', '', $ex);
					$is = extension_loaded($ex);
					$table[] = array(
						'key' => $ex,
						'must' => 1,
						'is' => $is,
						'###TD_CLASS###' => $val == $is ? 'success' : 'danger',
					);
				}
			} else {
				$is = ini_get($key);
				$table[] = array(
					'key' => $key,
					'must' => $val,
					'is' => $is,
					'###TD_CLASS###' => $val == $is ? 'success' : 'danger',
				);
			}
		}
		$content[] = new slTable($table, 'class="table table-striped"');
		return $content;
	}

}
