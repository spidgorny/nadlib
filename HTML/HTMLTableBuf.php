<?php

/**
 * @property array $table
 * @property array $thead
 * @property array $tbody
 * @property array $tfoot
 */
class HTMLTableBuf extends MergedContent
{

	var $curPart = 'tbody';

	function __construct()
	{
		parent::__construct([
			'table' => [],
			'thead' => [],
			'tbody' => [],
		]);
	}

	function table($more = "")
	{
		$this['table'] = "<table $more>\n";
	}

	function tablee()
	{
		$this['/table'] = "</table>\n";
	}

	function htr(array $more = [])
	{
		$this->addSub('thead', "<tr " . HTMLTag::renderAttr($more) . ">\n");
	}

	function htre()
	{
		$this->addSub('thead', "</tr>\n");
	}

	function tr($more = "")
	{
		$this->addSub('tbody', "<tr" . rtrim(' ' . $more) . ">\n");
	}

	function tre()
	{
		$this->addSub('tbody', "</tr>\n");
	}

	function ftr($more = "")
	{
		$this->addSub('tfoot', "<tr " . $more . ">\n");
	}

	function ftre()
	{
		$this->addSub('tfoot', "</tr>\n");
	}

	function th($more = '')
	{
		$this->addSub('thead', "<th" . rtrim(' ' . $more) . ">\n");
	}

	function the()
	{
		$this->addSub('thead', "</th>\n");
	}

	function td($more = "")
	{
		$this->addSub($this->curPart, "<td" . rtrim(' ' . $more) . ">");
	}

	function tde()
	{
		$this->addSub($this->curPart, "</td>\n");
	}

	function addTHead($text)
	{
		$this->addSub('thead', $text);
	}

	function text($text)
	{
		$this->addSub($this->curPart, $text);
	}

	function tfoot($text)
	{
		$this->addSub('tfoot', $text);
	}

	function cell($a, array $more = [])
	{
		$this->td(HTMLTag::renderAttr($more));
		$this->text($a);
		$this->tde();
	}

	/**
	 * @param array $aCaption - array of names
	 * @param array $thMore - more on each column TH
	 * @param string $trMore - more on the whole row
	 */
	function thes(array $aCaption, $thMore = [], $trMore = '')
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
				$this->thead[] .= '<th' . rtrim(' ' . $more) . '>' . $caption . '</th>';
			}
		}
		$this->htre();
		//debug($this);
	}

	function render()
	{
		print($this->getContent());
	}

	function tag(HTMLTag $tag)
	{
		$this->addSub($this->curPart, $tag . '');
	}

	function isDone()
	{
		return isset($this['/table']);
	}

}
