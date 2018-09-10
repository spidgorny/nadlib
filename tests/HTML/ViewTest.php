<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 10.12.2015
 * Time: 21:20
 */
class ViewTest extends PHPUnit_Framework_TestCase
{

	function test_render()
	{
		$v = new View(__DIR__ . '/ViewTemplate.phtml');
		$v->content = 'asd';
		$content = $v->render();
		$this->assertContains('asd', $content);
	}

	function test_cleanComment()
	{
		if (class_exists('HTMLPurifier_Config')) {
			$v = new View('whatever');
			$clean = $v->cleanComment('Some shit');
			$this->assertNotEmpty($clean);
		}
	}

	function test_extractScripts()
	{
		if (!class_exists('AdvancedHtmlDom')) {
			$this->markTestSkipped('AdvancedHtmlDom not installed');
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
		$this->assertEquals("<h1>bla</h1>
<div>bla</div>", $view->render());
		$this->assertEquals('<script>
alert("xss");
</script>', $scripts);
	}

}
