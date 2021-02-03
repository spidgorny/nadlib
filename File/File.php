<?php

class File
{

	protected $dir;

	public bool $isDir;

	protected $name;

	public SplFileInfo $spl;

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

	public static function fromLocal($file)
	{
		if (!file_exists($file) && !is_dir($file)) {
			throw new Exception('File ' . $file . ' does not exists');
		}
		$file = new static($file);
		$file->isDir = is_dir($file);
		return $file;
	}

	public static function fromSpl(SplFileInfo $info)
	{
		$file = new static($info->getPathname());
		$file->spl = $info;
		return $file;
	}

	public static function fromFly(League\Flysystem\Filesystem $fly, array $file)
	{
		$file = new static($file['path']);
		$file->fly = $fly;
		$file->meta = $file;
		return $file;
	}

	public function __construct($path)
	{
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

	public function size()
	{
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
