<?php

class UL
{

	public $items = [];

	public $before = '<ul>';

	public $after = '</ul>';

	public $wrap = '<li###ACTIVE###>|</li>';

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
	 * @var callback to link generation function(index, name)
	 */
	public $linkFunc;

	public function __construct(array $items = [])
	{
		$this->items = $items;
		$this->activeClass = first(array_keys($this->items));
	}

	public function add($value, $key = NULL)
	{
		if ($key) {
			$this->items[$key] = $value;
		} else {
			$this->items[] = $value;
		}
	}

	public function makeClickable($urlPrefix = '')
	{
		$this->linkWrap = '<a href="' . $urlPrefix . '###LINK###">|</a>';
	}

	public function render()
	{
		$out = [];
		foreach ($this->items as $class => $li) {
			$link = $this->getLinkFor($class, $li);

			// maybe we need to wrap after $this->links
			if ($this->linkWrap) {
				$wrap = Wrap::make($this->linkWrap);
				// don't translate __() because the values may come from DB
				if (is_array($li)) {
					$li = MergedContent::mergeStringArrayRecursive($li);
				}
				$li = $wrap->wrap($li);
			} else {
				$link = NULL;
			}

			$line = Wrap::make($this->wrap)->wrap($li);
			$line = str_replace('%23%23%23LINK%23%23%23', $link, $line);
			$line = str_replace('###LINK###', $link, $line);
			$line = str_replace('###CLASS###', $class, $line);
			$line = str_replace('###TEXT###', $li, $line);
			llog($class, $this->activeClass, $this->active);
			$line = str_replace('###ACTIVE###', $class == $this->activeClass ? $this->active : '', $line);
			$out[] = $line;
		}
		return $this->before . implode("\n", $out) . $this->after;
	}

	public function __toString()
	{
		return $this->render();
	}

	public static function DL(array $assoc)
	{
		$ul = new UL($assoc);
		$links = array_keys($assoc);
		$ul->links = array_combine($links, $links);
		$ul->wrap = '<dt>###CLASS###</dt>';
		$ul->linkWrap = '<dd>|</dd>';
		$ul->before = '<dl class="dl-horizontal">';
		$ul->after = '</dl>';
		return $ul;
	}

	public static function recursive(array $epesEmployees)
	{
		foreach ($epesEmployees as &$el) {
			if ($el instanceof Recursive) {
				$el = $el->value . UL::recursive($el->getChildren());
			}
		}
		$ul = new UL($epesEmployees);
		return $ul;
	}

	public function cli()
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

	/**
	 * @param $class
	 * @param $li
	 * @return mixed
	 */
	public function getLinkFor($class, $li)
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

}
