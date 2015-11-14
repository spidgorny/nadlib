<?php

class ChangePassword extends HTMLFormProcessor {
	protected $minLength = 8;
	protected $submitButton = 'Change';
	public $title = 'Change Password';

	function getDesc() {
		$desc = array();
		$desc['action'] = array(
			'type' => 'hidden',
			'value' => 'changePassword',
		);
		$desc['password']['label'] = __('Password');
		$desc['password']['append'] = __('Min: %s chars.', $this->minLength);
		$desc['password']['type'] = 'password';
		$desc['password']['minlen'] = $this->minLength;
		$desc['repeat']['label'] = __('Repeat again');
		$desc['repeat']['type'] = 'password';
		return $desc;
	}

	function onSuccess(array $data) {
		$content = '';
		if (strlen($data['password']) >= $this->minLength) {
			if ($data['password'] == $data['repeat']) {
				$this->user->updatePassword($data['password']);
				$content .= '<div class="message">'.__('Password changed.').'</div>';
			} else {
				throw new Exception(__('Passwords mismatch. Please try again.'));
			}
		} else {
			throw new Exception(__('Minimum password length is %s characters.', $this->minLength));
		}
		return $content;
	}

}
