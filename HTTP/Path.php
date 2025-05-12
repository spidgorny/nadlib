<?php

use spidgorny\nadlib\HTTP\URL;

class Path
{
	public $sPath;

	public $aPath;

	public $isDir = true;

	public $isFile = false;

	/**
	 * @var bool
	 */
	public $isAbsolute;

	public static function slash($path)
	{
		return str_replace('\\', '/', $path);
	}

	/**
	 * @param $sPath
	 */
	public static function make($sPath): self
	{
		return new self($sPath);
	}

	/**
	 * @return $this
	 */
	public function appendString(string $plus): static
	{
		$pPlus = new Path($plus);
		$this->append($pPlus);
		return $this;
	}

	/**
	 * @return $this
	 */
	public function append(Path $plus): static
	{
		foreach ($plus->aPath as $name) {
			if ($name === '.') {
				continue;
			}

			if ($name === '..') {
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
	 * Modifies the string path after array modification
	 */
	public function implode(): string
	{
		$notSlash = $this->aPath != ['/'];
		if ($this->isWindows()) {
			$prefix = '';
		} else {
			$prefix = (($this->isAbsolute && $notSlash) ? '/' : '');
		}

		$this->sPath = $prefix . implode('/', $this->aPath);
		if ($this->isDir && count($this->aPath)) {    // avoid "//"
			$this->sPath .= '/';
		}

		return $this->sPath;
	}

	/**
	 * Check for ":" in C:...
	 */
	public function isWindows(): bool
	{
		return (isset($this->aPath[0][1]) && $this->aPath[0][1] === ':');
	}

	/**
	 * @param string $plus
	 * @return $this
	 */
	public function prependString($plus): static
	{
		$pPlus = new Path($plus);
		$this->aPath = array_merge($pPlus->aPath, $this->aPath);
		return $this;
	}

	/**
	 * @param $plus string|Path
	 */
	public function appendIfExists($plus): bool
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
	public function exists()
	{
		if (ini_get('open_basedir')) {
			return false;
		}

		return is_dir($this->sPath) || file_exists($this->sPath);
	}

	public function trimIf($dirname): void
	{
		if (end($this->aPath) == $dirname) {
			$this->trim();
		}
	}

	public function trim(): void
	{
		array_pop($this->aPath);
		$this->implode();
	}

	/**
	 * @param $with
	 */
	public function ends($with): bool
	{
		return end($this->aPath) == $with;
	}

	/**
	 * @param $that
	 */
	public function upIf($that): static
	{
		if (end($this->aPath) == $that) {
			return $this->up();
		}

		return $this;
	}

	public function up(): static
	{
		if ($this->aPath) {
			array_pop($this->aPath);
		} else {
			$this->aPath[] = '..';
		}

		$this->implode();
		return $this;
	}

	/**
	 * @param $that
	 */
	public function upIfNot($that): static
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
	public function remove($minus): static
	{
		$minus = $minus instanceof Path ? $minus : new Path($minus);
		foreach ($minus->aPath as $sub) {
			if (ifsetor($this->aPath[0]) == $sub) {  // 0 because shift
				array_shift($this->aPath);
			} else {
				break;
			}
		}

		// remove empty paths which lead to //
		$this->aPath = array_filter($this->aPath);
		$this->implode();
		return $this;
	}

	/**
	 * Removes path elements from the BEGINNING of the path.
	 * Symlinks are considered identical.
	 * @param $minus
	 * @return $this
	 */
	public function removeWithLinks($minus): static
	{
		$minus = $minus instanceof Path ? $minus : new Path($minus);
		foreach ($minus->aPath as $sub) {
			$sameName = ifsetor($this->aPath[0]) == $sub;
			d($this->sPath);
			$linkTarget = is_link($this->sPath) ? readlink($this->sPath) : $this->sPath;

			d(__METHOD__, $linkTarget, $minus->sPath);
			$sameLink = $linkTarget == $minus->sPath;
			if ($sameName || $sameLink) {
				array_shift($this->aPath);
				$this->implode();
			} else {
				break;
			}
		}

		$this->implode();
		return $this;
	}

	public function reverse(): static
	{
		if (!$this->isAbsolute() && !empty($this->aPath)) {
			$this->aPath = array_fill(0, count($this->aPath), '..');
			$this->implode();
		}

		return $this;
	}

	public function isAbsolute(): bool
	{
		return self::isItAbsolute($this->sPath);
	}

	public static function isItAbsolute(string $sPath): bool
	{
		return str_startsWith($sPath, '/')        // Linux
			|| (isset($sPath[1]) && $sPath[1] === ':');    // Windows c:
	}

	public function resolveLink(): void
	{
		if (is_link($this->sPath)) {
			$this->sPath = readlink($this->sPath);
			$this->explode();
		}

		$this->realPath();
	}

	/**
	 * Modifies the array path after string modification
	 */
	public function explode(): void
	{
		$forwardSlash = str_replace('\\', '/', $this->sPath);
		$this->aPath = trimExplode('/', $forwardSlash);
	}

	public function realPath(): static
	{
		$this->sPath = realpath($this->sPath);
		$this->explode();
		return $this;
	}

	public function resolveLinksSimple(): static
	{
		foreach ($this->aPath as $i => $part) {
			$assembled = '/' .
				implode('/', array_slice($this->aPath, 0, $i));
			//			debug($assembled, is_link($assembled));
			if (ini_get('open_basedir')) {
				continue;
			}

			if (@is_link($assembled)) {
				$this->aPath[$i - 1] = trim(readlink($assembled), '/');
			}
		}

		$this->aPath = array_filter($this->aPath);
		$this->implode();
		return $this;
	}

	public function onlyExisting(): static
	{
		$assembled = '';
		foreach ($this->aPath as $i => $part) {
			$assembled = '/' .
				implode('/', array_slice($this->aPath, 0, $i + 1));
			if (!is_dir($assembled)) {
				break;
			}
		}

		$this->__construct($assembled);
		$this->up();
		return $this;
	}

	public function __construct(string $sPath)
	{
		$this->sPath = $sPath . '';
		$this->isDir = str_endsWith($this->sPath, '/');
		$this->isFile = !$this->isDir;
		$this->isAbsolute = $this->isAbsolute();
		$this->explode();
		$this->implode();   // to prevent '//'
	}

	public function relativeFromDocRoot(): self
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

	public function makeAbsolute(): void
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
	public function checkFileDir(): void
	{
		$this->isFile = is_file($this->sPath);
		$this->isDir = is_dir($this->sPath);
	}

	private function cutArrayFromArray(array $long, array $short): array
	{
		$new = [];
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

	public static function fromArray(array $parts): \Path
	{
		$path = new Path('');
		$path->aPath = $parts;
		$path->implode();
		return $path;
	}

	public function relativeFromAppRoot(): self
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

	/**
	 * It should not cap() the result, we don't know if it's a file or dir
	 */
	public function getURL(): string|\Path
	{
		//$self = new Path(AutoLoad::getInstance()->appRoot);
		$self = new Path(URL::getScriptWithPath());
		//debug($self, basename($this->sPath), $this->sPath);
		if ($self->contains(basename($this->sPath))) {
			$relative = new Path(URL::getRelativePath($self, $this));
			//			$relative->setAsDir();
			//debug(__METHOD__, $this . '', $self . '', $relative . '');
		} else {
			$relative = URL::getRelativePath($self, $this->sPath);
		}

		return $relative;
	}

	/**
	 * @param string $dirname
	 */
	public function contains($dirname): bool
	{
		return in_array($dirname, $this->aPath);
	}

	public function setFile($name): void
	{
		if ($this->isFile) {
			$this->aPath[count($this->aPath) - 1] = $name;
		} else {
			$this->aPath[] = $name;
		}

		$this->implode();
	}

	public function setAsDir(): void
	{
		$this->isDir = true;
		$this->isFile = false;
	}

	public function setAsFile(): void
	{
		$this->isDir = false;
		$this->isFile = true;
	}

	public function length(): int
	{
		return count($this->aPath);
	}

	public function getDebug(): array
	{
		return [
			'sPath' => $this->sPath,
			'aPath' => $this->aPath,
			'isAbsolute' => $this->isAbsolute(),
			'isDir' => $this->isDir,
			'isFile' => $this->isFile,
			'exists' => $this->exists(),
		];
	}

	public function getLevels()
	{
		return $this->aPath;
	}

	public function getNameless($i)
	{
		return $this->aPath[$i];
	}

	public function normalize(): static
	{
		$this->__construct($this->getNormalized());
		return $this;
	}

	/**
	 * http://php.net/manual/en/function.realpath.php#112367
	 */
	public function getNormalized(): string
	{
		$path = $this->__toString();
		$parts = [];// Array to build a new path from the good parts
		$path = str_replace('\\', '/', $path);// Replace backslashes with forwardslashes
		$path = preg_replace('/\/+/', '/', $path);
		// Combine multiple slashes into a single slash
		$segments = explode('/', $path);// Collect path segments
		$test = '';// Initialize testing variable
		foreach ($segments as $segment) {
			if ($segment !== '.') {
				$test = array_pop($parts);
				if (is_null($test)) {
					$parts[] = $segment;
				} elseif ($segment === '..') {
					if ($test == '..') {
						$parts[] = $test;
					}

					if ($test == '..' || $test == '') {
						$parts[] = $segment;
					}
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

	public function __toString(): string
	{
		return $this->isDir ? $this->getCapped() : $this->getUncapped();
	}

	public function getCapped(): string
	{
		if (!count($this->aPath) && $this->isAbsolute()) {
			return $this->implode();    // absolute empty has slash already
		}

		return cap($this->implode());
	}

	public function getUncapped(): string
	{
		return $this->implode();
	}

	public function hasFile($file): bool
	{
		$files = $this->getFiles();
		//debug($files);
		return (bool)ifsetor($files[$file]);
	}

	public function getFiles(): array
	{
		$files = glob(cap($this->sPath) . '*');
		$basenames = array_map(function ($file): string {
			return basename($file);
		}, $files);
		return array_combine($basenames, $files);
	}

	public function debugPathExists(): void
	{
		$debug = [];
		$sPath = $this->isAbsolute() ? '/' : '';
		foreach ($this->aPath as $i => $section) {
			$sPath .= $section;
			if ($i < count($this->aPath)) {
				$sPath .= '/';
			}

			$debug[$sPath] = file_exists($sPath);
		}

		debug($debug);
	}

	public function normalizeHomePage(): static
	{
		$this->realPath();
		//debug(__METHOD__, $this->sPath, $this->aPath);
		$this->resolveLinks();        // important to avoid differences
		foreach ($this->aPath as $i => $el) {
			if ($el[0] === '~') {
				$username = str_replace('~', '', $el);
				array_splice($this->aPath, $i, 1, [$username, 'public_html']);
				//				debug($el, $username, $this->aPath);
			}
		}

		$this->implode();
		return $this;
	}

	/**
	 * Recursive. For no reason?
	 * Reason is that readlink() fails if the final part is not a link
	 * @return $this
	 */
	public function resolveLinks(): static
	{
		foreach ($this->aPath as $i => $part) {
			$assembled = '/' . implode('/', array_slice($this->aPath, 0, $i));
			//			debug($assembled, is_link($assembled));
			if (ini_get('open_basedir')) {
				return $this;
			}

			if (@is_link($assembled)) {
				$this->sPath = readlink($assembled);
				$this->explode();
				$this->resolveLinks();
				break;
			}
		}

		return $this;
	}

	public function basename(): mixed
	{
		return end($this->aPath);
	}
}
