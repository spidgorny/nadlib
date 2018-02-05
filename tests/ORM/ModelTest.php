<?php
/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-07-19
 * Time: 23:17
 */


class ModelTest extends PHPUnit_Framework_TestCase {

	function test_getFormFromModel()
	{
		$cm = new CareerModel(new dbPlacebo());
		$form = $cm->getFormFromModel();
//		var_export($form);
		$this->assertEquals([
			'name' => [
				'label' => 'Career name',
				'type'  => 'text',
				'optional' => false,
			],
			'note' => [
				'label' => 'Note',
				'type'  => 'textarea',
				'optional' => true,
			],
		], $form);
	}

	function test_isset_empty_array()
	{
		$a['k'] = NULL;
		$this->assertFalse(isset($a['k']));
		$this->assertTrue(array_key_exists('k', $a));
	}

	function test_getFormFromModel2()
	{
		$cm = new ApplicationModel(new dbPlacebo());
		$form = $cm->getFormFromModel();
//		var_export($form);
		$this->assertEquals([
			'name' => [
				'label' => 'Job Title',
				'type'  => 'text',
				'optional' => false,
			],
			'note' => [
				'label' => 'Notes',
				'type'  => 'textarea',
				'optional' => true,
			],
			'url' => [
				'label' => 'URL',
				'type' => 'url',
				'optional' => true,
			]
		], $form);
	}

}
