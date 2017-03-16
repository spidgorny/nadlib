<?php

class PGArray extends AsIs {

	/**
	 * @var dbLayer
	 */
	var $db;

	/**
	 * @var bool
	 */
	var $standard_conforming_strings;

	/**
	 * @var array
	 */
	var $data;

	function __construct(dbLayer $db, array $data = NULL) {
		$this->db = $db;

		$query = "SHOW standard_conforming_strings;";
		$result = $this->db->perform($query);
		$return = pg_fetch_assoc($result);
		pg_free_result($result);
		$this->standard_conforming_strings = first($return);
		$this->standard_conforming_strings =
			strtolower($this->standard_conforming_strings) == 'on';

		if ($data) {
			$this->data = $data;
		}
	}

	function set(array $data) {
		$this->data = $data;
	}

	/**
	 * New better syntax for using it in SQL which does not
	 * require tripple escaping of backslashes
	 * @return string
	 */
	function __toString() {
		$quoted = $this->db->quoteValues($this->data);
		return 'ARRAY['.implode(', ', $quoted).']';
	}

	function encodeInString() {
		return $this->setPGArray($this->data);
	}

	/**
	 * http://www.php.net/manual/en/ref.pgsql.php#57709
	 *
	 * @param unknown_type $pgArray
	 * @return unknown
	 */
	function PGArrayToPHPArray($pgArray) {
		$ret = array();
		$stack = array(&$ret);
		$pgArray = substr($pgArray, 1, -1);
		$pgElements = explode(",", $pgArray);

		//ArrayDump($pgElements);

		foreach($pgElements as $elem)
		{
			if(substr($elem,-1) == "}")
			{
				$elem = substr($elem,0,-1);
				$newSub = array();
				while(substr($elem,0,1) != "{")
				{
					$newSub[] = $elem;
					$elem = array_pop($ret);
				}
				$newSub[] = substr($elem,1);
				$ret[] = array_reverse($newSub);
			}
			else
				$ret[] = $elem;
		}
		return $ret;
	}

	/**
	 * Slawa's own recursive approach. Not working 100%. See mTest from ORS.
	 * @param $input
	 * @internal param string $dbarr
	 * @return array
	 */
	function getPGArray($input) {
		if (strlen($input) && $input{0} == '{') {	// array inside
			$input = substr(substr(trim($input), 1), 0, -1);	// cut { and }
			return $this->getPGArray($input);
		} else {
			if (strpos($input, '},{') !== FALSE) {
				$parts = explode('},{', $input);
				foreach ($parts as &$p) {
					$p = $this->getPGArray($p);
				}
			} else {
				$parts = $this->str_getcsv($input, ',', '"');
				$parts = (array)$parts;
				//debug($parts);
				//$parts = array_map('stripslashes', $parts);	// already done in str_getcsv
			}
			return $parts;
		}
	}

	static function str_getcsv($input, $delimiter=',', $enclosure='"', $escape='\\', $eol=null) {
		$temp=fopen("php://memory", "rw");
		fwrite($temp, $input);
		fseek($temp, 0);
		$r = array();
		while (($data = fgetcsv($temp, 4096, $delimiter, $enclosure, $escape)) !== false) {
//			$data = array_map('stripcslashes', $data);
			$data = array_map(function ($str) {
				// exactly opposite to setPGArray()
				$str = str_replace('\"', '"', $str);
				// this is needed because even with
				// $standard_conforming_strings = on
				// PostgreSQL is escaping backslashes
				// inside arrays (not in normal strings)
				// select 'a
				// b', ARRAY['slawa', '{"a":"multi\nline"}']
				$str = str_replace('\\\\', '\\', $str);
				return $str;
			}, $data);
			$r[] = $data;
		}
		fclose($temp);
		return ifsetor($r[0]);
	}

