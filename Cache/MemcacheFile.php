<?php

class MemcacheFile implements MemcacheInterface
{

	/**
	 * Can be set statically in the bootstrap to influence all instances
	 * @var string
	 */
	static $defaultFolder = 'cache/';

	/**
	 * @used in ClearCache
	 * @var string
	 */
	public $folder;

	public $key;

	public $expire = 0;

	/**
	 * If you define $key and $expire in the constructor
	 * you don't need to define it in each method below.
	 * Otherwise, please specify.
	 * @param string $key
	 * @param int $expire
	 */
	public function __construct($folder = null, $expire = 0)
	{
		$this->folder = $folder ?: self::$defaultFolder;
		if (!Path::isItAbsolute($this->folder)) {
			// if relative, add current app
			$appRoot = AutoLoad::getInstance()->getAppRoot();
			$sub = cap($appRoot . '');
		} else {
			$sub = '';
		}

		$finalCachePath = realpath($sub . $this->folder);
		if (!file_exists($finalCachePath) && !is_dir($finalCachePath)) {
			debug([
				'unable to access cache folder',
				'env(storage)' => getenv('storage'),
				'cwd' => getcwd(),
				'open_basedir' => trimExplode(':', ini_get('open_basedir')),
				'this->folder' => $this->folder,
				'isAbsolute' => Path::isItAbsolute($this->folder),
				'method' => __METHOD__,
				'sub' => $sub,
				'folder' => $this->folder,
				'finalCachePath' => $finalCachePath,
			]);
			die(__METHOD__);
		} else {
			$this->folder = cap($finalCachePath);    // important as we concat
		}

		if ($expire) {
			$this->expire = $expire;
		}
	}

	public function map($key)
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

	/**
	 * @param string $key - can be provided in the constructor, but repeated here for BWC
	 * @param mixed $val
	 * @throws Exception
	 */
	public function set($key, $val)
	{
		TaylorProfiler::start(__METHOD__);
		$file = $this->map($key);
		if (is_writable($this->folder)) {
			file_put_contents($file, serialize($val));
			@chmod($file, 0777);    // needed for cronjob accessing cache files
		} else {
			TaylorProfiler::stop(__METHOD__);
			throw new Exception($file . ' write access denied.');
		}
		TaylorProfiler::stop(__METHOD__);
	}

	public function isValid($key = NULL, $expire = 0)
	{
		$key = $key ?: $this->key;
		$expire = $expire ?: $this->expire;
		$file = $this->map($key);
		$mtime = @filemtime($file);
		$bigger = ($mtime > (time() - $expire));
		if ($this->key == 'OvertimeChart::getStatsCached') {
//			debug($this->key, $file, $mtime, $expire, $bigger);
		}
		return /*!$expire ||*/ $bigger;
	}

	/**
	 * @param null $key - can be NULL to be used from the constructor
	 * @param int $expire
	 * @return mixed|null|string
	 */
	public function get($key = NULL, $expire = 0)
	{
		TaylorProfiler::start(__METHOD__);
		$val = NULL;
		$key = $key ?: $this->key;
		$expire = $expire ?: $this->expire;
		$file = $this->map($key);
		//debug($file);
		if ($this->isValid($key, $expire)) {
			$val = @file_get_contents($file);
			if ($val) {
				$try = @unserialize($val);
				if ($try !== false) {
					$val = $try;
				}
			}
		}
		TaylorProfiler::stop(__METHOD__);
		return $val;
	}

	public function setValue($value)
	{
		$this->set($this->key, $value);
	}

	public function clearCache($key = null)
	{
		$file = $this->map($key ?: $this->key);
		if (file_exists($file)) {
			//echo '<font color="green">Deleting '.$file.'</font>', BR;
			unlink($file);
		} else {
			//echo '<font color="orange">Cache file'.$file.' does not exist.</font>', BR;
		}
	}

	/**
	 * @param string $key
	 * @return Duration
	 */
	public function getAge($key)
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
