<?php

class HTMLFormDropZone extends HTMLFormType implements HTMLFormFieldInterface {

	/**
	 * Shows the form element in the form
	 * @return mixed
	 */
	function render() {
		$this->form->formMore['class'] .= ' dropzone';
		$index = Index::getInstance();
		$index->addJS('vendor/enyo/dropzone/dist/min/dropzone.min.js');
		$index->addCSS('vendor/enyo/dropzone/dist/min/basic.min.css');

		$u = new Uploader();
		$form = $u->getUploadForm();
		$content[] = $form->getBuffer();
		return $content;
	}

}
