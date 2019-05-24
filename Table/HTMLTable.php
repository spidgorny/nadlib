<?php
/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2018-11-28
 * Time: 00:17
 * This is like slTable but without the legacy code
 */

class HTMLTable
{

	public $data = [];

	public $thes = [];

	/**
	 * HTMLTable constructor.
	 *
	 * @param array|object|null $res
	 * @param array $thes
	 */
	public function __construct(array $res, array $thes = [])
	{
		$this->data = $res;
		$this->thes = $thes;
	}

	public function __toString()
	{
		if (!$this->thes) {
			$this->genThes();
		}
		return $this->table();
	}

	public function genThes()
	{
		$col1 = current($this->data);
		if ($col1) {
		}
		$this->thes = $col1;
	}

	public function table()
	{
		$content[] = '<table>';
		$content[] = '<thead><tr>';
		foreach ((array)$this->thes as $key => $_) {
			$content[] = '<th>' . htmlspecialchars($key) . '</th>';
		}
		$content[] = '</tr></thead>';
		$content[] = '<tbody>';
		foreach ($this->data as $row) {
			$content[] = $this->row((array)$row);
		}
		$content[] = '<tbody>';
		$content[] = '</table>';

		return implode(PHP_EOL, $content);
	}

	public function row(array $row)
	{
		$content[] = '<tr>';
		foreach ($this->thes as $key => $_) {
			$cell = $row[$key];
			$content[] = '<td>' . htmlspecialchars($cell) . '</td>';
		}
		$content[] = '</tr>';

		return implode(PHP_EOL, $content);
	}

}
