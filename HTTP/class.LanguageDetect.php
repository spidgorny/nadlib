<?php

class LanguageDetect
{
	public $languages = array();

	function __construct()
	{
		if ($_COOKIE['lang']) {
			$this->languages = array($_COOKIE['lang'] => $_COOKIE['lang']);
		}
		$this->languages = array_merge($this->languages, $this->getAcceptedLanguages());
		$this->languages = array_unique($this->languages);
		//debug($this->languages);// exit();
	}

	function getAcceptedLanguages()
	{
		$languagesArr = array();
		$rawAcceptedLanguagesArr = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

		foreach ($rawAcceptedLanguagesArr as $languageAndQualityStr) {
			list ($languageCode, $quality) = explode(';', $languageAndQualityStr);
			$acceptedLanguagesArr[$languageCode] = $quality ? (float)substr($quality, 2) : (float)1;
		}

		// Now sort the accepted languages by their quality and create an array containing only the language codes in the correct order.
		if (is_array($acceptedLanguagesArr)) {
			arsort($acceptedLanguagesArr);
			$languageCodesArr = array_keys($acceptedLanguagesArr);
			if (is_array($languageCodesArr)) {
				foreach ($languageCodesArr as $languageCode) {
					$languagesArr[substr($languageCode, 0, 2)] = substr($languageCode, 0, 2);
				}
			}
		}
		return $languagesArr;
	}

}
