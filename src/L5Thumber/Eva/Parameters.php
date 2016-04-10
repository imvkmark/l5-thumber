<?php namespace Imvkmark\L5Thumber\Eva;


class Parameters {

	/** @var  string 边框 */
	protected $border;

	/**
	 * @var  string 可以自动获得优质的图片素材
	 *    d_picasa， 从Picasa获得图片
	 *    d_flickr，从Flickr获得图片
	 */
	protected $dummy;

	/**
	 * @var string
	 * 基本正方形剪裁 c_[int Crop]:
	 * c_ 允许输入一个整数，如c_50会从图片的中心位置截取出一张50px*50px的缩略图
	 */
	protected $crop;
	protected $filter;
	/**
	 * @var string
	 * 输入g_，代表指定剪裁的宽度与高度
	 */
	protected $gravity;
	protected $height;
	protected $width;
	protected $layer;
	protected $percent;
	protected $quality;
	protected $rotate;
	/**
	 * @var string
	 * 如果想要指定剪裁的精确位置，需要用x_和y_参数指定起点坐标，x_0,y_0 以图片左上角为坐标原点。
	 */
	protected $x;

	/**
	 * @var string
	 * 如果想要指定剪裁的精确位置，需要用x_和y_参数指定起点坐标，x_0,y_0 以图片左上角为坐标原点。
	 */
	protected $y;
	protected $extension;
	protected $filename;

	protected $imageWidth;
	protected $imageHeight;

	protected $argMapping = [
		'b' => 'border',
		'c' => 'crop',
		'd' => 'dummy',
		'f' => 'filter',
		'g' => 'gravity',
		'h' => 'height',
		'l' => 'layer',
		'p' => 'percent',
		'q' => 'quality',
		'r' => 'rotate',
		'w' => 'width',
		'x' => 'x',
		'y' => 'y',
	];

	protected $argDefaults = [
		'border'  => null,
		'crop'    => 'crop',
		'dummy'   => null, //picasa | flickr
		'filter'  => null,
		'gravity' => null,
		'height'  => null,
		'percent' => 100,
		'quality' => 100,
		'rotate'  => 360,
		'width'   => null,
		'layer'   => null,
		'x'       => null,
		'y'       => null,
	];

	protected $config;

	public function setCrop($crop) {
		$crops = ['crop', 'fill'];
		if (is_numeric($crop)) {
			$crop = (int) $crop;
		} elseif (is_string($crop)) {
			$crop = strtolower($crop);
			if (!in_array($crop, $crops)) {
				$crop = $crops[1];
			}
		}
		$this->crop = $crop;
		return $this;
	}

	public function getCrop() {
		if ($this->crop) {
			return $this->crop;
		}
		return $this->crop = $this->argDefaults['crop'];
	}

	public function setBorder($border) {
		$this->border = $border;
		return $this;
	}

	public function getBorder() {
		return $this->border;
	}

	public function setDummy($dummy) {
		$dummy = strtolower($dummy);
		if (false === in_array($dummy, ['flickr', 'picasa'])) {
			$this->dummy = null;
			return $this;
		}
		$this->dummy = $dummy;
		return $this;
	}

	public function getDummy() {
		return $this->dummy;
	}

	public function setFilter($filter) {
		$filter = strtolower($filter);
		if (false === in_array($filter, [
				'gray',
				'negative',
				'gamma',
				'sharp',
				'lomo',
				'carve',
				'softenface',
			])
		) {
			$this->filter = null;
			return $this;
		}
		$this->filter = $filter;
		return $this;
	}

	public function getFilter() {
		return $this->filter;
	}

	public function getGravity() {
		return $this->gravity;
	}

	public function setGravity($gravity) {
		$gravities = ['top', 'bottom', 'left', 'right'];
		if (is_numeric($gravity)) {
			$gravity = (int) $gravity;
		} elseif (is_string($gravity)) {
			$gravity = strtolower($gravity);
			if (false === in_array($gravity, $gravities)) {
				$gravity = null;
			}
		}
		$this->gravity = $gravity;
		return $this;
	}

	public function getLayer() {
		return $this->layer;
	}

	public function setLayer($layer) {
		$this->layer = $layer;
		return $this;
	}

	public function setPercent($percent) {
		$percent       = (int) $percent;
		$percent       = $percent > 100 ? 100 : $percent;
		$percent       = $percent < 1 ? 1 : $percent;
		$this->percent = $percent;
		return $this;
	}

	public function getPercent() {
		return $this->percent;
	}

	public function getQuality() {
		if ($this->quality) {
			return $this->quality;
		}

		return $this->quality = $this->argDefaults['quality'];
	}

