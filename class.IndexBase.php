<?php

class IndexBase {
	/**
	 * Enter description here...
	 *
	 * @var MySQL
	 */
	public $db;

	/**
	 * Enter description here...
	 *
	 * @var LocalLangDummy
	 */
	public $ll;

	/**
	 * Enter description here...
	 *
	 * @var User
	 */
	public $user;

	/**
	 * For any error messages during initialization.
	 *
	 * @var unknown_type
	 */
	public $content = '';

	function __construct() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$this->db = Config::getInstance()->db;
		$this->ll = new LocalLangDummy();
		$this->request = new Request();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	function render() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		try {
			$class = $this->request->getController();
			$obj = new $class;
			$content .= $obj->render();
			$content = $this->content . $content;
		} catch (LoginException $e) {
			require('template/head.phtml');
			$content .= '<div class="headerMargin"></div>';
			$content .= '
			<div class="ui-state-error padding">
				'.$e->getMessage();
			$content .= '</div>';
			$loginForm = new LoginForm();
			$content .= $loginForm->render();
		} catch (Exception $e) {
			if (!$_REQUEST['ajax']) {
				require('template/head.phtml');
				$content .= '<div class="headerMargin"></div>';
			}
			$content .= '
			<div class="ui-state-error padding">
				'.$e->getMessage();
			if ($GLOBALS['i']->user->id < -3) {
				$content .= '<br>'.nl2br($e->getTraceAsString());
			}
			$content .= '</div>';
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $content;
	}

	function getURL(array $params) {
		return '?'.http_build_query($params);
	}

	function destruct() {
		if (is_object($this->user)) {
			$this->user->destruct();
		}
	}

	function log($action, $bookingID) {
		$qb = new SQLBuilder();
		$qb->runInsertQuery('log', array(
			'who' => $this->user->id,
			'action' => $action,
			'booking' => $bookingID,
		));
	}

}
