<?php

/**
 * Class slXMLParser
 * BugLog XML parser. Made before SimpleXML existed.
 * @deprecated
 */

class slXMLParser {
	var $parsed;

	function parseText($content) {
		//debug($content, 'content');
		$parser = xml_parser_create('UTF-8');
		//xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, TRUE); // to avoid \n in the text skipped
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, FALSE);
		$index = NULL;
		xml_parse_into_struct($parser, $content, $vals, $index);
		//debug(array('vals' => $vals, 'index' => $index));
		xml_parser_free($parser);
		$i = 0;
		$assoc = $this->xml_parse_into_assoc($vals, $i);
		$this->parsed = $assoc;
		//debug($assoc, 'first parse');
		$key = current(array_keys($assoc));
		$first = current($assoc);
		$assoc = array($key => current($this->simplify($first)));
		//debug($assoc);
		return $assoc;
	}

	function xml_parse_into_assoc($vals, &$i) {
		$ret = array();
		while ($i++ < sizeof($vals)) {
			$tag = $vals[$i];
			//println($tag['type']);
			switch($tag['type']) {
				case "cdata":
					$ret['value'] .= $tag['value'];
				break;
				case "complete":
					unset($tag['type']);
					unset($tag['level']);
					$tag['value'] = isset($tag['value']) ? trim($tag['value']) : '';
					if ($tag['value'] == "") unset($tag['value']);
					$attr = isset($tag['attributes']) ? $tag['attributes'] : '';
					if (!is_array($attr)) $attr = array();
					unset($tag['attributes']);
					$ret[] = array_merge($tag, $attr);
				break;
				case "open":
					unset($tag['type']);
					unset($tag['level']);
					$subpart = $this->xml_parse_into_assoc($vals, $i);
					if (is_array($subpart)) {
						$tag += $subpart;
					}
					$tag['value'] = trim($tag['value']);
					if ($tag['value'] == "") unset($tag['value']);
					$attr = $tag['attributes'];
					if (!$attr) $attr = array();
					unset($tag['attributes']);
					$ret[] = array_merge($tag, $attr);
				break;
				case "close":
					if ($i == sizeof($vals)-1) {
						return  array($tag['tag'] => $ret);
					} else {
						return $ret;
					}
				break;
			}
		}
	}

	/**
	 * Only works with Excel data.
	 *
	 * @param unknown_type $arr
	 * @return unknown
	 */
	function simplify($arr) {
		$res = array();
		if (is_array($arr)) {
			if (isset($arr['tag'])) {
				$res[$arr['tag']] = $arr['value'];
			}
			foreach ($arr as $i => $item) {
				if (is_numeric($i)) {
					$plus = $this->simplify($item);
					if ($plus) {
						$value = $arr['value'];
						if ($arr['tag'] == 'ss:Data') {
							//debug(array('before', $arr['tag'], $res[$arr['tag']]), "tag");
						}
						if ($res[$arr['tag']] /*!isset($res[$arr['tag']]['value']) && strlen($res[$arr['tag']]) > 0*/) {
							$temp = $res[$arr['tag']];
							if (!is_array($res[$arr['tag']])) {
								$res[$arr['tag']] = array();
							}
							$res[$arr['tag']]['value'] = $temp;
						}
						if ($arr['tag'] == 'ss:Data') {
							//debug(array('after', $arr['tag'], $res[$arr['tag']]), "tag");
						}
						if (is_array($res[$arr['tag']])) {
							$temp = $res[$arr['tag']];
							$res[$arr['tag']] = $this->array_merge_with_multi($res[$arr['tag']], $plus);
							if ($arr['tag'] == 'ss:Data') {
								//debug(array('original' => $temp, 'plus' => $plus, 'result' => $res[$arr['tag']]));
							}
						} else {
							$res[$arr['tag']] = $plus ? $plus : array();
						}
					}
				} else if ($i != 'tag' && $i != 'value') { // not numbers are attributes
					if (is_string($res[$arr['tag']][$i])) {
						$res[$i] = $item;
					} else {
						$res[$arr['tag']][$i] = $item;
					}
				}
			}
		}
		//if ($arr['tag'] == 'DocumentProperties') {
			//debug(array('source' => $arr, 'simplified' => $res), 'simplify');
		//}
		return $res;
	}

	function array_merge_with_multi($a, $b) {
		$c = $a;
		foreach ($b as $i => $v) {
			if (isset($c[$i])) {
				if (is_array($c[$i]) && current(array_keys($c[$i])) === 0) {
					//debug($c[$i], 'asdf1');
					//debug(current(array_keys($c[$i])), 'asdf2');
					$c[$i][] = $v;
				} else {
					//$c[$i] = $this->array_merge_with_multi(array($c[$i]), $v);
					$c[$i] = array(
						$c[$i],
						$v,
					);
				}
			} else {
				$c[$i] = $v;
			}
		}
		return $c;
	}

	/**
	 * Adds sub-elements as they are. Sub-elements are supposed to end with dot (.) to be TTree compatible.
	 *
	 * @param array $arr
	 * @return unknown
	 */
	function simplifySimple(array $arr) {
		//debug($arr);
		$res = array();
		if (is_array($arr)) foreach ($arr as $numeric => $pair) {
			//debug($pair, $numeric);
			if (is_numeric($numeric)) {
				$res[$pair['tag']] = $pair['value'];
				$sub = $this->simplifySimple($pair);
				if ($sub) {
					$res[$pair['tag']] = $sub;
				}
			}
		}
		return $res;
	}

	/**
	 * Will add sub-elements with dot (.) in the parent's name. TYPO3 style.
	 *
	 * @param array $arr
	 * @return unknown
	 */
	function simplifyTree(array $arr) {
		//debug($arr);
		$res = array();
		if (is_array($arr)) foreach ($arr as $numeric => $pair) {
			//debug($pair, $numeric);
			if (is_numeric($numeric)) {
				$res[$pair['tag']] = $pair['value'];
				$sub = $this->simplifyTree($pair);
				if ($sub) {
					$res[$pair['tag'].'.'] = $sub;
				}
			}
		}
		return $res;
	}

}