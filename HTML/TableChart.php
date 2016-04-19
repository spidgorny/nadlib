<?php

class TableChart {

	var $rows;
	var $y;
	var $x;

	function __construct(array $rows, $y, $x = NULL) {
		$this->rows = $rows;
		$this->y = $y;
		$this->x = $x;
	}

	function render() {
		$max = 0;
		foreach ($this->rows as $row) {
			$max = max($max, $row[$this->y]);
		}

		$content = [];
		foreach ($this->rows as $row) {
			$h = number_format($row[$this->y]/$max*300, 2);
			$content[] = '<td style="border: solid 1px silver; vertical-align: bottom">
				<div style="background: lightblue; height: '.$h.'px"></div>
			</td>';
		}
		$content = [
			'<table width="100%" style="height: 300px; border: solid 1px silver">',
			$content,
			'</table>',
		];
		return $content;
	}

}
