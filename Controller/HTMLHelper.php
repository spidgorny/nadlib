<?php

trait HTMLHelper {

	function s($something) {
		return MergedContent::mergeStringArrayRecursive($something);
	}

	/**
	 * @param string|URL $href
	 * @param string|htmlString $text
	 * @param bool $isHTML
	 * @param array $more
	 * @return HTMLTag
	 */
	function a($href, $text = '', $isHTML = false, array $more = array()) {
		return new HTMLTag('a', array(
				'href' => $href,
			) + $more, $text ?: $href, $isHTML);
	}

	function div($content, $class = '', array $more = array()) {
		$more['class'] = ifsetor($more['class']) .' '.$class;
		$more = HTMLTag::renderAttr($more);
		return '<div '.$more.'>'.$this->s($content).'</div>';
	}

	function span($content, $class = '', array $more = array()) {
		$more['class'] = ifsetor($more['class']) .' '.$class;
		$more = HTMLTag::renderAttr($more);
		return '<span '.$more.'>'.$this->s($content).'</span>';
	}

	function info($content) {
		return '<div class="alert alert-info">'.$this->s($content).'</div>';
	}

	function error($content) {
		return '<div class="alert alert-danger">'.$this->s($content).'</div>';
	}

	function success($content) {
		return '<div class="alert alert-success">'.$this->s($content).'</div>';
	}

	function message($content) {
		return '<div class="alert alert-warning">'.$this->s($content).'</div>';
	}

	function h1($content) {
		return '<h1>'.$this->s($content).'</h1>';
	}

	function h2($content) {
		return '<h2>'.$this->s($content).'</h2>';
	}

	function h3($content) {
		return '<h3>'.$this->s($content).'</h3>';
	}

	function h4($content) {
		return '<h4>'.$this->s($content).'</h4>';
	}

	function progress($percent) {
		$percent = intval($percent);
		return '<div class="progress">
		  <div class="progress-bar" role="progressbar"
		  	aria-valuenow="'.$percent.'" aria-valuemin="0" aria-valuemax="100"
		  	style="width: '.$percent.'%;">
			'.$percent.'%
		  </div>
		</div>';
	}

	function p($content, array $attr = array()) {
		$more = HTMLTag::renderAttr($attr);
		return '<p '.$more.'>'.$this->s($content).'</p>';
	}

	function img($src, array $attr = array()) {
		return new HTMLTag('img', array(
				'src' => /*$this->e*/($src),	// encoding is not necessary for &amp; in URL
			) + $attr);
	}

	function e($content) {
		if (is_array($content)) {
			$content = MergedContent::mergeStringArrayRecursive($content);
		}
		return htmlspecialchars($content, ENT_QUOTES);
	}

	function script($file) {
		$mtime = filemtime($file);
		$file .= '?'.$mtime;
		return '<script src="'.$file.'" type="text/javascript"></script>';
	}

	function url($page, array $params = array()) {
		return $page.'?'.http_build_query($params);
	}

}
