<?php

class AutoLoadBE extends AutoLoad
{

	private static ?\AutoLoadBE $instance2 = null;

	public function getFolders()
	{
		require_once __DIR__ . '/../HTTP/Request.php';
		$folders = [];
		if (!Request::isCLI() && $this->useCookies) {
			session_set_cookie_params(0, '');
			// current folder
			header('X-Session-Start: ' . __METHOD__);
			session_start();
			if (isset($_SESSION[__CLASS__])) {
				$folders = $_SESSION[__CLASS__]['folders'] ?? [];
				$this->classFileMap = $_SESSION[__CLASS__]['classFileMap'] ?? [];
			}
		}

		if (!$folders) {
			$folders = ['be/class'];
			$folders = array_merge($folders, $this->folders->getFoldersFromConfigBase());
		}

		debug($folders);

		return $folders;
	}

	public static function getInstance(): \AutoLoad
	{
		if (!self::$instance2 instanceof \AutoLoadBE) {
			self::$instance2 = new self();
		}

		return self::$instance2;
	}

}
