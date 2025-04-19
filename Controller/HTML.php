<?php

use spidgorny\nadlib\HTTP\URL;

class HTML
{

//	use HTMLHelper;
    /**
     * @param string|URL $href
     * @param string|HtmlString|HTMLTag $text
     * @param bool $isHTML
     */
    public function a($href, $text = '', $isHTML = false, array $more = []): \HTMLTag
	{
		return new HTMLTag('a', [
				'href' => $href,
			] + $more, $text ?: $href, $isHTML);
	}

	public function div($content, string $class = '', array $more = []): string
	{
		$more['class'] = ifsetor($more['class']) . ' ' . $class;
		$moreString = HTMLTag::renderAttr($more);
		return '<div ' . $moreString . '>' . $this->s($content) . '</div>';
	}

	public function s($something): string
	{
		return MergedContent::mergeStringArrayRecursive($something);
	}

	public function span($content, string $class = '', array $more = []): \HtmlString
	{
		$more['class'] = ifsetor($more['class']) . ' ' . $class;
		$moreString = HTMLTag::renderAttr($more);
		return new HtmlString('<span ' . $moreString . '>' . $this->s($content) . '</span>');
	}

	public function info($content): string
	{
		return '<div class="alert alert-info">' . $this->s($content) . '</div>';
	}

	public function error($content): string
	{
		return '<div class="alert alert-danger">' . $this->s($content) . '</div>';
	}

	public function success($content): string
	{
		return '<div class="alert alert-success">' . $this->s($content) . '</div>';
	}

	public function message($content): string
	{
		return '<div class="alert alert-warning">' . $this->s($content) . '</div>';
	}

	public function h1($content, array $attrs = []): string
	{
		return '<h1 ' . HTMLTag::renderAttr($attrs) . '>' . $this->s($content) . '</h1>';
	}

	public function h2($content, array $attrs = []): string
	{
		return '<h2 ' . HTMLTag::renderAttr($attrs) . '>' . $this->s($content) . '</h2>';
	}

	public function h3($content, array $attrs = []): string
	{
		return '<h3 ' . HTMLTag::renderAttr($attrs) . '>' . $this->s($content) . '</h3>';
	}

	public function h4($content, array $attrs = []): string
	{
		return '<h4 ' . HTMLTag::renderAttr($attrs) . '>' . $this->s($content) . '</h4>';
	}

	public function h5($content, array $more = []): string
	{
		return '<h5 ' . HTMLTag::renderAttr($more) . '>' . $this->s($content) . '</h5>';
	}

	public function h6($content, array $more = []): string
	{
		return '<h6 ' . HTMLTag::renderAttr($more) . '>' . $this->s($content) . '</h6>';
	}

	public function progress($percent): string
	{
		$percent = intval($percent);
		return '<div class="progress">
		  <div class="progress-bar" role="progressbar"
		  	aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100"
		  	style="width: ' . $percent . '%;">
			' . $percent . '%
		  </div>
		</div>';
	}

	public function p($content, array $attr = []): string
	{
		$more = HTMLTag::renderAttr($attr);
		return '<p ' . $more . '>' . $this->s($content) . '</p>';
	}

	public function img($src, array $attr = []): \HTMLTag
	{
		$html = new HTMLTag('img', [
				'src' => /*$this->e*/
					($src),    // encoding is not necessary for &amp; in URL
			] + $attr);
		$html->closingTag = false;
		return $html;
	}

	public function e($content): \HTMLTag|\HtmlString|string
	{
		if ($content instanceof HTMLTag) {
			return $content;
		}

		if ($content instanceof HtmlString) {
			return $content;
		}

		if (is_array($content)) {
			$content = MergedContent::mergeStringArrayRecursive($content);
		}

		return htmlspecialchars($content, ENT_QUOTES);
	}

	public function script($file): string
	{
		$mtime = filemtime($file);
		$file .= '?' . $mtime;
		return '<script src="' . $file . '" type="text/javascript"></script>';
	}

	public function url(string $page, array $params = []): string
	{
		return $page . '?' . http_build_query($params);
	}

	public function pre($text, array $attr = []): string
	{
		$more = HTMLTag::renderAttr($attr);
		return '<pre ' . $more . '>' . $this->e($this->s($text)) . '</pre>';
	}

	public function strong($text): string
	{
		return '<strong>' . $this->e($this->s($text)) . '</strong>';
	}

	public function em($text): string
	{
		return '<em>' . $this->e($this->s($text)) . '</em>';
	}

	public function hr(): string
	{
		return '<hr />';
	}

	public function badge($count): string
	{
		return '<span class="badge">' . htmlspecialchars($count) . '</span>';
	}

	public function blockquote($getDescription): string
	{
		return '<blockquote>' . $this->e($getDescription) . '</blockquote>';
	}

	public function li($text): string
	{
		return '<li>' . $this->e($text) . '</li>';
	}

	public function st($a): string
	{
		return strip_tags($a);
	}

	public function bold($text): string
	{
		return '<b>' . $this->e($text) . '</b>';
	}

}
