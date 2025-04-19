<?php


class MenuTest extends PHPUnit\Framework\TestCase
{

	public function test__construct(): void
	{
		$m = new Menu([
			'Page1' => 'Page 1',
		]);
		$html = $m->render();
		$this->assertStringContainsString('?c=Page1', $html);
	}

	public function test_recursive(): void
	{
		$m = new Menu([
			'Page1' => 'Page 1',
		]);
//		pre_print_r(['useRouter' => $m->useRouter()]);
//		$this->assertFalse($m->useRouter());
//		pre_print_r(['useControllerSlug' => $m->useControllerSlug]);
		$this->assertFalse($m->useControllerSlug);

		$m->basePath->setDocumentRoot('');
//		pre_print_r($m->basePath);
		$this->assertEquals('', $m->basePath->documentRoot);

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
		$this->assertStringContainsString('/level1/Page1', $html);
	}

	public function test_less_recursive(): void
	{
		$localhost = gethostname() ?: 'localhost';
		$m = new Menu([
			'Page1' => 'Page 1',
		]);
		$m->basePath->setDocumentRoot('');
		$m->request = new MockRequest();
		$m->request->pathAfterAppRootByPath = '/level1';
//		pre_print_r($m->request->getPathAfterAppRootByPath());
		$this->assertEquals('/level1', $m->request->getPathAfterAppRootByPath());
		$levels = $m->request->getURLLevels();
//		pre_print_r($levels);
		$this->assertContains('level1', $levels);
		$rootPath = $m->getRootpath();
//		pre_print_r($rootPath);
		$this->assertContains('level1', $rootPath);
		$m->useControllerSlug = true;
		$m->useRecursiveURL = true;
		$m->recursive = false;
		$m->renderOnlyCurrent = true;
		$m->basePath->reset();
		$m->setCurrent(null);

		$link = $m->getClassPath('level2', $m->getRootpath());
//		pre_print_r($link);
		$this->assertEquals(sprintf('http://%s/level1/level2', $localhost), $link);

		$level = $m->renderLevel((array)$m->items, $m->getRootpath());
//		pre_print_r($level);
		$this->assertStringContainsString(sprintf('http://%s/level1/Page1', $localhost), $level);

		$html = $m->render();
//		debug($m->debug());
		$this->assertStringContainsString($localhost . '/Page1', $html);
	}

	public function test_getClassPath(): void
	{
		$localhost = gethostname() ?: 'localhost';
		$m = new Menu([]);
		$m->basePath->setDocumentRoot('');
		$m->useControllerSlug = false;

		$path1 = $m->getClassPath('Class1', []);
//		debug($path1.'');
		$this->assertEquals(sprintf('http://%s/?c=Class1', $localhost), $path1 . '');

		$path1 = $m->getClassPath('http://someshit/', []);
//		debug($path1.'');
		$this->assertEquals('http://someshit/', $path1 . '');

		$m->basePath->reset();
//		debug($m->basePath);
		$m->useControllerSlug = true;
//		$m->useRecursiveURL = true;
		$path1 = $m->getClassPath('Class1', ['level1']);
//		debug($path1.'');
		$this->assertEquals(sprintf('http://%s/level1/Class1', $localhost), $path1 . '');
	}

}
