<?php

class UL implements ToStringable
{

	/**
	 * @var mixed[]
	 */
	public $items = [];

	public $before = '<ul>';

	public $after = '</ul>';

	public $wrap = '<li ###ACTIVE###>|</li>';

	/**
	 * Should be equal to an $this->items key which is selected
	 * @var string
	 */
	public $activeClass = '';

	/**
	 * Piece of HTML to mark active items
	 * @var string
	 */
	public $active = ' class="active"';

	public $links = [];

	/**
	 * <a href="###LINK###">|</a>
	 * @var string
	 */
	public $linkWrap = '';

	/**
	 * @var ?closure callback to link generation function(index, name)
	 */
	public $linkFunc;

	public function __construct(array $items = [])
	{
		$this->items = $items;
		$this->activeClass = key($this->items);
	}

	public static function DL(array $assoc): \UL
	{
		$ul = new UL($assoc);
		$links = array_keys($assoc);
		$ul->links = array_combine($links, $links);
		$ul->wrap = '<dt>###CLASS###</dt>|';
		$ul->linkWrap = '<dd>|</dd>';
		$ul->before = '<dl class="dl-horizontal">';
		$ul->after = '</dl>';
		return $ul;
	}

	public static function recursive(array $epesEmployees): \UL
	{
		foreach ($epesEmployees as &$el) {
			if ($el instanceof Recursive) {
				$el = $el->value . UL::recursive($el->getChildren());
			}
		}

		return new UL($epesEmployees);
	}

	public function add($value, $key = null): void
	{
		if ($key) {
			$this->items[$key] = $value;
		} else {
			$this->items[] = $value;
		}
	}

	public function makeClickable(string $urlPrefix = ''): void
	{
		$this->linkWrap = '<a href="' . $urlPrefix . '###LINK###">|</a>';
		$this->links = array_keys($this->items);
		$this->links = array_combine($this->links, $this->links);
	}

	public function __toString(): string
	{
		return $this->render();
	}

	public function render(): string
	{
		$out = $this->withoutUL();
		return $this->before . MergedContent::mergeStringArrayRecursive($out) . $this->after;
	}

	/**
	 * @return mixed[][]|string[]
	 */
	public function withoutUL(): array
	{
		$out = [];
		foreach ($this->items as $class => $li) {
			$link = $this->getLinkFor($class, $li);

			// maybe we need to wrap after $this->links
			if ($this->linkWrap && !is_numeric($class)) {
				$wrap = Wrap::make($this->linkWrap);
				// don't translate __() because the values may come from DB
				if (is_array($li)) {
					$li = MergedContent::mergeStringArrayRecursive($li);
				}

				$li = $wrap->wrap($li);
			} else {
				$link = null;
			}

			$line = Wrap::make($this->wrap)->wrap($li);
			if ($link) {
				$line = str_replace('%23%23%23LINK%23%23%23', $link, $line);
				$line = str_replace('###LINK###', $link, $line);
			}

			$line = str_replace('###CLASS###', $class, $line);
			$line = str_replace('###TEXT###', $li, $line);
			$line = str_replace('###ACTIVE###', $class == $this->activeClass ? $this->active : '', $line);
			$out[] = $line;
		}

		return $out;
	}

	/**
	 * @param $class
	 * @param $li
	 * @return mixed
	 */
	protected function getLinkFor($class, $li)
	{
		if ($this->links) {
			$link = $this->links[$class];
		} elseif ($this->linkFunc) {
			$link = call_user_func($this->linkFunc, $class, $li);
		} else {
			$link = $class;
		}

		return $link;
	}

	public function cli(): void
	{
		foreach ($this->items as $class => $li) {
			echo '* ', strip_tags($li);
			if (!is_numeric($class)) {
				echo ' [', $class, ']', BR;
			} else {
				echo BR;
			}
		}
	}

	public function clear(): void
	{
		$this->before = '';
		$this->after = '';
		$this->wrap = '|';
	}

}
