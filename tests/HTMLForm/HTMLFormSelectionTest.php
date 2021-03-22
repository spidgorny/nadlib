<?php


use PHPUnit\Framework\TestCase;

class HTMLFormSelectionTest extends TestCase
{

	protected function trim($string)
	{
		return trim(implode(PHP_EOL, explode("\n", $string)));
	}

	public function testRender_with_more()
	{
		$fs = new HTMLFormSelection('field');
		$html = $fs->render();
		$this->assertEquals('<select  name="field" required="1">
</select>', $this->trim($html));

		$fs = new HTMLFormSelection('field');
		$fs->setDesc([
			'id' => 'id1',
			'data-x' => 'y'
		]);
		$html = $fs->render();
		$this->assertEquals('<select  name="field" id="id1" required="1">
</select>', $this->trim($html));
	}

	public function test_render_from_html_form()
	{
		if (Request::isJenkins()) {
			$this->markTestSkipped();
		}
		$fieldName = 'exception';
		$f = new HTMLForm();
		$f->selection(
			'updateGame[' . $fieldName . 'Department]',
			Department::getOptions(),
			'',
			false,
			[
				'id' => 'game-' . $fieldName . '-Department',
				'style' => 'width: 100%',
			]
		);
		$depSelect = $f->getBuffer();
		$firstLine = trimExplode("\n", $depSelect)[0];
		$this->assertEquals('<select  name="updateGame[exceptionDepartment]" id="game-exception-Department" style="width: 100%" required="1">', $this->trim($firstLine));
	}

}