	public function setQuality($quality) {
		$extension = $this->getExtension();
		if (false === in_array($extension, ['jpg', 'jpeg'])) {
			$this->quality = null;
			return $this;
		}
		$this->quality = (int) $quality;
		return $this;
	}

	public function getWidth() {
		return $this->width;
	}

	public function setWidth($width) {
		$width = (int) $width;
		/*
		if(!$this->config->allow_stretch){
			$maxWidth = $this->argDefaults['width'];
			$width = $maxWidth && $width > $maxWidth ? $maxWidth : $width;
		}
		*/
		$this->width = $width;
		return $this;
	}

	public function getHeight() {
		return $this->height;
	}

	public function setHeight($height) {
		$this->height = (int) $height;
		return $this;
	}

	public function getX() {
		return $this->x;
	}

	public function setX($x) {
		$this->x = (int) $x;
		return $this;
	}

	public function getY() {
		return $this->y;
	}

	public function setY($y) {
		$this->y = (int) $y;
		return $this;
	}

	public function getRotate() {
		return $this->rotate;
	}

	public function setRotate($rotate) {
		$rotate = (int) $rotate;
		//rotate is between 1 ~ 360
		$this->rotate = $rotate % 360;
		return $this;
	}

	/**
	 * @return mixed 获取扩展名
	 */
	public function getExtension() {
		if (!$this->extension) {
			throw new Exception\InvalidArgumentException(sprintf('File extension not be set in parameters'));
		}
		return $this->extension;
	}

	public function setExtension($extension) {
		$this->extension = strtolower($extension);
		return $this;
	}

	public function getFilename() {
		if (!$this->filename) {
			throw new Exception\InvalidArgumentException(sprintf('Filename not be set in parameters'));
		}
		return $this->filename;
	}

	public function setFilename($filename) {
		$this->filename = $filename;
		return $this;
	}


	public function setConfig(Config\Config $config) {
		$this->config = $config;
		return $this;
	}

	public function getConfig() {
		return $this->config;
	}

	public function setImageSize($imageWidth, $imageHeight) {
		$this->imageWidth  = $imageWidth;
		$this->imageHeight = $imageHeight;
		$this->normalize();
		return $this;
	}

	/**
	 * Populate from native PHP array
	 * @param  array $values
	 * @return Parameters
	 */
	public function fromArray(array $params) {
		foreach ($params as $key => $value) {
			$method = 'set' . ucfirst($key);
			if (method_exists($this, $method)) {
				$this->$method($value);
			}
		}
		$this->normalize();
		return $this;
	}

	/**
	 * Populate from filename string
	 * @param  string $string
	 * @return Parameters
	 */
	public function fromString($fileName) {
		$fileNameArray = $fileName ? explode('.', $fileName) : [];
		if (!$fileNameArray || count($fileNameArray) < 2) {
			throw new Exception\InvalidArgumentException('File name not correct');
		}

		$fileExt       = array_pop($fileNameArray);
		$fileNameMain  = implode('.', $fileNameArray);
		$fileNameArray = explode(',', $fileNameMain);
		if (!$fileExt || !$fileNameArray || !$fileNameArray[0]) {
			throw new Exception\InvalidArgumentException('File name not correct');
		}

		//remove empty elements
		$fileNameArray = array_filter($fileNameArray);
		$fileNameMain  = array_shift($fileNameArray);
		$this->setExtension($fileExt);
		$this->setFilename($fileNameMain);

		$args       = $fileNameArray;
		$argMapping = $this->argMapping;
		$params     = [];
		foreach ($args as $arg) {
			if (!$arg) {
				continue;
			}
			if (strlen($arg) < 3 || strpos($arg, '_') !== 1) {
				continue;
			}
			$argKey = $arg{0};
			if (isset($argMapping[$argKey])) {
				$arg = substr($arg, 2);
				if ($arg !== '') {
					$params[$argMapping[$argKey]] = $arg;
				}
			}
		}

		$this->fromArray($params);
		return $params;
	}

	/**
	 * Serialize to native PHP array
	 * @return array
	 */
	public function toArray() {
		return [
			'filter'    => $this->getFilter(),
			'width'     => $this->getWidth(),
			'height'    => $this->getHeight(),
			'percent'   => $this->getPercent(),
			'dummy'     => $this->getDummy(),
			'border'    => $this->getBorder(),
			'layer'     => $this->getLayer(),
			'quality'   => $this->getQuality(),
			'crop'      => $this->getCrop(),
			'x'         => $this->getX(),
			'y'         => $this->getY(),
			'rotate'    => $this->getRotate(),
			'gravity'   => $this->getGravity(),
			'extension' => $this->getExtension(),
			'filename'  => $this->getFilename(),
		];
	}

