<?php

class SimpleMenu extends Menu
{

	public function render(): string
	{
		$content = '<ul>' . PHP_EOL;
		$items = $this->items;
		foreach ($items as $class => $name) {
			$link = HTMLTag::a(cap($this->basePath) . $class, $name);
			$content .= '<li>' . $link . '</li>' . PHP_EOL;
		}

		return $content . ('</ul>' . PHP_EOL);
	}
}
