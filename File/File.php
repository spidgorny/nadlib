<?php

use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\StorageAttributes;

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
	protected $name;

	public $spl;

	/**
	 * @var Filesystem
	 */
	public $fly;

	public FileAttributes $meta;

	/**
	 * @var string|null the path in the $name is relative to this
	 */
	public $relativeTo;

	/**
	 * @param $path
	 * @param string|null $relativeTo
	 */
	public function __construct($path, string $relativeTo = null)
	{
		$this->relativeTo = $relativeTo;
		$this->dir = dirname($path) === '.' ? '' : dirname($path);
		$this->name = basename($path);
	}

	public static function fromLocal($file, string $relativeTo = null)
	{
		if (!file_exists($file) && !is_dir($file)) {
			throw new RuntimeException('File ' . $file . ' does not exists');
		}
		$file = new static($file, $relativeTo);
		$file->isDir = is_dir($file);
		return $file;
		// relative to some unknown root should work
//		if (!file_exists($file) && !is_dir($file)) {
//			throw new Exception('File ' . $file . ' does not exists');
//		}
		$file = new static($file, $relativeTo);
		$file->isDir = is_dir($file);
		return $file;
	}

	public static function fromSpl(SplFileInfo $info)
	{
		$file = new static($info->getPathname());
		$file->spl = $info;
		return $file;
	}

	public function getPathname()
	{
		if ($this->dir) {
			return $this->dir . '/' . $this->name;
		}
		$absolute = path_plus($this->relativeTo, $this->name);
//		llog(__METHOD__, $this->relativeTo, $this->name, $absolute);
		return $absolute;
	}

	public static function fromFly(Filesystem $fly, FileAttributes $fileMeta)
	{
		$file = new static($fileMeta->path());
		$file->fly = $fly;
		$file->meta = $fileMeta;
		return $file;
	}

	public function getDir()
	{
		return $this->dir;
	}

	public function getExt()
	{
		return pathinfo($this->getName(), PATHINFO_EXTENSION);
	}

	public function getName()
	{
		return $this->name;
	}

	public function getBasename()
	{
		return $this->name;
	}

	public function md5()
	{
		return md5_file($this->getPathname());
	}

	public function getURL()
	{
		$path = new Path($this->getPathname());
		return $path->getURL();
	}

	public function getSize()
	{
		return $this->size();
	}

	public function size()
	{
		if ($this->meta) {
			return $this->meta->fileSize();
		}
		return filesize($this->getPathname());
	}

	public function time()
	{
		if ($this->meta) {
			return $this->meta->lastModified();
		}
		return filemtime($this->getPathname());
	}

	public function mime()
	{
		$mime = new MIME();
		return $mime->get_mime_type($this->getPathname());
	}

	public function getExtension()
	{
		return pathinfo($this->getName(), PATHINFO_EXTENSION);
	}

	public function __toString()
	{
		return $this->getPathname();
	}

	public function getCTime()
	{
		return filectime($this->getPathname());
	}

	public function getMTime()
	{
		return filemtime($this->getPathname());
	}

	public function getType()
	{
		return $this->isDir ? 'dir' : 'file';
	}

}