	/**
	 * Serialize to query string
	 * @return string
	 */
	public function toString() {
		$params    = $this->toArray();
		$filename  = $params['filename'];
		$extension = $params['extension'];
		unset($params['filename'], $params['extension']);

		ksort($params);
		$mapping  = array_flip($this->argMapping);
		$defaults = $this->argDefaults;

		$nameArray = [];
		foreach ($params as $key => $value) {
			//remove value if as same as default setting
			if ($value !== null && $value !== $defaults[$key]) {
				$nameArray[$mapping[$key]] = $mapping[$key] . '_' . $value;
			}
		}
		$nameArray = $nameArray ? ',' . implode(',', $nameArray) : '';
		return $filename . $nameArray . '.' . $extension;
	}

	/**
	 * 禁用某些操作， 将某些操作设置为 false
	 * @param Config\Config $disabledOperates
	 * @return $this
	 */
	public function disableOperates(Config\Config $disabledOperates) {
		foreach ($disabledOperates as $key => $operate) {
			if (isset($this->$operate)) {
				$this->$operate = null;
			}
		}
		return $this;
	}

	/**
	 * Constructor
	 * Enforces that we have an array, and enforces parameter access to array
	 * elements.
	 * @param  array $values
	 */
	public function __construct($imageName = null, Config\Config $config = null) {
		if ($imageName && is_string($imageName)) {
			$this->fromString($imageName);
		}

		if ($imageName && is_array($imageName)) {
			$this->fromArray($imageName);
		}
	}

	protected function normalize() {
		//set default here;
		$defaults = $this->argDefaults;
		$config   = $this->getConfig();

		//Max width & height from config
		if ($config) {
			$maxWidth  = $config->max_width;
			$maxHeight = $config->max_height;
			if ($maxWidth) {
				$defaults['width'] = $maxWidth;
			}
			if ($maxHeight) {
				$defaults['height'] = $maxHeight;
			}

			//Change max width & height as image size if small than config
			$imageWidth   = $this->imageWidth;
			$imageHeight  = $this->imageHeight;
			$allowStretch = $config->allow_stretch;
			if ($imageWidth && $imageHeight) {
				if ($maxWidth && $maxWidth < $imageWidth) {
					$defaults['width'] = $maxWidth;
				} else {
					$maxWidth          = $allowStretch ? $maxWidth : $imageWidth;
					$defaults['width'] = $maxWidth;
				}

				if ($maxHeight && $maxHeight < $imageHeight) {
					$defaults['height'] = $maxHeight;
				} else {
					$maxHeight          = $allowStretch ? $maxHeight : $imageHeight;
					$defaults['height'] = $maxHeight;
				}
			}

			//Width & height Limit
			$width  = $this->width;
			$height = $this->height;
			if ($width && $maxWidth) {
				$this->width = $width = $width > $maxWidth ? $maxWidth : $width;
			}
			if ($height && $maxHeight) {
				$this->height = $height = $height > $maxHeight ? $maxHeight : $height;
			}


			if ($config->allow_sizes && $config->allow_sizes->count() > 0) {
				$allowSizes = $config->allow_sizes;
				$matched    = false;
				foreach ($allowSizes as $allowSize) {
					list($allowWidth, $allowHeight) = explode('*', $allowSize);
					if ($allowWidth && $width == $allowWidth && $allowHeight && $height == $allowHeight) {
						$matched = true;
						break;
					}
				}
				if (false === $matched) {
					$this->width  = $width = null;
					$this->height = $height = null;
				}
			}

			if ($config->quality) {
				$defaults['quality'] = $config->quality;
			}
		}

		//X & Y only need when cropping
		if (!$this->crop || $this->crop == 'fill') {
			$this->x = null;
			$this->y = null;
		}

		//fill mode request both width & height
		if ($this->crop == 'fill' & (!$this->width || !$this->height)) {
			$defaults['crop'] = 'fill';
		}

		if (is_numeric($this->crop)) {
			if ($this->x === null || $this->y === null) {
				$this->x = null;
				$this->y = null;
			}
		}

		//In fill mode, gravity could only be a string
		if ('fill' === $this->crop) {
			//fill mode not allow x/y
			$this->x = null;
			$this->y = null;

			if ($this->gravity && is_numeric($this->gravity)) {
				$this->gravity = null;
			}
		}

		if ($this->percent) {
			$this->width  = null;
			$this->height = null;
		}

		$this->argDefaults = $defaults;

		return $this;
	}
}
