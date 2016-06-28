<?php

/**
 * Class CsvIterator
 * http://de2.php.net/fgetcsv#57802
 */
class CsvIterator implements Iterator
{
	const ROW_SIZE = 4096*1024;
	/**
	 * The pointer to the cvs file.
	 * @var resource
	 * @access private
	 */
	public $filePointer = null;

	/**
	 * The current element, which will
	 * be returned on each iteration.
	 * @var array
	 * @access private
	 */
	protected $currentElement = null;

	/**
	 * The row counter.
	 * @var int
	 * @access private
	 */
	private $rowCounter = 0;

	/**
	 * The delimiter for the csv file.
	 * @var str
	 * @access private
	 */
	private $delimiter = null;

	/**
	 * This is the constructor.It try to open the csv file.The method throws an exception
	 * on failure.
	 *
	 * @access public
	 * @param string $file The csv file.
	 * @param string $delimiter The delimiter.
	 *
	 * @throws Exception
	 */
	public function __construct($file, $delimiter=',')
	{
		try {
			ini_set('auto_detect_line_endings',TRUE);
			$this->filePointer = $this->fopen_utf8($file, 'r');
			$this->delimiter = $delimiter;
		}
		catch (Exception $e) {
			throw new Exception('The file "'.$file.'" cannot be read.');
		}
	}

	/**
	 * Reads past the UTF-8 bom if it is there.
	 */
	function fopen_utf8 ($filename, $mode) {
		$file = @fopen($filename, $mode);
		$bom = fread($file, 3);
		if ($bom != b"\xEF\xBB\xBF") {
			rewind($file);
		} else {
			//echo "bom found!\n";
		}
		return $file;
	}

	/**
	 * This method resets the file pointer.
	 *
	 * @access public
	 */
	public function rewind() {
		$this->rowCounter = 0;
		rewind($this->filePointer);
	}

	/**
	 * This method returns the current csv row as a 2 dimensional array
	 *
	 * @access public
	 * @return array The current csv row as a 2 dimensional array
	 */
	public function current() {
		$this->read();
		return $this->currentElement;
	}

	/**
	 * This method returns the current row number.
	 *
	 * @access public
	 * @return int The current row number
	 */
	public function key() {
		return $this->rowCounter;
	}

	/**
	 * This method checks if the end of file is reached.
	 *
	 * @access public
	 * @return boolean Returns true on EOF reached, false otherwise.
	 */
	public function next() {
		$this->rowCounter++;
		$this->read();
		return !feof($this->filePointer);
	}

	/**
	 * This method checks if the next row is a valid row.
	 *
	 * @access public
	 * @return boolean If the next row is a valid row.
	 */
	public function valid() {
		if (!$this->next()) {
			fclose($this->filePointer);
			return false;
		}
		return true;
	}

	function read() {
		static $last = -1; // != 0
		if ($this->rowCounter != $last) {
			$this->currentElement = fgetcsv($this->filePointer, self::ROW_SIZE,
				$this->delimiter);
			$last = $this->rowCounter;
		}
	}

}
