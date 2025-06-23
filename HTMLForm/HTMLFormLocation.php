<?php

class HTMLFormLocation extends HTMLFormType
{

	/**
	 * @var AutoLoad
	 */
	public $al;

	public $size;

	/**
	 * @param string|string[] $field
	 * @param string $value
	 */
	public function __construct($field, $value = '')
	{
//		parent::__construct();
		$this->field = is_array($field) ? $field : [$field];
		$this->setValue($value);
		$this->al = AutoLoad::getInstance();
	}

	/**
	 * @param int|string $value
	 */
	public function setValue($value): void
	{
		$this->value = $value;
	}

	public function render(): string|array
	{
		$content = [];
		$content[] = $this->form->getInput('input', $this->field, $this->value, [
			'class' => ifsetor($this->desc['class']),
		], ifsetor($this->desc['class']));

		if ($this->value) {
//			$map = new StaticMapOSM($this->value);
//			$map = new StaticMapGM($this->value);
//			$config = Config::getInstance();
//			$map = $config->getStaticMapMQ($this->value);
//			if ($this->size) {
//				$map->size = $this->size;
//			}

//			$content[] = '<div class="text-center p-3">' . $map->render() . '</div>';
		}

		return $content;
	}

}
