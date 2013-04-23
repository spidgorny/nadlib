<?php

/**
 * Class PageBase - Renders a TYPO3 page
 */

class PageBase extends AppController {

	/**
	 * @var TYPO3Page
	 */
	var $page;

	function __construct() {
		parent::__construct();
		$slug = $this->request->getNameless(2);
		$slug = ucfirst($slug);
		$className = 'Page_'.$slug;
		if (class_exists($className)) {
			$this->page = new $className();
			if (method_exists($this->page, 'postInit')) {
				$this->page->postInit();
			}
		} else {
			$id = $this->request->getNameless(1);
			$this->page = new TYPO3Page($id);
		}
	}

	function render() {
		if ($this->page->data['abstract']) {
			$this->index->header['abstract'] = '<meta name="abstract" content="'.
				htmlspecialchars($this->page->data['abstract']).'" />';
		}
		if ($this->page->data['description']) {
			$this->index->header['description'] = '<meta name="description" content="'.
				htmlspecialchars($this->page->data['description']).'" />';
		}
		if ($this->page->data['keywords']) {
			$this->index->header['keywords'] = '<meta name="keywords" content="'.
				htmlspecialchars($this->page->data['keywords']).'" />';
		}
		$content = $this->page->render();
		$this->layout = $this->page->layout;	// for Search in Page_Cruises
		return $content;
	}

	function sidebar() {
		$content = $this->page->sidebar();
		return $content;
	}

}
