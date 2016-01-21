<?php

class HTMLFormDropZone extends HTMLFormType implements HTMLFormFieldInterface {

	/**
	 * Shows the form element in the form
	 * @return mixed
	 */
	function render() {
		$this->form->action(new URL(NULL, [
			'action' => 'upload',
		]));
		$this->form->formMore['class'] .= ' dropzone';
		$index = Index::getInstance();
		$index->addJS('vendor/enyo/dropzone/dist/min/dropzone.min.js');
		$index->addCSS('vendor/enyo/dropzone/dist/min/dropzone.min.css');

		//$u = new Uploader();
		//$form = $u->getUploadForm();
		$form = new HTMLForm();
		$form->text('<div class="fallback">');
		$form->file('file');
		$form->text('</div>');
		$content[] = $form->getBuffer();
		return $content;
	}

}
