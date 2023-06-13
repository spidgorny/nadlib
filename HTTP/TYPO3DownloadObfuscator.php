<?php

class TYPO3DownloadObfuscator extends DownloadObfuscator
{

	/**
	 * TYPO3 related
	 */
	const page = 18;

	/**
	 * TYPO3 related
	 */
	const type = 574;

	public $sub;

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

	public function test()
	{
		$samePage = ifsetor($_REQUEST['id']) == TYPO3DownloadObfuscator::page;
		$sameType = ifsetor($_REQUEST['type']) == TYPO3DownloadObfuscator::type;
		if ($samePage && $sameType) {
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
	}

}
