<?php

/**
 * (c) 2010 Slawa
 * TypoScript:
 * page.typeNum = 574
 * page.config.pageGenScript = typo3conf/ext/submission/lib/class.DownloadObfuscator.php
 * page.config.no_cache = 1
 * page.config.disableCharsetHeader = 1
 * page.config.sendCacheHeaders = 0
 * page.config.disableAllHeaderCode = 1
 *
 * 2013 Detached from TYPO3 environment
 *
 * Usage:
 *        $obfuskator = new DownloadObfuscator($this);
 * $attachment = $obfuskator->getDownloadLink();
 */
class DownloadObfuscator
{
	/**
	 * TYPO3 related
	 */
	const page = 18;

	/**
	 * TYPO3 related
	 */
	const type = 574;

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
		$this->fileSuffix = $fileSuffix;
		$this->swordfish .= date('Y-m-d-H');
	}

	public function getDownloadLink()
	{
		//$link = '?id='.DownloadObfuscator::page.'&type='.DownloadObfuscator::type.'&file='.urlencode($this->filename).'&check='.$this->getHash();
		//$link = '?id='.DownloadObfuscator::page.'&type='.DownloadObfuscator::type.'&subid='.$this->sub->submission['uid'].'&fileSuffix='.$this->fileSuffix.'&check='.$this->getHash();
		$link = $this->linkPrefix . http_build_query(array(
				'c' => static::class,
				//'id' => DownloadObfuscator::page,
				//'type' => DownloadObfuscator::type,
				'file' => $this->file,
				'fileSuffix' => $this->fileSuffix,
				'check' => $this->getHash(),
			));
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
		return $this->getHash() == $check;
	}

	/**
	 * TYPO3 specific
	 */
	public function checkAndStreamFileTYPO3()
	{
		//t3lib_div::debug($this->sub); exit();
		if ($this->sub->game['secret']) {
			if ($GLOBALS['TSFE']->fe_user->user['uid']) {
				//t3lib_div::debug($GLOBALS['TSFE']->fe_user);
				//t3lib_div::debug(get_class_methods($GLOBALS['TSFE']->fe_user));
				$conf = $GLOBALS['TSFE']->fe_user->getUserTSconf();
				//t3lib_div::debug($conf);
				if ($conf['submission.']['downloadSecretFiles']) {
					$this->streamFile();
				} else {
					echo 'Access to downloadSecretFiles denied.';
				}
			} else {
				echo 'Login to download from the secret project.';
			}
		} else {
			$this->streamFile();
		}
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
		// FIX http://ors.nintendo.de/dev/QueueEPES/QueueEPES/RequestInfoEPES?id=97311
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
			// urlencode makes is ugly
			header('Content-Disposition: attachment; filename="' ./*urlencode*/
				(basename($file)) . '"');
			header('Content-type: application/force-download');
			header('Content-type: application/octet-stream');
			//header('Content-type: application/x-msdownload'); // Excel?!?
			readfile($file);
			exit();
		} else {
			throw new Exception(__('File does not exist.'));
		}
	}

}

if (ifsetor($_REQUEST['id']) == DownloadObfuscator::page && ifsetor($_REQUEST['type']) == DownloadObfuscator::type) {
	$subid = intval($_REQUEST['subid']);
	if ($subid) {
		$extPath = t3lib_extMgm::extPath('submission');
		require_once($extPath . '/lib/config.php');
		require_once($extPath . '/lib/class.collection.php');
		require_once($extPath . '/lib/class.utilities.php');
		require_once($extPath . '/pi1/class.tx_submission_pi1.php');
		$pi1 = new tx_submission_pi1();
		$pi1->initNadlib();
		$pi1->init();
		$sub = new user_SubmissionUtils($subid);
		$obfuscator = new DownloadObfuscator($sub, $_REQUEST['fileSuffix']);
		if ($obfuscator->checkHash($_REQUEST['check'])) {
			$obfuscator->streamFile();
		} else {
			echo 'Hash check failed. Hacking?';
		}
	} else {
		echo '"subid" parameter missing.';
	}
}
