<?php
/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-07-19
 * Time: 23:17
 */

namespace ORM;


use AppDev\OnlineRequestSystem\Framework\TestCase;

class ModelTest extends TestCase
{

	public function _test_getFormFromModel(): void
	{
//		$cm = new CareerModel(new DBPlacebo());
//		$form = $cm->getFormFromModel();
////		var_export($form);
//		$this->assertEquals([
//			'name' => [
//				'label' => 'Career name',
//				'type' => 'text',
//				'optional' => false,
//			],
//			'note' => [
//				'label' => 'Note',
//				'type' => 'textarea',
//				'optional' => true,
//			],
//		], $form);
	}

	public function test_isset_empty_array(): void
	{
		$a['k'] = null;
		static::assertFalse(isset($a['k']));
		static::assertTrue(\array_key_exists('k', $a));
	}

	public function _test_getFormFromModel2(): void
	{
//		$cm = new ApplicationModel(new DBPlacebo());
//		$form = $cm->getFormFromModel();
////		var_export($form);
//		$this->assertEquals([
//			'name' => [
//				'label' => 'Job Title',
//				'type' => 'text',
//				'optional' => false,
//			],
//			'note' => [
//				'label' => 'Notes',
//				'type' => 'textarea',
//				'optional' => true,
//			],
//			'url' => [
//				'label' => 'URL',
//				'type' => 'url',
//				'optional' => true,
//			]
//		], $form);
	}

}
