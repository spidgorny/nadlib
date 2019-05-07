<?php

namespace nadlib\HTML;

class Messages extends \MergedContent
{

	function message($text)
	{
		$msg = '<div class="message alert alert-info ui-state-message alert alert-notice padding">' . $text . '</div>';
		if (is_array($this->content)) {
			$this->content[] = $msg;
		} else {
			$this->content .= $msg;
		}
	}

	function error($text)
	{
		$msg = '<div class="error error_top ui-state-error alert alert-error alert-danger padding">' . $text . '</div>';
		if (is_array($this->content)) {
			$this->content[] = $msg;
		} else {
			$this->content .= $msg;
		}
	}

	function success($text)
	{
		$msg = '<div class="alert alert-success padding">' . $text . '</div>';
		if (is_array($this->content)) {
			$this->content[] = $msg;
		} else {
			$this->content .= $msg;
		}
	}

	function info($text)
	{
		$msg = '<div class="alert alert-info padding">' . $text . '</div>';
		if (is_array($this->content)) {
			$this->content[] = $msg;
		} else {
			$this->content .= $msg;
		}
	}

	function saveMessages()
	{
		$_SESSION[__CLASS__]['messages'] = $this->content;
	}

	function restoreMessages()
	{
		if (isset($_SESSION[__CLASS__])) {
			$this->content = $_SESSION[__CLASS__]['messages'];
			$_SESSION[__CLASS__]['messages'] = array();
		}
	}

}
