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

	public function test_getMaxPage()
	{
		$fixture = [
			0 => 0,
			1 => 0,
			5 => 0,
			10 => 0,
			11 => 1,
			15 => 1,
			19 => 1,
			20 => 1,
			21 => 2,
		];
		foreach ($fixture as $records => $maxPage) {
			echo '> ', $records, ' => ', $maxPage, PHP_EOL;
			echo TAB, $records/10, ' => ', ceil($records/10), PHP_EOL;
			$p = new Pager();
			$p->setItemsPerPage(10);
			$p->setNumberOfRecords($records);
			echo TAB, TAB, $p->getMaxPage(), PHP_EOL;
			$this->assertEquals($maxPage, $p->getMaxPage());
		}
	}

}
