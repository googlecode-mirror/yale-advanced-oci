<?php
class OCIGraphReader
{
	/**
	 * Variables that are read-only.
	 * @var array
	 */
	private $readOnlyVars = array('error', 'errno', 'imageDimensions', 'temporaryFile', 'temporaryImageFile');
	protected $error = '';
	protected $errno = 0;
	
	
	private static $barTopLineColor = array('r' => 192, 'g' => 192, 'b' => 192, 'a' => 1);
	private static $noBarLineColor = array('r' => 156, 'g' => 156, 'b' => 156, 'a' => 1);
	private static $leftBorderColor = array('r' => 128, 'g' => 128, 'b' => 128, 'a' => 1);
	private static $rightBorderColor = array('r' => 191, 'g' => 191, 'b' => 191, 'a' => 1);
	private $columnsLeftEdges = array();
	private $columnWidth = 0;
	private $returnKeys = array();
	private $imageDimensions = array('width' => 0, 'height' => 0);
	private $temporaryFile = 'temp';
	private $temporaryImageFile = 'temp.tif';
	private $tesseractCommand = 'tesseract';
	private $image;
	
	
	public function __construct() {
		$this->image = new Imagick();
	}

	/**
	 * Reads the bar graph picture.
	 * @param string $blob
	 */
	public function readImageBlob($blob) {
		$return = $this->image->readImageBlob($blob);
		if ($return) {
			$this->imageDimensions = $this->image->getImageGeometry();
		}
		return $return;
	}
	
	private function findBorders() {
		$y = 8;
		$return = array();
		for ($x = 0; $x < $this->imageDimensions['width']; $x++) {
			$color = $this->image->getImagePixelColor($x, $y)->getcolor(false);
			if ($color === self::$leftBorderColor) {
				$return['left'] = $x;
				break;
			}
		}
		
		for ($x = $this->imageDimensions['width'] - 1; $x >= 0; $x--) {
			$color = $this->image->getImagePixelColor($x, $y)->getcolor(false);
			if ($color === self::$rightBorderColor) {
				$return['right'] = $x;
				break;
			}
		}
		
		return $return;
	}
	
	/**
	 * Sets number of columns.
	 * @param int $columns either 2 or 5 (sorry, nothing else works)
	 */
	public function setColumns($columns) {
		if (!($columns === 2 || $columns === 5)) {
			return false;
		}
		
		if ($columns === 2) {
			$this->columnsLeftEdges = array(75, 250);
			$this->columnWidth = 100;
			return true;
		}
		
		$this->columnsLeftEdges = array();
		$borders = $this->findBorders();
		$distanceBetween = $borders['right'] - $borders['left'] - 31;
		$firstBar = $borders['left'] + 15;
		for ($i = 0; $i < 5; $i++) {
			$this->columnsLeftEdges[] = (int) ($firstBar + 2 + ($i * 0.216 * $distanceBetween));
		}
		$this->columnWidth = 44;
		return true;
	}
	
	/**
	 * Sets the keys in the return array.
	 * @param array $returnLabels
	 */
	public function setReturnKeys($returnKeys) {
		$this->returnKeys = $returnKeys;
		return true;
	}
	
	/**
	 * Sets the location of the temporary file for OCR output.
	 * @param string $temporaryFile
	 */
	public function setTemporaryFile($temporaryFile) {
		$this->temporaryFile = $temporaryFile;
		return true;
	}
	
	/**
	 * Sets the location of the temporary file for OCR input.
	 * @param string $temporaryImageFile
	 */
	public function setTemporaryImageFile($temporaryImageFile) {
		$this->temporaryImageFile = $temporaryImageFile;
		return true;
	}
	
	/**
	 * @param string $tesseractCommand
	 */
	public function setTesseractCommand($tesseractCommand) {
		$this->tesseractCommand = $tesseractCommand;
	}

	/**
	 * Finds the y-coordinate of the highest point of a bar.
	 * @param int $x x-coordinate to look at
	 */
	private function findHighestYOnBar($x) {
		for ($y = 0; $y < $this->imageDimensions['height']; $y++) {
			$color = $this->image->getImagePixelColor($x, $y)->getcolor(false);
			if ($color === self::$barTopLineColor || $color === self::$noBarLineColor) {
				return $y;
			}
		}
		return false;
	}
	
	/**
	 * Makes image for OCR'ing with left-bottom corner specified
	 * @param int $x
	 * @param int $y
	 */
	private function makeOcrImage($x, $y, $outputFile) {
		$ocrImage = $this->image->clone();
		$ocrImage->cropImage($this->columnWidth, 14, $x, $y - 15);
		$dimensions = $ocrImage->getImageGeometry();
		$ocrImage->resizeImage($dimensions['width'] * 10, $dimensions['height'] * 10,
			imagick::FILTER_LANCZOS, 1);
		for ($i = 0; $i < 4; $i++) {
			$ocrImage->contrastImage(1);
		}
		$ocrImage->blackThresholdImage('grey');
		$ocrImage->setImageDepth(1);
		$ocrImage->writeImage($outputFile);
		
		return true;
	}
	
	private static function sanitizeOcrOutput($output) {
		$output = trim($output);
		$output = str_replace('$', '5', $output);
		$output = str_replace(':', '=', $output);
		$output = str_ireplace('i', '1', $output);
		$output = str_ireplace('o', '0', $output);
		return $output;
	}
	
	private static function extractNumberFromOcrOutput($output) {
		preg_match('@[0-9]*$@', $output, $matches);
		return ((int) $matches[0]);
	}
	
	public function ocrGraph($numbersOnly) {
		$return = array();
		$temporaryImageFile = escapeshellarg($this->temporaryImageFile);
		$temporaryFile = escapeshellarg($this->temporaryFile);
		
		reset($this->returnKeys);
		foreach ($this->columnsLeftEdges as &$x) {
			$y = $this->findHighestYOnBar($x);
			
			$this->makeOcrImage($x, $y, $this->temporaryImageFile);
			shell_exec("{$this->tesseractCommand} {$temporaryImageFile} {$temporaryFile} 2> NUL");
			$output = file_get_contents("{$this->temporaryFile}.txt");
			if (!$output) {
				exit;
			}
			unlink("{$this->temporaryFile}.txt");
			unlink($this->temporaryImageFile);
			
			$entry = self::sanitizeOcrOutput($output);
			if ($numbersOnly) {
				$entry = self::extractNumberFromOcrOutput($entry);
			}
			
			$key = each($this->returnKeys);
			if ($key === false) {
				$return[] = $entry;
			} else {
				$key = $key['value'];
				$return[$key] = $entry;
			}
		}
		return $return;
	}
}