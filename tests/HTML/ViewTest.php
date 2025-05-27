<?php

namespace HTML;

use AppDev\OnlineRequestSystem\Framework\TestCase;
use HTMLProcessor;
use MockController;
use MockIndexDCI;
use View;

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 10.12.2015
 * Time: 21:20
 */
class ViewTest extends TestCase
{

	public function test_render(): void
	{
		$v = new View(__DIR__ . '/ViewTemplate.phtml');
		$v->content = 'asd';

		$content = $v->render();
		static::assertContains('asd', $content);
	}

	public function test_cleanComment(): void
	{
		if (class_exists('HTMLPurifier_Config')) {
			$v = new HTMLProcessor('whatever');
			$clean = $v->cleanComment('Some shit');
			static::assertNotEmpty($clean);
		} else {
			static::markTestSkipped('HTMLPurifier_Config');
		}
	}

	public function test_extractScripts(): void
	{
		if (!class_exists('AdvancedHtmlDom')) {
			static::markTestSkipped('AdvancedHtmlDom not installed');
		}

		$html = '<html><h1>bla</h1>
<script>
alert("xss");
</script>
<div>bla</div></html>';
		$view = new View('');
		$view->setHTML($html);

		$scripts = $view->extractScripts();
		//debug($scripts);
		static::assertEquals("<h1>bla</h1>
<div>bla</div>", $view->render());
		static::assertEquals('<script>
alert("xss");
</script>', $scripts);
	}

	/**
	 * @throws \Exception
	 */
	public function test_double(): void
	{
		new MockController();
		$i = new MockIndexDCI();
		$v = new View(__DIR__ . '/template.phtml', $i);
		$html = $v->render();
		ini_set('xdebug.var_display_max_data', -1);
//		var_dump($html);
		$countSidebar = substr_count($html, '<sidebar>');
		static::assertEquals(1, $countSidebar);
	}

}
