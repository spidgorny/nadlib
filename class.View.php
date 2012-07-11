<?php

class View {
	protected $file;
	protected $caller;

	/**
	 * Enter description here...
	 *
	 * @var Request
	 */
	protected $request;

	protected $parts = array();

	function __construct($file, $copyObject = NULL) {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__.' ('.$file.')');
		$this->file = $file;
		if ($copyObject) {
			$this->caller = $copyObject;
			/*$vars = get_object_vars($copyObject);
			if ($vars) foreach ($vars as $prop => $val) {
				$this->$prop = $val;
			}
			*/
		}
		$this->request = $GLOBALS['i']->request;
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__.' ('.$file.')');
	}

/*	Add as many public properties as you like and use them in the PHTML file. */

	function render() {
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->startTimer(__METHOD__);
		$file = 'template/'.$this->file;
		ob_start();
		require($file);
		if (!$content) {
			$content = ob_get_clean();
		} else {
			ob_end_clean();
		}
		if (isset($GLOBALS['profiler'])) $GLOBALS['profiler']->stopTimer(__METHOD__);
		return $content;
	}

	function wikify($text) {
		$lines = explode("\n", $text);
		foreach ($lines as $line) {
			if ($line{0} == '*') {
				if (!$inUL) {
					$lines2[] = "<ul>";
					$inUL = TRUE;
				}
			}
			$lines2[] = $line;
			if ($line{0} != '*') {
				if ($inUL) {
					$lines2[] = "</ul>";
					$inUL = FALSE;
				}
			}
		}
		$text = implode("\n", $lines2);
		$text = str_replace("\n* ", "\n<li> ", $text);
		$text = str_replace("\n<ul>\n", "<ul>", $text);
		$text = str_replace("</ul>\n", "</ul>", $text);
		$text = nl2br($text);
		return $text;
	}

	/**
	 * Will load the template file and split it by the dividor.
	 * Use renderPart($i) to render the corresponding part.
	 *
	 * @param unknown_type $sep
	 */
	function splitBy($sep) {
		$file = 'template/'.$this->file;
		$content = file_get_contents($file);
		$this->parts = explode($sep, $content);
	}

	/**
	 * http://www.php.net/manual/en/function.eval.php#88820
	 *
	 * @param unknown_type $i
	 * @return unknown
	 */
	function renderPart($i) {
		return eval('?>'.$this->parts[$i]);
	}

	function escape($str) {
		return htmlspecialchars($str, ENT_QUOTES);
	}

	function __toString() {
		//debug_pre_print_backtrace();
		return $this->render().'';
	}

	function link(array $params) {
		return Controller::getInstance()->makeURL($params);
	}

	function __call($func, array $args) {
		$method = array($this->caller, $func);
		if (!is_callable($method) || !method_exists($this->caller, $func)) {
			$method = array($this->caller, $func);
		}
		return call_user_func_array($method, $args);
	}

	function &__get($var) {
		return $this->caller->$var;
	}

/*	function __set($var, $val) {
		$this->caller->$var = &$val;
	}
*/

	/**
	   NAME        : autolink()
	   VERSION     : 1.0
	   AUTHOR      : J de Silva
	   DESCRIPTION : returns VOID; handles converting
					 URLs into clickable links off a string.
	   TYPE        : functions
	 * http://www.gidforums.com/t-1816.html
	   ======================================*/

	function autolink( &$text, $target='_blank', $nofollow=true ) {
	  // grab anything that looks like a URL...
	  $urls  =  $this->_autolink_find_URLS( $text );
	  if( !empty($urls) ) // i.e. there were some URLS found in the text
	  {
		array_walk( $urls, array($this, '_autolink_create_html_tags'), array('target'=>$target, 'nofollow'=>$nofollow) );
		$text  =  str_replace( array_keys($urls), array_values($urls), $text );
	  }
	  return $text;
	}

	function _autolink_find_URLS( $text ) {
	  // build the patterns
	  $scheme         =       '(http:\/\/|https:\/\/)';
	  $www            =       'www\.';
	  $ip             =       '\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}';
	  $subdomain      =       '[-a-z0-9_]+\.';
	  $name           =       '[a-z][-a-z0-9]+\.';
	  $tld            =       '[a-z]+(\.[a-z]{2,2})?';
	  $the_rest       =       '\/?[a-z0-9._\/~#&=;%+?-]+[a-z0-9\/#=?]{1,1}';
	  $pattern        =       "$scheme?(?(1)($ip|($subdomain)?$name$tld)|($www$name$tld))$the_rest";

	  $pattern        =       '/'.$pattern.'/is';
	  $c              =       preg_match_all( $pattern, $text, $m );
	  unset( $text, $scheme, $www, $ip, $subdomain, $name, $tld, $the_rest, $pattern );
	  if( $c )
	  {
		return( array_flip($m[0]) );
	  }
	  return( array() );
	}

	function _autolink_create_html_tags( &$value, $key, $other=null ) {
	  $target = $nofollow = null;
	  if( is_array($other) )
	  {
		$target      =  ( $other['target']   ? " target=\"$other[target]\"" : null );
		// see: http://www.google.com/googleblog/2005/01/preventing-comment-spam.html
		$nofollow    =  ( $other['nofollow'] ? ' rel="nofollow"'            : null );
	  }
	  $value = "<a href=\"$key\"$target$nofollow>$key</a>";
	}

	function linkBIDs($text) {
		$text = preg_replace('/\[#(\d+)\]/', '<a href="?main2.php?bid=$1">$1</a>', $text);
		return $text;
	}

}
