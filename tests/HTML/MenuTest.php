<?php


class MenuTest extends PHPUnit\Framework\TestCase
{

	public function test__construct()
	{
		$m = new Menu([
			'Page1' => 'Page 1',
		]);
		$html = $m->render();
		$this->assertContains('?c=Page1', $html);
	}

	public function test_recursive()
	{
		$m = new Menu([
			'Page1' => 'Page 1',
		]);
		$m->request = new MockRequest();
		$m->request->pathAfterAppRootByPath = '/level1';
//		debug($m->request->getPathAfterAppRootByPath());
		$this->assertEquals('/level1', $m->request->getPathAfterAppRootByPath());
		$levels = $m->request->getURLLevels();
//		debug($levels);
		$this->assertContains('level1', $levels);
		$rootPath = $m->getRootpath();
//		debug($rootPath);
		$this->assertContains('level1', $rootPath);
		$m->basePath->setDocumentRoot('level1');
		$m->useControllerSlug = true;
		$m->useRecursiveURL = true;
		$m->recursive = true;
		$m->renderOnlyCurrent = true;
		$m->basePath->reset();
		$m->setCurrent(0);
		$html = $m->render();
//		debug($html);
//		debug($m->debug());
		$this->assertContains('/level1/Page1', $html);
	}

	public function test_less_recursive()
	{
		$m = new Menu([
			'Page1' => 'Page 1',
		]);
		$m->request = new MockRequest();
		$m->request->pathAfterAppRootByPath = '/level1';
//		debug($m->request->getPathAfterAppRootByPath());
		$this->assertEquals('/level1', $m->request->getPathAfterAppRootByPath());
		$levels = $m->request->getURLLevels();
//		debug($levels);
		$this->assertContains('level1', $levels);
		$rootPath = $m->getRootpath();
//		debug($rootPath);
		$this->assertContains('level1', $rootPath);
		$m->useControllerSlug = true;
		$m->useRecursiveURL = true;
		$m->recursive = false;
		$m->renderOnlyCurrent = true;
		$m->basePath->reset();
		$m->setCurrent(null);
		$html = $m->render();
//		debug($m->debug());
		$this->assertContains('localhost/Page1', $html);
	}

	public function test_getClassPath()
	{
		$m = new Menu([]);
		$m->useControllerSlug = false;

		$path1 = $m->getClassPath('Class1', []);
//		debug($path1.'');
		$this->assertEquals('http://localhost/?c=Class1', $path1);

		$path1 = $m->getClassPath('http://someshit/', []);
//		debug($path1.'');
		$this->assertEquals('http://someshit/', $path1);

		$m->basePath->reset();
//		debug($m->basePath);
		$m->useControllerSlug = true;
//		$m->useRecursiveURL = true;
		$path1 = $m->getClassPath('Class1', ['level1']);
//		debug($path1.'');
		$this->assertEquals('http://localhost/level1/Class1', $path1.'');
	}

}
