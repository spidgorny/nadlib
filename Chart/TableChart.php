<?php

/**
 * Class TableChart - renders an HTML table with cell heights corresponding to data in %
 */
class TableChart
{

	/**
	 * @var mixed[]
	 */
	public $rows;

	public $y;

	public $x;

	/**
	 * @var HTML
	 */
	public $html;

	/**
	 * @var callable|null
	 */
	public $linkGenerator;

	public function __construct(array $rows, $y, $x = null)
	{
		$this->rows = $rows;
		$this->y = $y;
		$this->x = $x;
		$this->html = new HTML();
	}

	public function render(): array
	{
		$max = 0;
		foreach ($this->rows as $row) {
			$max = max($max, $row[$this->y]);
		}

		$content = [];
		foreach ($this->rows as $row) {
			$h = number_format($row[$this->y] / $max * 300, 2);
			$div = '<div style="background: lightblue; height: ' . $h . 'px"></div>';
			if ($this->linkGenerator) {
				$div = call_user_func($this->linkGenerator, $row, $h, $div);
			}

			$content[] = '<td style="border: solid 1px silver; vertical-align: bottom">
				' . $div . '
			</td>';
		}

		return [
			'<table width="100%" style="height: 300px; border: solid 1px silver">',
			$content,
			'</table>',
		];
	}

	public function __toString(): string
	{
		return $this->html->s($this->render());
	}

}
