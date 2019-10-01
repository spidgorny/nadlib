<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 22.12.2015
 * Time: 13:37
 */

class MailerTest extends PHPUnit_Framework_TestCase
{

	public function test_getShortFilename()
	{
		$sut = new Mailer('spidgorny@gmail.com', 'test', 'test');
		$filename = './request/97777/RP Nintendo EA3P40 The Legend of Zelda Tri Force Heroes Checklists.zip';
		$short = $sut->getShortFilename($filename);
		$this->assertEquals('RP_Nintendo_EA3P40_The_Legend_of_Zelda_Tri_Force_Heroes_Che.zip', $short);
	}

	public function test_getShortFilename2()
	{
		$sut = new Mailer('spidgorny@gmail.com', 'test', 'test');
		$filename = './request/97777/RP Nintendo EA3P40 The Legend of Соме Рашшан Шит.zip';
		$short = $sut->getShortFilename($filename);
		$this->assertEquals('RP_Nintendo_EA3P40_The_Legend_of_.zip', $short);
	}

	public function test_getShortFilename3()
	{
		// from RequestInfoEPES?id=102865
		$fixture = [
			'VC Pokemon Local Play Compatibility.pdf' => 'VC_Pokemon_Local_Play_Compatibility.pdf',
			'CL Nintendo QBFA11 Pokémon Yellow.pdf' => 'CL_Nintendo_QBFA11_Pokmon_Yellow.pdf',
			'CP_QBFA11.zip' => 'CP_QBFA11.zip',
			'RP Nintendo QBFA11 Pokémon Yellow Version.doc' => 'RP_Nintendo_QBFA11_Pokmon_Yellow_Version.doc',
			'RP Nintendo QBFA11 Pokémon Yellow Version.pdf' => 'RP_Nintendo_QBFA11_Pokmon_Yellow_Version.pdf',
		];
		$sut = new Mailer('spidgorny@gmail.com', 'test', 'test');
		foreach ($fixture as $filename => $must) {
			$short = $sut->getShortFilename($filename);
			$this->assertEquals($must, $short);
		}
	}

}
