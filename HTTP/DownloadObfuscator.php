<?php

/**
 * (c) 2010 Slawa
 * Usage:
 *        $obfuskator = new DownloadObfuscator($this);
 * $attachment = $obfuskator->getDownloadLink();
 */
class DownloadObfuscator
{
	/**
	 * File with relative path (as if downloading directly from browser URL)
	 * @var string
	 */
	protected $file;

	/**
	 * @var string
	 */
	protected $fileSuffix;

	/**
	 * @var string secret code for hash generation
	 */
	protected $swordfish = 's0meS$cretC0de';

	public $linkPrefix = '?';

	/**
	 * Supply the filename and folder
	 *
	 * @param $file
	 * @param string $fileSuffix
	 */
	public function __construct($file = '', $fileSuffix = '')
	{
		$this->file = $file ?: $_REQUEST['file'];
		// numeric ID may be used to calculate hash without downloading the file from a particular page (FlySystem)
		invariant(is_numeric($this->file) || $this->validateFilePath($this->file), $this->file . ' file path can not be validated');
		$this->fileSuffix = $fileSuffix;
		if (getenv('DOWNLOAD_OBFUSCATOR_SECRET')) {
			$this->swordfish = getenv('DOWNLOAD_OBFUSCATOR_SECRET');
		}
		$this->swordfish .= date('Y-m-d-H');
	}

	public function validateFilePath($filePath)
	{
		$parts = trimExplode('/', $filePath);
		return collect($parts)->every(fn($x) => $this->validateFilename($x, false));
	}

	public function validateFilename(string $filename, bool $allowUnicode = false): bool
	{
		// 1. Empty filename check
		if (empty($filename)) {
			return false;
		}

		// 2. Length check (optional, but recommended)
		$maxLength = 255; // Adjust as needed
		if (strlen($filename) > $maxLength) {
			return false;
		}

		// 3. Character validation (most important)
		$pattern = '/^[\w\-\.\_\~\(\)\ ]+$/'; // Basic alphanumeric, hyphen, underscore, period, tilde, parentheses, and space
		if ($allowUnicode) {
			$pattern = '/^[\p{L}\p{N}\p{S}\p{P}\w\-\.\_\~\(\)\ ]+$/u'; // Unicode support (letters, numbers, symbols, punctuation, etc.)
		}

		if (!preg_match($pattern, $filename)) {
			return false;
		}

		// 4. Reserved characters or names (OS-specific)
		$reservedNames = ['con', 'prn', 'aux', 'nul', 'com1', 'com2', 'com3', 'com4', 'lpt1', 'lpt2', 'lpt3', 'lpt4']; // Windows reserved names (case-insensitive)
		if (in_array(strtolower($filename), $reservedNames)) {
			return false;
		}

		// 5. Check for leading/trailing spaces (often unwanted)
		if (trim($filename) !== $filename) {
			return false; // Or you could just trim it: $filename = trim($filename);
		}


		// 6. Check for multiple consecutive periods (sometimes problematic)
		if (preg_match('/\.\.+/', $filename)) {
			return false;
		}

		// 7. Check for special characters you want to exclude (e.g., control characters)
		if (preg_match('/[\x00-\x1F\x7F]/', $filename)) { // Control characters
			return false;
		}

		return true; // Filename is valid
	}


	public function getDownloadLink()
	{
		//$link = '?id='.DownloadObfuscator::page.'&type='.DownloadObfuscator::type.'&file='.urlencode($this->filename).'&check='.$this->getHash();
		//$link = '?id='.DownloadObfuscator::page.'&type='.DownloadObfuscator::type.'&subid='.$this->sub->submission['uid'].'&fileSuffix='.$this->fileSuffix.'&check='.$this->getHash();
		$link = $this->linkPrefix . http_build_query([
				'c' => static::class,
				//'id' => DownloadObfuscator::page,
				//'type' => DownloadObfuscator::type,
				'file' => $this->file,
				'fileSuffix' => $this->fileSuffix,
				'check' => $this->getHash(),
			]);
		$link .= '&/' . basename($this->file);    // http://stackoverflow.com/a/216777
		return $link;
	}

	public function getHash()
	{
		//return md5($this->swordfish.$this->filename);
		//t3lib_div::debug($this->sub->uploadFileLink . $this->swordfish . $this->sub->submission['uid']);
		//return md5($this->getFileNameWithSuffix($this->sub->uploadFileLink) . $this->swordfish . $this->sub->submission['uid']);
		return md5($this->getFileNameWithSuffix($this->file) . $this->swordfish);
	}

	public function getFileNameWithSuffix($file)
	{
		return substr($file, 0, -4) . $this->fileSuffix . substr($file, -4);
	}

	public function checkHash($check)
	{
		//debug($this->getHash(), $check);
		return $this->getHash() === $check;
	}

	/**
	 * Previous name checkAndStreamFile
	 */
	public function render()
	{
		$r = Request::getInstance();
		if ($this->checkHash($r->getTrim('check'))) {
			$this->streamFile();
		} else {
			throw new AccessDeniedException(__('Hash check failed.'));
		}
	}

	public function fileExists($file)
	{
		//$file = str_replace(SUBMISSION_SUB_BASE, '', $file);
		//$file = escapeshellcmd($file);
		$file = str_replace("''", "'", $file);    // who escaped it?!?
		return file_exists($file) && is_readable($file);
	}

	public function streamFile()
	{
		$file = $this->getFileNameWithSuffix($this->file);
		//$file = str_replace(SUBMISSION_SUB_BASE, '', $file);
		$exists = $this->fileExists($file);
		//debug($file, $exists, glob(dirname($file).'/*')); exit();
		if ($exists) {
			//if ($GLOBALS['TSFE']->fe_user->user['uid']) {
			$this->forceDownload($file);
			readfile($file);
			exit();
		} else {
			throw new Exception(__('File does not exist.'));
		}
	}

	public function forceDownload($file)
	{
		// urlencode makes is ugly
		header('Content-Disposition: attachment; filename="' ./*urlencode*/
			(basename($file)) . '"');
		header('Content-type: application/force-download');
		header('Content-type: application/octet-stream');
		//header('Content-type: application/x-msdownload'); // Excel?!?
	}

}
