<?php

/**
 * Class Uploader
 * General validating uploader with error handling
 */
class Uploader {

	public $allowed = array(
		'gif', 'jpg', 'png', 'jpeg',
	);

	public $allowedMime = array(

	);

	/**
	 * Which method of mime detection was used
	 * @var string
	 */
	public $mimeMethod;

	protected $errors = array(
		1 =>'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
			'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
			'The uploaded file was only partially uploaded.',
			'No file was uploaded.',
		6 =>'Missing a temporary folder.',
			'Failed to write file to disk.',
			'A PHP extension stopped the file upload.'
	);

	function __construct() {
		if ($this->isUploaded()) {
			//debug($_FILES);
		}
	}

	function isUploaded() {
		return !!$_FILES;
	}

	public function getUploadForm($fieldName = 'file') {
		$f = new HTMLForm();
		$f->file($fieldName);
		$f->submit('Upload', array('class' => 'btn btn-primary'));
		$content = $f;
		$content .= '<div class="message">Max size: '.ini_get('upload_max_filesize').'</div>';
		$content .= '<div class="message">Max post: '.ini_get('post_max_size').'</div>';
		return $content;
	}

	public function moveUpload($from, $to) {
		if ($uf = $_FILES[$from]) {
			if (!$this->checkError($uf)) {
				throw new Exception($this->errors[$uf['error']]);
			}
			if (!$this->checkExtension($uf)) {
				throw new Exception('File uploaded is not allowed ('.$uf['ext'].')');
			}
			if (!$this->checkMime($uf)) {
				throw new Exception('File uploaded is not allowed ('.$uf['mime'].')');
			}
			$ok = @move_uploaded_file($uf['tmp_name'], $to.$uf['name']);
			if (!$ok) {
				//throw new Exception($php_errormsg);	// empty
				$error = error_get_last();
				//debug($error);
				throw new Exception($error['message']);
			}
		}
	}

	function checkError(array $uf) {
		$errorCode = $uf['error'];
		return (!$errorCode);
	}

	/**
	 * Only if $this->allowed is defined
	 * @param array $uf
	 * @return bool
	 */
	function checkExtension(array &$uf) {
		if ($this->allowed) {
			$filename = $uf['name'];
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			$uf['ext'] = $ext;
			return in_array(strtolower($ext), $this->allowed);
		} else {
			return true;
		}
	}

	/**
	 * Only if $this->allowedMime is defined
	 * @param array $uf
	 * @return bool
	 */
	function checkMime(array &$uf) {
		if ($this->allowedMime) {
			$mime = $this->get_mime_type($uf['tmp_name']);
			$uf['mime'] = $mime;
			//debug($mime, $this->allowedMime);
			return in_array($mime, $this->allowedMime);
		} else {
			return true;
		}
	}

	/**
	 * Tries different methods
	 * @param $filename
	 * @return string
	 */
	function get_mime_type($filename) {
		if (class_exists('finfo')) {
			$fi = new finfo();
			$mime = $fi->file($filename);
			$this->mimeMethod = 'finfo';
		} else if (function_exists('mime_content_type')) {
			$mime = mime_content_type($filename);
			$this->mimeMethod = 'mime_content_type';
		} else {
			$mime = $this->get_mime_type_system($filename);
			$this->mimeMethod = 'get_mime_type_system';
		}
		$mime = trim($mime); 	// necessary !!!
		//debug($mime, $this->mimeMethod);
		return $mime;
	}

	/**
	 * http://www.php.net/manual/en/function.finfo-open.php#78927
	 * @param $filepath
	 * @return string
	 */
	function get_mime_type_system($filepath) {
		ob_start();
		system("file -i -b {$filepath}");
		$output = ob_get_clean();
		$output = explode("; ",$output);	// text/plain; charset=us-ascii
		if ( is_array($output) ) {
			$output = $output[0];
		}
		$output = explode(" ", $output);	// text/plain charset=us-ascii
		if ( is_array($output) ) {
			$output = $output[0];
		}
		return $output;
	}

}
