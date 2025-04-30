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

	/**
	 * @var mixed[]
	 */
	public $data = [];

	public $thes = [];

	/**
	 * @var mixed[]
	 */
	public $attributes = [];

	/**
	 * HTMLTable constructor.
	 *
	 * @param array $res
	 */
	public function __construct(array $res, array $thes = [], array $attributes = [])
	{
		$this->data = $res;
		$this->thes = $thes;
		$this->attributes = $attributes;
	}

	public function __toString(): string
	{
		if (!$this->thes) {
			$this->genThes();
		}

		return implode(PHP_EOL, $this->table());
	}

	public function genThes(): void
	{
		$col1 = current($this->data);
		$this->thes = $col1;
	}

	public function table()
	{
		$attributes = HTMLTag::renderAttr($this->attributes);
		$content['table'] = sprintf('<table %s>', $attributes);
		$content['thead'] = '<thead><tr>';
		foreach (array_keys((array)$this->thes) as $key) {
			$content['th.' . $key] = '<th>' . htmlspecialchars($key) . '</th>';
		}

		$content['/thead'] = '</tr></thead>';
		$content['tbody'] = '<tbody>';
		foreach ($this->data as $row) {
			$content[] = $this->row((array)$row);
		}

		$content['/tbody'] = '<tbody>';
		$content['/table'] = '</table>';
		return $content;
	}

	public function row(array $row): string
	{
		$content[] = '<tr>';
		foreach ($this->thes as $key => $_) {
			$cell = ifsetor($row[$key]);
			if ($cell instanceof HTMLTag) {
			} elseif ($cell instanceof HtmlString) {
			} else {
				$cell = htmlspecialchars($cell ?? '');
			}

			$content[] = '<td>' . $cell . '</td>';
		}

		$content[] = '</tr>';

		return implode(PHP_EOL, $content);
	}

}
