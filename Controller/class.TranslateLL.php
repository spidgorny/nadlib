<?php

class TranslateLL extends HTMLFormProcessor {
	protected $submitButton = 'Update';

	function getDesc() {
		$ll = Index::getInstance()->ll;
		$code = $this->request->getTrim('code');
		$desc = array(
			'lang' => array(
				'label' => __('Lang'),
				'more' => 'disabled="1"',
				'value' => $ll->lang,
			),
			'code' => array(
				'label' => __('Code'),
				'more' => 'disabled="1"',
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