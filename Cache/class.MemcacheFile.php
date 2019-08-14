<?php

class MemcacheFile
{

	/**
	 * @used in ClearCache
	 * @var string
	 */
	public $folder = 'cache/';

	function __construct()
	{
		$sub = Config::getInstance()->appRoot;

		if (!file_exists($sub . $this->folder)) {
			debug(__METHOD__, $sub . $this->folder);
			die();
		} else {
			$this->folder = $sub . DIRECTORY_SEPARATOR . $this->folder;
		}
	}

	function map($key)
	{
		$key = str_replace('(', '-', $key);
		$key = str_replace(')', '-', $key);
		$key = str_replace('::', '-', $key);
		$key = str_replace(',', '-', $key);
		if (strpos($key, ' ') !== false || strpos($key, '/') !== false) {
			$key = md5($key);
		}
		$file = $this->folder . $key . '.cache'; // str_replace('(', '-', str_replace(')', '-', $key))
		return $file;
	}

	function set($key, $val)
	{
		if ($GLOBALS['prof']) $GLOBALS['prof']->startTimer(__METHOD__);
		$file = $this->map($key);
		if (is_writable($this->folder)) {
			file_put_contents($file, serialize($val));
			@chmod($file, 0777);    // needed for cronjob accessing cache files
		} else {
			if ($GLOBALS['prof']) $GLOBALS['prof']->stopTimer(__METHOD__);
			throw new Exception($file . ' write access denied.');
		}
		if ($GLOBALS['prof']) $GLOBALS['prof']->stopTimer(__METHOD__);
	}

	function isValid($key, $expire = 0)
	{
		$file = $this->map($key);
		return !$expire || (@filemtime($file) > (time() - $expire));
	}

	function get($key, $expire = 0)
	{
		if ($GLOBALS['prof']) $GLOBALS['prof']->startTimer(__METHOD__);
		$file = $this->map($key);
		if ($this->isValid($key, $expire)) {
			$val = @file_get_contents($file);
			if ($val) {
				$val = unserialize($val);
			}
		}
		if ($GLOBALS['prof']) $GLOBALS['prof']->stopTimer(__METHOD__);
		return $val;
	}

	function clearCache($key)
	{
		$file = $this->map($key);
		if (file_exists($file)) {
			//debug('<font color="green">Deleting '.$file.'</font>');
			unlink($file);
		}
	}

	/**
	 * @param $key
	 * @return Duration
	 */
	function getAge($key)
	{
		$file = $this->map($key);
		return new Duration(time() - @filemtime($file));
	}

	/**
	 * unfinished
	 * static function getInstance($file, $expire) {
	 * $mf = new self();
	 * $get = $mf->get($file, $expire);
	 * }
	 */
}
