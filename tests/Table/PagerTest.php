<?php


class PagerTest extends PHPUnit\Framework\TestCase
{

	public function testDetectCurrentPage()
	{
		$_REQUEST['Pager.'] = ['page' => 2];
		$p = new Pager();
		$p->setNumberOfRecords(100);
		$p->detectCurrentPage();
		$this->assertEquals(2, $p->currentPage);
	}
}
