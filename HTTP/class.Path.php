<?php

class Path {

	var $sPath;

	var $aPath;

	var $isAbsolute = false;

	var $isDir = true;

	var $isFile = false;

	function __construct($sPath)
	{
		$this->sPath = $sPath . '';
		$this->isAbsolute = startsWith($this->sPath, '/') || (isset($this->sPath[1]) && $this->sPath[1] == ':');
		$this->isDir = endsWith($this->sPath, '/');
		$this->isFile = !$this->isDir;
		$this->explode();
		$this->implode();   // to prevent '//'
	}

	/**
	 * @param $sPath
	 * @return Path
	 */
	static function make($sPath)
	{
		$new = new self($sPath);
		return $new;
	}

	/**
	 * Modifies the array path after string modification
	 */
	function explode()
	{
		$forwardSlash = str_replace('\\', '/', $this->sPath);
		$this->aPath = trimExplode('/', $forwardSlash);
	}

	/**
	 * Modifies the string path after array modification
	 */
	function implode()
	{
		$notSlash = $this->aPath != array('/');
		$this->sPath = ((!Request::isWindows() && $this->isAbsolute && $notSlash) ? '/' : '') .
			implode('/', $this->aPath);
	}

	function __toString()
	{
		return $this->isDir ? $this->getCapped() : $this->getUncapped();
	}

	/**
	 * @param string $dirname
	 * @return bool
	 */
	function contains($dirname)
	{
		return in_array($dirname, $this->aPath);
	}

	/**
	 * @param Path $plus
	 * @return $this
	 */
	function append(Path $plus)
	{
		$this->aPath = array_merge($this->aPath, $plus->aPath);
		$this->implode();
		return $this;
	}

	/**
	 * @param $plus
	 * @return $this
	 */
	function appendString($plus)
	{
		$pPlus = new Path($plus);
		$this->append($pPlus);
		return $this;
	}

	/**
	 * @param $plus
	 * @return $this
	 */
	function prependString($plus)
	{
		$pPlus = new Path($plus);
		$this->aPath = array_merge($pPlus->aPath, $this->aPath);
		return $this;
	}

	/**
	 * @param $plus string|Path
	 * @return bool
	 */
	function appendIfExists($plus)
	{
		$pPlus = $plus instanceof Path
			? $plus
			: new Path($plus);
		$new = clone $this;
		$new->append($pPlus);
		//debug($pPlus, $new, $new->exists());
		if ($new->exists()) {
			$this->aPath = $new->aPath;
			$this->sPath = $new->sPath;
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	function exists()
	{
		return is_dir($this->sPath) || file_exists($this->sPath);
	}

	function trim()
	{
		array_pop($this->aPath);
		$this->implode();
	}

	function trimIf($dirname)
	{
		if (end($this->aPath) == $dirname) {
			$this->trim();
		}
	}

	function getUncapped()
	{
		return $this->sPath;
	}

	function getCapped()
	{
		return cap($this->sPath);
	}

	/**
	 * @param $with
	 * @return bool
	 */
	function ends($with)
	{
		return end($this->aPath) == $with;
	}

	/**
	 * @return self
	 */
	public function up()
	{
		if ($this->aPath) {
			array_pop($this->aPath);
		} else {
			array_push($this->aPath, '..');
		}
		$this->implode();
		return $this;
	}

	/**
	 * @param $that
	 * @return self
	 */
	public function upIf($that)
	{
		if (end($this->aPath) == $that) {
			return $this->up();
		}
		return $this;
	}

	/**
	 * @param $minus
	 * @return $this
	 */
	function remove($minus)
	{
		$minus = $minus instanceof Path ? $minus : new Path($minus);
		foreach ($minus->aPath as $i => $sub) {
			if (ifsetor($this->aPath[0]) == $sub) {  // 0 because shift
				array_shift($this->aPath);
				$this->isAbsolute = false;
			} else {
				break;
			}
		}
		$this->implode();
		return $this;
	}

	function reverse()
	{
		if (!$this->isAbsolute && !empty($this->aPath)) {
			$this->aPath = array_fill(0, sizeof($this->aPath), '..');
			$this->implode();
		}
		return $this;
	}

	public function resolveLink()
	{
		if (is_link($this->sPath)) {
			$this->sPath = readlink($this->sPath);
			$this->explode();
		}
		$this->realpath();
	}

	public function realpath()
	{
		$this->sPath = realpath($this->sPath);
		$this->explode();
	}

	/**
	 * @return Path
	 */
	public function relativeFromDocRoot()
	{
		$this->makeAbsolute();
		$al = AutoLoad::getInstance();
		$new = array_diff($this->aPath, $al->documentRoot->aPath);
		$relative = Path::fromArray($new);
		$relative->isFile = $this->isFile;
		$relative->isDir = $this->isDir;
		return $relative;
	}

	/**
	 * @return Path
	 */
	public function relativeFromAppRoot()
	{
		$this->makeAbsolute();
		$al = AutoLoad::getInstance();
		$new = array_diff($this->aPath, $al->appRoot->aPath);
		$relative = Path::fromArray($new);
		$relative->isFile = $this->isFile;
		$relative->isDir = $this->isDir;
		return $relative;
	}

	function makeAbsolute()
	{
		if (!$this->isAbsolute) {
			//debug(getcwd(), $this);
			$prefix = new Path(getcwd());
			//debug($prefix);
			$prefix->append($this);
			$this->aPath = $prefix->aPath;
			$this->implode();
			$this->isAbsolute = true;
			$this->checkFileDir();
		}
	}

	function checkFileDir()
	{
		$this->isFile = is_file($this->sPath);
		$this->isDir = is_dir($this->sPath);
	}

	public function getURL()
	{
		//$self = new Path(AutoLoad::getInstance()->appRoot);
		$self = new Path(URL::getScriptWithPath());
		//debug($self, basename($this->sPath), $this->sPath);
		if ($self->contains(basename($this->sPath))) {
			$relative = new Path(URL::getRelativePath($self, $this));
			$relative->setAsDir();
			//debug(__METHOD__, $this . '', $self . '', $relative . '');
		} else {
			$relative = cap(URL::getRelativePath($self, $this->sPath));
		}
		return $relative;
	}

	static function fromArray(array $parts)
	{
		$path = new Path('');
		$path->aPath = $parts;
		$path->implode();
		return $path;
	}

	public function setFile($name)
	{
		if ($this->isFile) {
			$this->aPath[sizeof($this->aPath) - 1] = $name;
		} else {
			$this->aPath[] = $name;
		}
		$this->implode();
	}

	public function setAsDir()
	{
		$this->isDir = true;
		$this->isFile = false;
	}

	public function setAsFile()
	{
		$this->isDir = false;
		$this->isFile = true;
	}

	public function length()
	{
		return sizeof($this->aPath);
	}

	public function getDebug()
	{
		return array(
			'sPath' => $this->sPath,
			'aPath' => $this->aPath,
			'isAbsolute' => $this->isAbsolute,
			'isDir' => $this->isDir,
			'isFile' => $this->isFile,
			'exists' => $this->exists(),
		);
	}

	public function getLevels()
	{
		return $this->aPath;
	}

	public function getNameless($i)
	{
		return $this->aPath[$i];
	}

}
