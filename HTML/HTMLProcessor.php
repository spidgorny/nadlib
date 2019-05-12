<?php

/**
 * composer require ezyang/htmlpurifier
 */
class HTMLProcessor
{

	public $source;

	public $allowedTags = [
		'a[href]'
	];

	public function __construct($source)
	{
		$this->source = $source;
	}

	public function purifyLinkify()
	{
		$comment = preg_replace(
			"/([<&].+?>[^&<>]*?)#(\w+)([^<>]*?<.+?>)/",
			"\\1<a href=\"Search?q=\\2\" target=\"_blank\">#\\2</a>\\3",
			$this->source
		);
		$comment = $this->cleanComment($comment);
		$comment = trim($comment);
		$comment = nl2br($comment);
		if (class_exists('Essence\Essence')) {
			$comment .= $this->getEmbeddables($comment);
		}
		return $comment;
	}

	/**
	 * @param string $comment
	 * @param array $allowedTags
	 * @return string
	 */
	public function cleanComment($comment)
	{
		//$v = new View('');
		//$comment = $v->autolink($comment);
		$config = HTMLPurifier_Config::createDefault();
		//debug($config);
		$config->set('HTML.Allowed', implode(',', $this->allowedTags));
		$config->set('Attr.AllowedFrameTargets', ['_blank']);
		$config->set('Attr.AllowedRel', ['nofollow']);
		if (ifsetor($this->allowedTags['a[href]'])) {
			$config->set('AutoFormat.Linkify', true);
		}
		$config->set('HTML.TargetBlank', true);
		$config->set('HTML.Nofollow', true);
		$purifier = new HTMLPurifier($config);
		$clean_html = $purifier->purify($comment);
		return $clean_html;
	}

	/**
	 * composer require essence/essence
	 * @param $comment
	 * @return string
	 */
	public function getEmbeddables($comment)
	{
		$content = '';
		$links = $this->getLinks($comment);
		foreach ($links as $link => $_) {
			/** @noinspection PhpUndefinedNamespaceInspection */
			$Essence = @Essence\Essence::instance();
			$Media = $Essence->embed($link);

			if ($Media) {
				$content .= $Media->html;
			}
		}
		return $content;
	}

	/**
	 * @param $comment
	 * @return array
	 */
	public function getLinks($comment)
	{
		return View::_autolink_find_URLS($comment);
	}

}
