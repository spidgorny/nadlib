<?php

namespace HTMLForm;

use HTMLForm;
use HTMLFormTable;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\AbstractNode;
use PHPUnit\Framework\TestCase;
use tidy;

/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-03-22
 * Time: 16:26
 */
class HTMLFormTableTest extends TestCase
{

	public function test_fillValues(): void
	{
		$f = new HTMLFormTable([
			'name' => 'Name',
			'email' => 'E-mail',
		]);
		$fixture = [
			'name' => 'slawa',
			'email' => 'someshit',
			'new_field' => 'new_values',
		];
		$f->fill($fixture);
		$values = $f->getValues();
//		debug($values);
		unset($fixture['new_field']);
		static::assertEquals($fixture, $values);
	}

	public function test_fillValues_with_force(): void
	{
		$f = new HTMLFormTable([
			'name' => 'Name',
			'email' => 'E-mail',
		]);
		$fixture = [
			'name' => 'slawa',
			'email' => 'someshit',
			'new_field' => 'new_values',
		];
		$f->fill($fixture, true);
		$values = $f->getValues();
		unset($fixture['new_field']);
		static::assertEquals($fixture, $values);
	}

	public function test_fillValues_twice(): void
	{
		$f = new HTMLFormTable([
			'name' => 'Name',
			'email' => 'E-mail',
		]);
		$fixture = [
			'name' => 'slawa',
			'email' => 'someshit',
		];
		$f->fill($fixture, true);
		$fixture = [
			'name' => 'slawa 2',
			'email' => 'someshit ',
		];
		$f->fill($fixture, true);
		$values = $f->getValues();
		static::assertEquals($fixture, $values);
	}

	public function test_hidden()
	{
		$form = new HTMLForm();
		$html = $form->hidden('field', 'value');
		static::assertStringContainsString('<input type="hidden"', $html);
	}

	public function test_htmlspecialchars(): void
	{
		$f = new HTMLFormTable([
			'field' => [
				'value' => 'asd'
			]
		]);
		$f->showForm();

		$html = $f->getContent();
//		echo $html;
		static::assertStringContainsString('value="asd"', $html);

		$f = new HTMLFormTable([
			'field' => [
				'value' => 'asd & "qwe"'
			]
		]);
		$f->showForm();

		$html = $f->getContent();
//		echo $html;
		static::assertStringContainsString('value="asd &amp; &quot;qwe&quot;"', $html);
	}

	public function test_showCell()
	{
		$form = new HTMLFormTable();
		$html = $form->showCell(['prefix'], [
			'type' => 'hidden',
			'value' => 'asd',
		]);
		static::assertStringContainsString('<input type="hidden"', $html);
	}

	public function test_showTR()
	{
		$form = new HTMLFormTable();
		$html = $form->showTR(['prefix'], [
			'type' => 'hidden',
			'value' => 'asd',
		]);
		static::assertStringContainsString('<input type="hidden"', $html);
	}

	/**
	 * @throws \JsonException
	 */
	public function test_change_status_form_tags()
	{
		$f = new HTMLFormTable([
			'newcomment' => [
				'label' => 'Comment',
				'type' => 'textarea',
				'more' => [
					'class' => "input-medium ctrlSubmit w-100",
					// 'onkeyup' => 'ors.ctrlSubmit(this)',
					'onfocus' => 'ors.focusComment(this)',
					'onblur' => 'ors.blurComment(this)',
					'style' => [
						'resize' => 'vertical',
					],
					'required' => true,
					'value' => 'asd'
				],
			],
			'oldstatus' => [
				'type' => 'hidden',
				'value' => 'asd',
			],
			'newstatus' => [
				'label' => 'Status',
				'more' => [
					'class' => "input-medium",
				],
				'type' => 'select',
				'options' => ['asd'],
				'value' => ['asd'],
			],
			'is_private' => [
				'label' => 'Is Private (for EPES only)',
				'type' => 'checkbox',
			],
			'submit' => [
				'value' => 'Post',
				'type' => 'submit',
			],
		]);
		$f->tableMore['class'] = "w-full";
//		$f->fieldset(__('Add new comment'));
		$f->method(HTMLForm::METHOD_POST);
		//debug($this->request->getMethod());
		$f->hidden('action', 'postComment');
		$f->defaultBR = true;
		$f->showForm();
		$html = $f->getContent();
//		llog($html);

		$tidy = new Tidy();
		$tidy->parseString($html, [
			'indent' => true,
			'output-xhtml' => true,
			'wrap' => 200
		], 'utf8');
		$tidy->cleanRepair();
		echo $tidy->html()->value;

		$dom = new Dom();
		$dom->loadStr($html);
//		echo $dom->root->outerHtml();
		$structure = $this->getTagStructure($dom->root);
		echo json_encode($structure, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
		static::assertStringNotContainsString('<tr></tr>', $dom->root->outerHtml());
	}

	protected function getTagStructure(AbstractNode $dom)
	{
		return collect($dom->find('*'))->map(function (AbstractNode $x) {
			$children = $x->find('*')?->toArray();
			if (count($children) > 0) {
				return [
					$x->getTag()->name() . ':' . count($children) => $this->getTagStructure($x)
				];
			}

			return $x->getTag()->name();
		})->all();
	}

}
