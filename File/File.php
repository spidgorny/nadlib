<?php

class File
{

	protected $dir;

	protected $name;

	public $spl;

	public $fly;

	public $meta;

	public static function fromLocal($file)
	{
		return new static($file);
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
		$this->dir = dirname($path);
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

	public function getPathname()
	{
		return $this->dir.'/'.$this->name;
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

}