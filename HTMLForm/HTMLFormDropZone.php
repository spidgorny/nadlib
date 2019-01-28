<?php

use spidgorny\nadlib\HTTP\URL;

class HTMLFormDropZone extends HTMLFormType implements HTMLFormFieldInterface {

	var $makeFallback = true;

	var $class = 'dropzone';

	/**
	 * Shows the form element in the form
	 * @return mixed
	 * @throws Exception
	 */
	function render() {
		$content = [];
		$this->form->action(new URL(NULL, [
			'action' => 'upload',
		]));
		$this->form->formMore['class'] .= ' '.$this->class;
		$index = Index::getInstance();
		$index->addJS('vendor/enyo/dropzone/dist/min/dropzone.min.js');
		$index->addCSS('vendor/enyo/dropzone/dist/min/dropzone.min.css');

		if ($this->makeFallback) {
			//$u = new Uploader();
			//$form = $u->getUploadForm();
			$form = new HTMLForm();
			$form->text('<div class="fallback">');
			$form->file('file');
			$form->text('</div>');
			$content[] = $form->getBuffer();
		}
		return $content;
	}

}
