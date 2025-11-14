<?php

use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;

/**
 * @phpstan-consistent-constructor
 */
class File
{

	public $isDir;

	public string $dir;

	/**
	 * @var string this is a relative path (can be absolute as well)
	 */
	protected string $name;

	public $spl;

	/**
	 * @var Filesystem
	 */
	public $fly;

	public ?FileAttributes $meta;

	/**
	 * @var string|null the path in the $name is relative to this
	 */
	public $relativeTo;

	/**
	 * @param $path
	 * @param string|null $relativeTo
	 */
	public function __construct($path, ?string $relativeTo = null)
	{
		$this->relativeTo = $relativeTo;
		$this->dir = dirname($path) === '.' ? '' : dirname($path);
		$this->name = basename($path);
	}

	public static function fromLocal($file, ?string $relativeTo = null): static
	{
		if (!file_exists($file) && !is_dir($file)) {
			throw new RuntimeException('File ' . $file . ' does not exists');
		}

		$file = new static($file, $relativeTo);
		$file->isDir = is_dir($file);
		return $file;
	}

	public static function fromSpl(SplFileInfo $info): static
	{
		$file = new static($info->getPathname());
		$file->spl = $info;
		return $file;
	}

	/**
	 * Get the full pathname of the file.
	 * 
	 * Returns the complete path to the file by combining directory and filename.
	 * If $dir is set, it returns $dir/$name.
	 * If $relativeTo is set, it uses path_plus() to combine $relativeTo and $name.
	 * If neither is set, it returns just the $name.
	 * 
	 * @return string The full pathname of the file
	 */
	public function getPathname()
	{
		if ($this->dir !== '' && $this->dir !== '0') {
			return $this->dir . '/' . $this->name;
		}

//		llog(__METHOD__, $this->relativeTo, $this->name, $absolute);
		if ($this->relativeTo === null) {
			return $this->name;
		}
		return path_plus($this->relativeTo, $this->name);
	}

	public static function fromFly(Filesystem $fly, FileAttributes $fileMeta): static
	{
		$file = new static($fileMeta->path());
		$file->fly = $fly;
		$file->meta = $fileMeta;
		return $file;
	}

	public function getDir(): string
	{
		return $this->dir;
	}

	public function getExt(): string
	{
		return pathinfo($this->getName(), PATHINFO_EXTENSION);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getBasename(): string
	{
		return $this->name;
	}

	public function md5()
	{
		return md5_file($this->getPathname());
	}

	public function getURL(): string|Path
	{
		$path = new Path($this->getPathname());
		return $path->getURL();
	}

	public function getSize(): int|null|false
	{
		return $this->size();
	}

	public function size(): int|null|false
	{
		if ($this->meta) {
			return $this->meta->fileSize();
		}

		return filesize($this->getPathname());
	}

	public function time(): int|null|false
	{
		if ($this->meta) {
			return $this->meta->lastModified();
		}

		return filemtime($this->getPathname());
	}

	public function mime(): string
	{
		$mime = new MIME();
		return $mime->get_mime_type($this->getPathname());
	}

	public function getExtension(): string
	{
		return pathinfo($this->getName(), PATHINFO_EXTENSION);
	}

	public function __toString(): string
	{
		return $this->getPathname();
	}

	public function getCTime(): int|false
	{
		return filectime($this->getPathname());
	}

	public function getMTime(): int|false
	{
		return filemtime($this->getPathname());
	}

	public function getType(): string
	{
		return $this->isDir ? 'dir' : 'file';
	}

}
