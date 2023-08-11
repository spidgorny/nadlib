<?php

/**
 * Class DocCommentParser
 * Shamelessly stolen from TYPO3.Flow - don't tell anybody
 */
class DocCommentParser
{

	/**
	 * The description as found in the doc comment
	 * @var string
	 */
	protected $description = '';
	/**
	 * An array of tag names and their values (multiple values are possible)
	 * @var array
	 */
	protected $tags = [];

	function __construct($text = NULL)
	{
		$this->text = $text;
	}

	/**
	 * Parses the given doc comment and saves the result (description and
	 * tags) in the parser's object. They can be retrieved by the
	 * getTags() getTagValues() and getDescription() methods.
	 *
	 * @param string $docComment A doc comment as returned by the reflection getDocComment() method
	 * @return DocCommentParser
	 */
	public function parseDocComment($docComment = NULL)
	{
		$docComment = $docComment ?: $this->text;
		$this->description = '';
		$this->tags = [];
		$lines = explode(chr(10), $docComment);
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '*/') {
				break;
			}
			if (strlen($line) > 0 && strpos($line, '* @') !== FALSE) {
				$this->parseTag(substr($line, strpos($line, '@')));
			} elseif (count($this->tags) === 0) {
				$this->description .= preg_replace('/\s*\\/?[\\\\*]*\s?(.*)$/', '$1', $line) . chr(10);
			}
		}
		$this->description = trim($this->description);
		return $this;
	}

	/**
	 * Parses a line of a doc comment for a tag and its value.
	 * The result is stored in the internal tags array.
	 *
	 * @param string $line A line of a doc comment which starts with an @-sign
	 * @return void
	 */
	protected function parseTag($line)
	{
		$tagAndValue = [];
		if (preg_match('/@[A-Za-z0-9\\\\]+\\\\([A-Za-z0-9]+)(?:\\((.*)\\))?$/', $line, $tagAndValue) === 0) {
			$tagAndValue = preg_split('/\s/', $line, 2);
		} else {
			array_shift($tagAndValue);
		}
		$tag = strtolower(trim($tagAndValue[0], '@'));
		if (count($tagAndValue) > 1) {
			$this->tags[$tag][] = trim($tagAndValue[1], ' "');
		} else {
			$this->tags[$tag] = [];
		}
		//debug($this->tags);
	}

	/**
	 * Returns the description which has been previously parsed
	 *
	 * @return string The description which has been parsed
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Returns the values of the specified tag. The doc comment
	 * must be parsed with parseDocComment() before tags are
	 * available.
	 *
	 * @param string $tagName The tag name to retrieve the values for
	 * @return array The tag's values
	 */
	public function getTagValues($tagName)
	{
		return ifsetor($this->tags[$tagName]);
	}

	public function getFirstTagValue($tagName)
	{
		$values = $this->getTagValues($tagName);
		return ifsetor($values[0]);
	}

}