	/**
	 * Change a db array into a PHP array
	 * @param $input
	 * @internal param String $arr representing the DB array
	 * @return A PHP array
	 */
	/*	function getPGArray($dbarr) {
			// Take off the first and last characters (the braces)
			$arr = substr($dbarr, 1, strlen($dbarr) - 2);

			// Pick out array entries by carefully parsing.  This is necessary in order
			// to cope with double quotes and commas, etc.
			$elements = array();
			$i = $j = 0;
			$in_quotes = false;
			while ($i < strlen($arr)) {
				// If current char is a double quote and it's not escaped, then
				// enter quoted bit
				$char = substr($arr, $i, 1);
				if ($char == '"' && ($i == 0 || substr($arr, $i - 1, 1) != '\\'))
					$in_quotes = !$in_quotes;
				elseif ($char == ',' && !$in_quotes) {
					// Add text so far to the array
					$elements[] = substr($arr, $j, $i - $j);
					$j = $i + 1;
				}
				$i++;
			}
			// Add final text to the array
			$elements[] = substr($arr, $j);

			// Do one further loop over the elements array to remote double quoting
			// and escaping of double quotes and backslashes
			for ($i = 0; $i < sizeof($elements); $i++) {
				$v = $elements[$i];
				if (strpos($v, '"') === 0) {
					$v = substr($v, 1, strlen($v) - 2);
					$v = str_replace('\\"', '"', $v);
					$v = str_replace('\\\\', '\\', $v);
					$elements[$i] = $v;
				}
			}

			return $elements;
		}
	*/
	function getPGArray1D($input) {
		$pgArray = substr(substr(trim($input), 1), 0, -1);
		$v1 = explode(',', $pgArray);
		if ($v1 == array('')) return array();
		$inside = false;
		$out = array();
		$o = 0;
		foreach ($v1 as $word) {
			if ($word{0} == '"') {
				$inside = true;
				$word = substr($word, 1);
			}
			if (in_array($word{strlen($word)-1}, array('"'))
				&& !in_array($word{strlen($word)-2}, array('\\'))
			) {
				$inside = false;
				$word = substr($word, 0, -1);
			}
			$out[$o] .= stripslashes($word); // strange but required
			if (!$inside) {
				$o++;
			}
		}
		//debug($input, $pgArray, $out);
		return $out;
	}

	/*	public function getPGArray($text) {
			$this->pg_array_parse($text, $output);
			return $output;
		}

		private function pg_array_parse( $text, &$output, $limit = false, $offset = 1 ) {
			if( false === $limit )
			{
				$limit = strlen( $text )-1;
				$output = array();
			}
			if( '{}' != $text )
				do
				{
					if( '{' != $text{$offset} )
					{
						preg_match( "/(\\{?\"([^\"\\\\]|\\\\.)*\"|[^,{}]+)+([,}]+)/", $text, $match, 0, $offset );
						$offset += strlen( $match[0] );
						$output[] = ( '"' != $match[1]{0} ? $match[1] : stripcslashes( substr( $match[1], 1, -1 ) ) );
						if( '},' == $match[3] ) return $offset;
					}
					else  $offset = $this->pg_array_parse( $text, $output, $limit, $offset+1 );
				}
				while( $limit > $offset );
		}
	*/

	/**
	 * @param array $data
	 * @return string
	 */
	function setPGArray(array $data) {
		foreach ($data as &$el) {
			if (is_array($el)) {
				$el = $this->setPGArray($el);
			} else {
				$el = pg_escape_string($el);
//				$el = addslashes($el);

				if ($this->standard_conforming_strings) {
					//$el = addslashes($el); // changed after postgres version updated to 9.4
					//$el = str_replace('\\', '\\\\', $el);
					$el = str_replace("'", "''", $el);
					$el = "'".$el."'";
				} else {
					$el = str_replace('"', '\\"', $el);
					$el = '"'.$el.'"';
				}
			}
		}
		//$result = '{'.implode(',', $data).'}';
		$result = new AsIs('ARRAY['.implode(',', $data).']');
		debug($result.'', $this->standard_conforming_strings, $el, $data);
		return $result;
	}

	function __toString() {
		return $this->setPGArray($this->data);
	}

}
