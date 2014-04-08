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

	/**
	 *
	 * @param array|null $allowed If provided this will override allowed extensions
	 */
	function __construct($allowed = array()) {

		if(!empty($allowed)) {
			$this->allowed = $allowed;
		}

		if ($this->isUploaded()) {
			//debug($_FILES);
		}
	}

	function isUploaded() {
		return !!$_FILES;
	}

	/**
		Usage:
		$uf = new Uploader();
		$f = $uf->getUploadForm()
		// add custom hidden fields to upload form (e.g. Loan[id])
		if (!empty($hiddenFields)) {
			foreach ($hiddenFields as $name => $value) {
				$f->hidden($name, $value);
			}
		}
		@param  string - input field name - usually 'file'
		@return HTMLForm
	*/
	public function getUploadForm($fieldName = 'file') {
		$f = new HTMLForm();
		$f->file($fieldName);
		$f->text('<br />');
		$f->submit('Upload', array('class' => 'btn btn-primary'));
		$f->text('<div class="message">Max size: '.ini_get('upload_max_filesize').'</div>');
		$f->text('<div class="message">Max post: '.ini_get('post_max_size').'</div>');
		$f->text('<div class="message">Allowed: '.implode(', ', $this->allowed).'</div>');
		return $f;
	}

    /**
     * @param string $from - usually 'file' - the same name as in getUploadForm()
     * @param string $to - directory
     * @param bool $overwriteExistingFile
     * @throws Exception
     */
	public function moveUpload($from, $to, $overwriteExistingFile = true) {
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

            // if you don't want existing files to be overwritten,
            // new file will be renamed to *_n,
            // where n is the number of existing files
            $fileName = $to.$uf['name'];
            if (!$overwriteExistingFile && file_exists($fileName)) {
                $actualName = pathinfo($fileName, PATHINFO_FILENAME);
                $originalName = $actualName;
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);

                $i = 1;
                while(file_exists($to.$actualName.".".$extension))
                {
                    $actualName = (string) $originalName.'_'.$i;
                    $fileName = $to.$actualName.".".$extension;
                    $i++;
                }
            }

			$ok = @move_uploaded_file($uf['tmp_name'], $fileName);
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

	/**
	 * Will incrementally create subfolders which don't exist.
	 * @param $folder
	 */
	public function createUploadFolder($folder) {
		$parts = trimExplode('/', $folder);
		$current = '/';
		foreach ($parts as $plus) {
			$current .= $plus.'/';
			if (!file_exists($current)) {
				mkdir($current);
			}
		}
	}

	function getContent($from) {
		if ($uf = $_FILES[$from]) {
			return file_get_contents($uf['tmp_name']);
		}
	}

	public function getTempFile($fieldName = 'file') {
		if ($this->isUploaded()) {
			return $_FILES[$fieldName]['tmp_name'];
		}
	}

	/**
	 * Handles the file upload from https://github.com/blueimp/jQuery-File-Upload/wiki/Basic-plugin
	 * If no error it will call a callback to retrieve a redirect URL
	 * @param $callback
	 * @param array $params
	 */
	function handleBlueImpUpload($callback, array $params) {
		require 'vendor/blueimp/jquery-file-upload/server/php/UploadHandler.php';
		$uh = new UploadHandler($params, false);
		//$uh->post(true); exit();
		ob_start();
		$uh->post(true);
		$done = ob_get_clean();
		$json = json_decode($done);
		//print_r(array($uh, $done, $json));
		if (is_object($json)) {
			$data = get_object_vars($json->file[0]);
			if (!$data['error']) {
				$redirect = $callback($data);
				$json->file[0]->redirect = $redirect;
				$request = Request::getInstance();
				if ($request->isAjax()) {
					echo json_encode($json);
				} else if ($redirect) {
					$request->redirect($redirect);
				}
			} else {
				echo $data['error'];
			}
		} else {
			echo $done;
		}
		exit();
	}

}
