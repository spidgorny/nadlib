<?php

/**
 * Class PageBase - Renders a TYPO3 page
 */

class PageBase extends AppController {

	/**
	 * @var TYPO3Page|PageBase
	 */
	var $page;

	function __construct() {
		parent::__construct();

		$id = $this->request->getNameless(1);
		$this->t3page = new TYPO3Page($id);

		$slug = $this->request->getNameless(2);
		$slug = ucfirst($slug);
		$className = 'Page_'.$slug;
		if (class_exists($className)) {
			$this->page = new $className();
			if (method_exists($this->page, 'postInit')) {
				$this->page->postInit();
			}
		} else {
			$this->page = $this->t3page;
		}
	}

	function render() {
		//debug($this->t3page->data);
		if ($this->t3page->data['abstract']) {
			$this->index->header['abstract'] = '<meta name="abstract" content="'.
				htmlspecialchars($this->t3page->data['abstract']).'" />';
		}
		if ($this->t3page->data['description']) {
			$this->index->header['description'] = '<meta name="description" content="'.
				htmlspecialchars($this->t3page->data['description']).'" />';
		}
		if ($this->t3page->data['keywords']) {
			$this->index->header['keywords'] = '<meta name="keywords" content="'.
				htmlspecialchars($this->t3page->data['keywords']).'" />';
		}
		$content = $this->page->render();
		$this->layout = $this->page->layout;	// for Search in Page_Cruises
		$this->title = $this->page->title;
		return $content;
	}

	function sidebar() {
		$content = $this->page->sidebar();
		return $content;
	}

}
