<?php

use spidgorny\nadlib\HTTP\URL;

trait HTMLHelper
{

	/**
     * @param string|URL $href
     * @param string|HtmlString $text
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
		$sMore = HTMLTag::renderAttr($more);
		return '<div ' . $sMore . '>' . $this->s($content) . '</div>';
	}

	public function s($something): string
	{
		return MergedContent::mergeStringArrayRecursive($something);
	}

	public function span($content, string $class = '', array $more = []): string
	{
		$more['class'] = ifsetor($more['class']) . ' ' . $class;
		$sMore = HTMLTag::renderAttr($more);
		return '<span ' . $sMore . '>' . $this->s($content) . '</span>';
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
		return new HTMLTag('img', [
				'src' => /*$this->e*/ ($src),    // encoding is not necessary for &amp; in URL
			] + $attr);
	}

	public function e($content): string
	{
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

}
