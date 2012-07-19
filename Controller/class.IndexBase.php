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
	 * @var LoginUser
	 */
	public $user;

	/**
	 * For any error messages during initialization.
	 *
	 * @var string
	 */
	public $content = '';

	/**
	 * @var Controller
	 */
	public $controller;

	/**
	 * @var Index
	 */
	public static $instance;

	protected function __construct() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		if ($_REQUEST['d'] == 'log') echo __METHOD__."<br />\n";
		$this->db = Config::getInstance()->db;
		$this->ll = new LocalLangDummy();
		$this->request = new Request();
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
	}

	static function getInstance() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$instance = &self::$instance;
		if (!$instance) {
			if ($_REQUEST['d'] == 'log') echo __METHOD__."<br />\n";
			$instance = new Index();
			$instance->content .= $instance->initController();
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $instance;
	}

	protected function initController() {
		if ($_REQUEST['d'] == 'log') echo __METHOD__."<br />\n";
		try {
			$class = $this->request->getControllerString();
			if (class_exists($class)) {
				$this->controller = new $class;
			} else {
				$this->controller = NULL;
				throw new Exception('Class '.$class.' not found.');
			}
		} catch (Exception $e) {
			$this->controller = NULL;
			$content = $this->renderException($e);
		}
		return $content;
	}

	function render() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$content = '';
		if ($this->controller) {
			try {
				$this->content .= $this->controller->render();
				$v = new View('template.phtml', $this);
				$content = $v->render();
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
				$content = $this->renderException($e);
			}
		} else {
			$content = $this->content;	// display Exception
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $content;
	}

	function renderException(Exception $e) {
		$content = '<div class="ui-state-error padding">
			'.$e->getMessage();
		if (DEVELOPMENT) {
			$content .= '<br />'.nl2br($e->getTraceAsString());
		}
		$content .= '</div>';
		$content .= '<div class="headerMargin"></div>';

		if (!$this->request->isAjax()) {
			$v = new View('template.phtml', $this);
			$v->content = $content;
			$content = $v;
		}

		return $content;
	}

	function destruct() {
		if (is_object($this->user)) {
			$this->user->destruct();
		}
	}

	function log($action, $bookingID) {
		$qb = Config::getInstance()->qb;
		$qb->runInsertQuery('log', array(
			'who' => $this->user->id,
			'action' => $action,
			'booking' => $bookingID,
		));
	}

}
