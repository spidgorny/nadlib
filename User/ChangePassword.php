<?php

class ChangePassword extends HTMLFormProcessor
{

	public $title = 'Change Password';
	protected $minLength = 8;
	protected $submitButton = 'Change';

	public function getDesc()
	{
		$desc = [];
		$desc['action'] = [
			'type' => 'hidden',
			'value' => 'changePassword',
		];
		$desc['current'] = [
			'label' => 'Current password',
			'type' => 'password',
		];
		$desc['password']['label'] = __('Password');
		$desc['password']['append'] = __('Min: %s chars.', $this->minLength);
		$desc['password']['type'] = 'password';
		$desc['password']['minlen'] = $this->minLength;
		$desc['repeat']['label'] = __('Repeat again');
		$desc['repeat']['type'] = 'password';
		return $desc;
	}

	public function onSuccess(array $data)
	{
		$content = '';
		if (strlen($data['password']) >= $this->minLength) {
			if ($data['password'] === $data['repeat']) {
				$ok = $this->user->updatePassword($data['password']);
				if (!$ok['error']) {
					$content .= $this->success(__('Password changed.'));
				} else {
					$content .= $this->error($ok['message']);
				}
			} else {
				throw new \RuntimeException(__('Passwords mismatch. Please try again.'));
			}
		} else {
			throw new \RuntimeException(__('Minimum password length is %s characters.', $this->minLength));
		}
		return $content;
	}

}
