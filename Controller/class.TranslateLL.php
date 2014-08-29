<?php

/**
 * Class TranslateLL
 * TODO: Combine with Localize
 * No, don't combine and this is used to localize a SINGLE message.
 * Admins get here by using clickTranslate.js
 */
class TranslateLL extends HTMLFormProcessor {
	protected $submitButton = 'Update';

	function __construct() {
		parent::__construct();
		$this->user = Config::getInstance()->user;
		if (!$this->user || !$this->user->id || !$this->user->isAdmin()) {
			throw new AccessDeniedException();
		}
	}

	function getDesc() {
		$ll = Index::getInstance()->ll;
		$code = $this->request->getTrim('code');
		$desc = array(
			'lang' => array(
				'label' => __('Lang'),
				'more' => array(
					'readonly' => "1",
				),
				'value' => $ll->lang,
			),
			'code' => array(
				'label' => __('Code'),
				'more' => array(
					'readonly' => "1",
				),
				'value' => $code,
			),
			'text' => array(
				'label' => __('Trans'),
				'type' => 'textarea',
				'value' => $ll->ll[$code],
			),
		);
		return $desc;
	}

	function render() {
		$content = parent::render();
		$content .= '<iframe
			src="http://dict.leo.org/ende?search='.
			urlencode($this->request->getTrim('code')).'"
			width="100%"
			height="500"></iframe>';
		return $content;
	}

	function onSuccess(array $data) {
		$ll = Index::getInstance()->ll;
		$ll->updateMessage($data);
		$content = '<div class="message">Updated.</div>';
		$content .= '<script>
			window.opener.location.href = window.opener.location.href;
			window.close();
		</script>';
		//debug($ll->ll, json_encode($ll->ll));
		return $content;
	}

}
