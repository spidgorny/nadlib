<?php

/**
 * @property  table
 * @property  thead
 * @property  tbody
 * @property  tfoot
 */
class HTMLTableBuf extends MergedContent
{

	public $curPart = 'tbody';

	public function __construct()
	{
		parent::__construct(array(
			'table' => '',
			'thead' => '',
			'tbody' => '',
		));
	}

	public function table($more = "")
	{
		$this['table'] = "<table $more>\n";
	}

	public function tablee()
	{
		$this['/table'] = "</table>\n";
	}

	public function htr(array $more = [])
	{
		$this->addSub('thead', "<tr " . HTMLTag::renderAttr($more) . ">\n");
	}

	public function htre()
	{
		$this->addSub('thead', "</tr>\n");
	}

	public function tr($more = "")
	{
		$this->addSub('tbody', "<tr" . rtrim(' ' . $more) . ">\n");
	}

	public function tre()
	{
		$this->addSub('tbody', "</tr>\n");
	}

	public function ftr($more = "")
	{
		$this->addSub('tfoot', "<tr " . $more . ">\n");
	}

	public function ftre()
	{
		$this->addSub('tfoot', "</tr>\n");
	}

	public function th($more = '')
	{
		$this->addSub('thead', "<th" . rtrim(' ' . $more) . ">\n");
	}

	public function the()
	{
		$this->addSub('thead', "</th>\n");
	}

	public function td($more = "")
	{
		$this->addSub($this->curPart, "<td" . rtrim(' ' . $more) . ">");
	}

	public function tde()
	{
		$this->addSub($this->curPart, "</td>\n");
	}

	public function addTHead($text)
	{
		$this->addSub('thead', $text);
	}

	public function text($text)
	{
		$this->addSub($this->curPart, $text);
	}

	public function tfoot($text)
	{
		$this->addSub('tfoot', $text);
	}

	public function cell($a, array $more = array())
	{
		$this->td(HTMLTag::renderAttr($more));
		$this->text($a);
		$this->tde();
	}

	/**
	 * @param array $aCaption - array of names
	 * @param array $thMore - more on each column TH
	 * @param array $trMore - more on the whole row
	 */
	public function thes(array $aCaption, $thMore = array(), $trMore = [])
	{
		$this->htr($trMore);
		foreach ($aCaption as $i => $caption) {
			if ($caption instanceof HTMLTag) {
				$this->thead[] .= $caption;
			} else {
				if (is_string($thMore[$i])) {
					debug($i, $thMore[$i]);
				}
				$more = isset($thMore[$i]) ? HTMLTag::renderAttr($thMore[$i]) : '';
				if (is_array($more)) {
					$more = HTMLTag::renderAttr($more);
				}
				$this->thead[] .= '<th' . rtrim(' ' . $more) . '>' . $caption . '</th>' . "\n";
			}
		}
		$this->htre();
		//debug($this);
	}

	public function render()
	{
		print($this->getContent());
	}

	public function tag(HTMLTag $tag)
	{
		$this->addSub($this->curPart, $tag . '');
	}

	public function isDone()
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

	/**
	 * @param mixed $offset
	 * @return null
	 */
	public function offsetGet($offset)
	{
		return ifsetor($this->content[$offset], []);
	}

}
