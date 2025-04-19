<?php

/**
 * @property string $table
 * @property array $thead
 * @property array $tbody
 * @property array $tfoot
 */
class HTMLTableBuf extends MergedContent
{

	public $curPart = 'tbody';

	public $thead;

	public $tbody;

	public $tfoot;

	public function __construct(array $parts = [])
	{
		parent::__construct($parts + [
				'table' => '',
				'thead' => '',
				'tbody' => '',
			]);
	}

	public function table(array $more = []): void
	{
		$this['table'] = "<table " . HTMLTag::renderAttr($more) . ">\n";
	}

	public function tablee(): void
	{
		$this['/table'] = "</table>\n";
	}

	public function tr(array $more = []): void
	{
		$this->addSub('tbody', "<tr " . HTMLTag::renderAttr($more) . ">\n");
	}

	public function tre(): void
	{
		$this->addSub('tbody', "</tr>\n");
	}

	public function ftr(array $more = []): void
	{
		$this->addSub('tfoot', "<tr " . HTMLTag::renderAttr($more) . ">\n");
	}

	public function ftre(): void
	{
		$this->addSub('tfoot', "</tr>\n");
	}

	public function the(): void
	{
		$this->addSub('thead', "</th>\n");
	}

	public function addTHead($text): void
	{
		$this->addSub('thead', $text);
	}

	public function tfoot($text): void
	{
		$this->addSub('tfoot', $text);
	}

	public function cell($a, array $more = []): void
	{
		$this->td($more);
		$this->text($a);
		$this->tde();
	}

	public function td(array $more = []): void
	{
		$this->addSub($this->curPart, "<td " . HTMLTag::renderAttr($more) . ">");
	}

	public function text($text): void
	{
		$this->addSub($this->curPart, $text);
	}

	public function tde(): void
	{
		$this->addSub($this->curPart, "</td>\n");
	}

	/**
	 * @param array $aCaption - array of names
	 * @param array $thMore - more on each column TH
	 * @param array $trMore - more on the whole row
	 */
	public function thes(array $aCaption, $thMore = [], array $trMore = []): void
	{
		$this->htr($trMore);
		foreach ($aCaption as $i => $caption) {
			if ($caption instanceof HTMLTag && $caption->tag === 'th') {
				$this->thead[] = $caption . '';
			} else {
				$more = $thMore[$i] ?? [];
				$this->th($more, $caption);
			}
		}

		$this->htre();
//		llog('thead after thes', $this->thead);
	}

	public function htr(array $more = []): void
	{
		$this->addSub('thead', "<tr " . HTMLTag::renderAttr($more) . ">\n");
	}

	public function th(array $more = [], $content = ''): void
	{
		$this->addSub('thead', new HTMLTag('th', $more, $content) . "\n");
	}

	public function htre(): void
	{
		$this->addSub('thead', "</tr>\n");
	}

	public function render(): void
	{
		print($this->getContent());
	}

	public function tag(HTMLTag $tag): void
	{
		$this->addSub($this->curPart, $tag . '');
	}

	public function isDone(): bool
	{
		return isset($this['/table']);
	}

	public function &__get($key)
	{
//		echo __METHOD__, '(', $key, ')', BR;
		if (!isset($this[$key])) {
			$this->offsetSet($key, []);
		}

		return $this->content[$key];
	}

	public function offsetGet(mixed $offset): mixed
	{
		return ifsetor($this->content[$offset], []);
	}

	public function reset(): void
	{
		$this['table'] = [];
		$this['thead'] = [];
		$this['tbody'] = [];
	}

	public function __debugInfo()
	{
		return [
			'table' => $this['table'],
			'thead' => $this['thead'],
			'tbody' => $this['tbody'],
			'content' => $this->content,
		];
	}

}
