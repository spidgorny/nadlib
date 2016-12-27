<?php

class IniCheck extends AppControllerBE {

	function render() {
		$content = array();
		$iniFile = AutoLoad::getInstance()->appRoot.'php.ini';
		$iniData = parse_ini_file($iniFile, true);  // sections

		$htaccess = AutoLoad::getInstance()->appRoot.'.htaccess';
		if (file_exists($htaccess)) {
			$iniData['htaccess'] = $this->parseHtAccess($htaccess);
		}

		foreach ($iniData as $section => $subSection) {
			$content[] = '<h1>' . $section . '</h1>';
			if (is_array($subSection)) {
				$content[] = $this->showSection($subSection);
			}
		}
		//$content[] = getDebug(get_loaded_extensions());
		return $content;
	}

	function showSection(array $iniData) {
		$table = array();
		foreach ($iniData as $key => $val) {
			if ($key == 'extension' && is_array($val)) {
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
		$content[] = new slTable($table, 'class="table niceTable nospacing" width="100%"');
		return $content;
	}

	function parseHtAccess($htaccess) {
		$ini = [];
		$lines = file($htaccess);
		foreach ($lines as $line) {
			$line = str_replace("\t", ' ', $line);
			$parts = trimExplode(' ', $line);
			if ($parts) {
				if ($parts[0] == 'php_value') {
					$ini[$parts[1]] = unquote($parts[2]);
				} elseif ($parts[0] == 'php_flag') {
					$ini[$parts[1]] = strtolower($parts[2]) == 'on';
				}
			}
		}
		return $ini;
	}

}
