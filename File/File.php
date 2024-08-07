<?php

use League\Flysystem\StorageAttributes;

class File
{

	public $isDir;

	/**
	 * @var string this is a relative path (can be absolute as well)
	 */
	protected $name;

	public $spl;

	/**
	 * @var \League\Flysystem\Filesystem
	 */
	public $fly;

	/**
	 * @var array[
	 * 'type'=>'file',
	 * 'path'=>'asd.ext',
	 * 'timestamp'=>1234567890
	 * 'size'=>1234,
	 * 'dirname'=>string,
	 * 'basename'=>string,
	 * 'extension'=>'ext',
	 * 'filename'=>'asd',
	 * ]
	 */
	public $meta;

	/**
	 * @var string|null the path in the $name is relative to this
	 */
	public $relativeTo;

	public static function fromLocal($file, string $relativeTo = null)
	{
		if (!file_exists($file) && !is_dir($file)) {
			throw new Exception('File ' . $file . ' does not exists');
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

	public static function fromFly(League\Flysystem\Filesystem $fly, array $fileMeta)
	{
		$file = new static($fileMeta['path']);
		$file->fly = $fly;
		$file->meta = $fileMeta;
		return $file;
	}

	public function __construct($path, string $relativeTo = null)
	{
		$this->relativeTo = $relativeTo;
		$this->dir = dirname($path) === '.' ? '' : dirname($path);
		$this->name = basename($path);
	}

	public function getDir()
	{
		return $this->dir;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getExt()
	{
		return pathinfo($this->getName(), PATHINFO_EXTENSION);
	}

	public function getBasename()
	{
		return $this->name;
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

	public function md5()
	{
		return md5_file($this->getPathname());
	}

	public function getURL()
	{
		$path = new Path($this->getPathname());
		return $path->getURL();
	}

	public function size()
	{
		if ($this->fly) {
//			llog('meta', $this->meta);
			return $this->meta[StorageAttributes::ATTRIBUTE_FILE_SIZE];
		}
		return filesize($this->getPathname());
	}

	public function getSize()
	{
		return $this->size();
	}

	public function time()
	{
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
