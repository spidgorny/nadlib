<?php

class ChangePassword extends HTMLFormProcessor
{
	protected $minLength = 8;
	protected $submitButton = 'Change';

	function getDesc()
	{
		$desc = array();
		$desc['password']['label'] = __('Password');
		$desc['password']['append'] = __('Min: %s chars.', $this->minLength);
		$desc['password']['type'] = 'password';
		$desc['repeat']['label'] = __('Repeat again');
		$desc['repeat']['type'] = 'password';
		return $desc;
	}

	function onSuccess(array $data)
	{
		$content = '';
		if (strlen($data['password']) >= 6) {
			if ($data['password'] == $data['repeat']) {
				$this->user->updatePassword($data['password']);
				$content .= '<div class="message">' . __('Password changed.') . '</div>';
			} else {
				throw new Exception(__('Passwords mismatch. Please try again.'));
			}
		} else {
			throw new Exception(__('Minimum password length is %s characters.', $this->minLength));
		}
		return $content;
	}

}
