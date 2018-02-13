<?php

class Path {

	var $sPath;

	var $aPath;

	var $isDir = true;

	var $isFile = false;

	var $isAbsolute;

	static function slash($path)
	{
		return str_replace('\\', '/', $path);
	}

	function __construct($sPath)
	{
		$this->sPath = $sPath . '';
		$this->isDir = str_endsWith($this->sPath, '/');
		$this->isFile = !$this->isDir;
		$this->isAbsolute = $this->isAbsolute();
		$this->explode();
		$this->implode();   // to prevent '//'
	}

	static function isItAbsolute($sPath)
	{
		return str_startsWith($sPath, '/')
			|| (isset($sPath[1]) && $sPath[1] == ':');
	}

	function isAbsolute()
	{
		return self::isItAbsolute($this->sPath);
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
		if ($this->isWindows()) {
			$prefix = '';
		} else {
			$prefix = (($this->isAbsolute && $notSlash) ? '/' : '');
		}
		$this->sPath = $prefix . implode('/', $this->aPath);
		if ($this->isDir && sizeof($this->aPath)) {    // avoid "//"
			$this->sPath .= '/';
		}
		return $this->sPath;
	}

	/**
	 * Check for ":" in C:...
	 * @return bool
	 */
	function isWindows()
	{
		return (isset($this->aPath[0][1]) && $this->aPath[0][1] == ':');
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
		if (defined('DEVELOPMENT') && DEVELOPMENT) {
			//debug($this->aPath, $plus->aPath);
		}
		foreach ($plus->aPath as $name) {
			if ($name == '.') {
				continue;
			} elseif ($name == '..') {
				array_pop($this->aPath);
			} else {
				$this->aPath[] = $name;
			}
		}
		$this->isDir = $plus->isDir;
		$this->isFile = $plus->isFile;
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
		return $this->implode();
	}

	function getCapped()
	{
		if (!sizeof($this->aPath) && $this->isAbsolute()) {
			return $this->implode();    // absolute empty has slash already
		}
		return cap($this->implode());
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
	 * @param $that
	 * @return self
	 */
	public function upIfNot($that)
	{
		if (end($this->aPath) != $that) {
			return $this->up();
		}
		return $this;
	}

	/**
	 * Removes path elements from the BEGINNING of the path
	 * @param $minus
	 * @return $this
	 */
	function remove($minus)
	{
		$minus = $minus instanceof Path ? $minus : new Path($minus);
		foreach ($minus->aPath as $i => $sub) {
			if (ifsetor($this->aPath[0]) == $sub) {  // 0 because shift
				array_shift($this->aPath);
			} else {
				break;
			}
		}
		$this->implode();
		return $this;
	}

	function reverse()
	{
		if (!$this->isAbsolute() && !empty($this->aPath)) {
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
	}

	public function resolveLinks()
	{
		foreach ($this->aPath as $i => $part) {
			$assembled = '/' . implode('/', array_slice($this->aPath, 0, $i));
//			debug($assembled, is_link($assembled));
			if (is_link($assembled)) {
				$this->sPath = readlink($assembled);
				$this->explode();
				$this->resolveLinks();
				break;
			}
		}
	}

	/**
	 * @return Path
	 */
	public function relativeFromDocRoot()
	{
		$this->makeAbsolute();
		$al = AutoLoad::getInstance();

		// will cut duplicates
		//$new = array_diff($this->aPath, $al->documentRoot->aPath);
		$new = $this->cutArrayFromArray($this->aPath, $al->documentRoot->aPath);

		//debug($this->aPath, $al->documentRoot->aPath, $new);
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
		$appRoot = $al->getAppRoot();
		if ($appRoot) {
			$new = $this->cutArrayFromArray($this->aPath, $appRoot->aPath);
//			debug(json_encode($this->aPath), json_encode($al->getAppRoot()->aPath), json_encode($new));
			$relative = Path::fromArray($new);
			$relative->isFile = $this->isFile;
			$relative->isDir = $this->isDir;
		} else {
			$relative = $this;
		}
		return $relative;
	}

	function makeAbsolute()
	{
		if (!$this->isAbsolute()) {
			//debug(getcwd(), $this);
			$prefix = new Path(getcwd());
			//debug($prefix);
			$prefix->append($this);
			$this->aPath = $prefix->aPath;
			$this->implode();
			$this->checkFileDir();
		}
	}

	/**
	 * This may not be isFile() and isDir() functions
	 * because we can force it to be a file or dir
	 * with setAsFile() and setAsDir().
	 */
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
			'isAbsolute' => $this->isAbsolute(),
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

	/**
	 * @param array $long
	 * @param array $short
	 * @return array
	 * @internal param $al
	 */
	private function cutArrayFromArray(array $long, array $short)
	{
		$new = array();
		$different = false;
		foreach ($long as $key => $value) {
			$other = ifsetor($short[$key]);
			if ($value != $other) {
				$different = true;
			}
			if ($different) {
				$new[] = $value;
			}
		}
		return $new;
	}

	/**
	 * http://php.net/manual/en/function.realpath.php#112367
	 * @return string
	 */
	function getNormalized()
	{
		$path = $this->__toString();
		$parts = array();// Array to build a new path from the good parts
		$path = str_replace('\\', '/', $path);// Replace backslashes with forwardslashes
		$path = preg_replace('/\/+/', '/', $path);// Combine multiple slashes into a single slash
		$segments = explode('/', $path);// Collect path segments
		$test = '';// Initialize testing variable
		foreach ($segments as $segment) {
			if ($segment != '.') {
				$test = array_pop($parts);
				if (is_null($test))
					$parts[] = $segment;
				else if ($segment == '..') {
					if ($test == '..')
						$parts[] = $test;

					if ($test == '..' || $test == '')
						$parts[] = $segment;
				} else {
					$parts[] = $test;
					$parts[] = $segment;
				}
			}
		}
		$parts = array_filter($parts);    // avoid "//"
		$prefix = $this->isAbsolute() ? '/' : '';
		return $prefix . implode('/', $parts);
	}

	function normalize()
	{
		$this->__construct($this->getNormalized());
		return $this;
	}

	function getFiles()
	{
		$files = glob(cap($this->sPath) . '*');
		$basenames = array_map(function ($file) {
			return basename($file);
		}, $files);
		$files = array_combine($basenames, $files);
		return $files;
	}

	function hasFile($file)
	{
		$files = $this->getFiles();
		//debug($files);
		return !!ifsetor($files[$file]);
	}

	public function debugPathExists()
	{
		$debug = array();
		$sPath = $this->isAbsolute() ? '/' : '';
		foreach ($this->aPath as $i => $section) {
			$sPath .= $section;
			if ($i < sizeof($this->aPath)) {
				$sPath .= '/';
			}
			$debug[$sPath] = file_exists($sPath);
		}
		debug($debug);
	}

	public function normalizeHomePage()
	{
		//debug(__METHOD__, $this->sPath, $this->aPath);
		$this->resolveLinks();        // important to avoid differences
		foreach ($this->aPath as $i => $el) {
			if ($el[0] == '~') {
				$username = str_replace('~', '', $el);
				array_splice($this->aPath, $i, 1, [$username, 'public_html']);
//				debug($el, $username, $this->aPath);
			}
		}
		$this->implode();
		return $this;
	}

	public function basename()
	{
		return end($this->aPath);
	}

}
