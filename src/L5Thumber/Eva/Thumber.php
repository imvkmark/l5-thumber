<?php namespace Imvkmark\L5Thumber\Eva;

use Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Imvkmark\L5Thumber\Eva\Config\Config;

/**
 * 图片的缩略图处理
 * Class Thumber
 * @package Imvkmark\L5Thumber\Eva
 */
class Thumber {

	/**
	 * Thumber config 对象
	 * @var \Imvkmark\L5Thumber\Eva\Config\Config
	 */
	protected $config;

	/**
	 * 图片处理类
	 * @var Imagine\Image\ImageInterface
	 */
	protected $image;

	/**
	 * 图片选项
	 * @var array
	 */
	protected $imageOptions = [];

	/**
	 * 图片处理类
	 * @var Imagine\Image\ImagineInterface
	 */
	protected $thumber;

	/**
	 * 保存的地址
	 * @var Url
	 */
	protected $url;

	/**
	 * 参数
	 * @var Parameters
	 */
	protected $params;

	/**
	 * 文件系统
	 * @type
	 */
	protected $filesystem;

	/**
	 * 源文件
	 * @var mixed
	 */
	protected $sourceFile;

	/**
	 * @type
	 */
	protected $faker;

	/**
	 * @type
	 */
	protected $cacher;

	/**
	 * 进程中
	 * @type bool
	 */
	protected $processed = false;

	/**
	 * 是否进行图片优化
	 * @type bool
	 */
	protected $optimized = false;

	/**
	 * 优化的图像
	 * @type
	 */
	protected $optimizedImage;


	/**
	 * 缓存器
	 * @return Cacher
	 */
	public function getCacher() {
		if ($this->cacher) {
			return $this->cacher;
		}
		return $this->cacher = new Cacher();
	}

	public function setCacher(Cacher $cacher) {
		$this->cacher = $cacher;
		return $this;
	}

	public function getThumber($sourceFile = null, $adapter = null) {
		if ($this->thumber) {
			return $this->thumber;
		}

		$thumber = $this->createThumber($adapter);

		if ($sourceFile) {
			$this->image = $thumber->open($sourceFile);
		}
		return $this->thumber = $thumber;
	}

	public function getFaker($dummyName) {
		if ($this->faker) {
			return $this->faker;
		}

		return $this->faker = new Faker($dummyName);
	}

	public function setThumber(ImagineInterface $thumber) {
		$this->thumber = $thumber;
		return $this;
	}

	public function getImage() {
		return $this->image;
	}

	public function getEffect() {
		$className = get_class($this->image);
		return 'EvaThumber\\' . $className($this->image);
	}

	public function getUrl() {
		return $this->url;
	}

	public function getImageOptions() {
		return $this->imageOptions;
	}

	/**
	 * Set configuration object
	 * @param  Config $config
	 * @return Thumber
	 */
	public function setConfig(Config $config) {
		$this->config = $config;
		return $this;
	}

	/**
	 * Retrieve configuration object
	 * @return Config
	 */
	public function getConfig() {
		return $this->config;
	}

	public function getFilesystem() {
		if ($this->filesystem) {
			return $this->filesystem;
		}
		return $this->filesystem = new Filesystem();
	}

	/**
	 * 获取源文件位置地址
	 * @return mixed|string
	 */
	public function getSourceFile() {
		if ($this->sourceFile) {
			return $this->sourceFile;
		}

		// ~/uploads
		$fileRootPath = $this->config->source_path;
		// /some/directory
		$filePath = $this->url->getImagePath();
		// demo.jpg
		$fileName = $this->url->getImageName();
		if (is_dir($fileRootPath)) {
			if (!$fileName) {
				throw new Exception\InvalidArgumentException(sprintf("Request an empty filename"));
			}
			$sourceFile     = $fileRootPath . $filePath . '/' . $fileName;
			$systemEncoding = $this->config->get('system_file_encoding');
			$sourceFile     = urldecode($sourceFile);
			if ($systemEncoding || $systemEncoding != 'UTF-8') {
				$sourceFile = iconv('UTF-8', $this->config->get('system_file_encoding'), $sourceFile);
			}
		} elseif (is_file($fileRootPath)) {
			if (!Feature\ZipReader::isSupport()) {
				throw new Exception\BadFunctionCallException(sprintf("Your system not support ZipArchive feature"));
			}
			$sourceFile = Feature\ZipReader::getStreamPath(urldecode($filePath . '/' . $fileName), $fileRootPath, $this->config->get('zip_file_encoding'));
		} else {
			throw new Exception\IOException(sprintf(
				"Source file not readable %s", $fileRootPath
			));

		}

		return $this->sourceFile = $sourceFile;
	}

	public function setSourceFile($sourceFile) {
		$this->sourceFile = $sourceFile;
		return $this;
	}

	public function sourceFileExist() {
		$sourceFile     = $this->getSourceFile();
		$sourceFilePath = substr($sourceFile, 0, strrpos($sourceFile, '.'));
		$fileExist      = false;

		if (0 === strpos($sourceFilePath, 'zip://')) {
			$files = Feature\ZipReader::glob($sourceFilePath . '.*');
			if ($files) {
				$streamPath = Feature\ZipReader::parseStreamPath($sourceFile);
				$sourceFile = Feature\ZipReader::getStreamPath($files[0]['name'], $streamPath['zipfile']);
				$this->setSourceFile($sourceFile);
				$fileExist = true;
			}
		} else {
			//Not use file system, instead of glob
			foreach (glob($sourceFilePath . '.*') as $sourceFile) {
				$this->setSourceFile($sourceFile);
				$fileExist = true;
				break;
			}
		}
		return $fileExist;
	}

	public function getParameters() {
		if ($this->params) {
			return $this->params;
		}

		$params = new Parameters();
		$params->setConfig($this->config);
		$params->fromString($this->url->getUrlImageName());
		return $this->params = $params;
	}

	public function setParameters(Parameters $params) {
		$this->params = $params;
		return $this;
	}

	/**
	 * 重定向到新的地址
	 * @param $imageName
	 */
	public function redirect($imageName) {
		$this->getUrl()->setUrlImageName($imageName);
		$newUrl = $this->getUrl()->toString();
		header("Location:$newUrl"); //+old url + server referer
	}

	public function save($path = null) {
		return $this->saveImage($path);
	}

	public function show() {
		$config    = $this->getConfig();
		$extension = $this->getParameters()->getExtension();
		$this->process();
		$this->getImage();
		if ($config->get('cache')) {
			$this->saveImage();
		}
		return $this->showImage($extension);
	}


	public function __construct(Config $config, $url = null) {
		$this->url    = new Url($url, $config);
		$this->config = $this->url->getConfig();
	}

	public function __destruct() {
		if ($this->optimizedImage) {
			unlink($this->optimizedImage);
		}
	}

	protected function saveImage($path = null) {
		if (!$path) {
			$config    = $this->getConfig();
			$cacheRoot = $config->thumb_cache_path;
			// ~/thumber/config/some/directory
			$imagePath = '/' . $this->getUrl()->getRoute() . '/' . $this->getUrl()->getConfigKey() . $this->getUrl()->getImagePath();
			// ~/thumber/config/some/directory/demo,q_60,w_122.jpg
			$cachePath = $cacheRoot . $imagePath . '/' . $this->getUrl()->getUrlImageName();
			$pathLevel = count(explode('/', $imagePath));
			$this->getFilesystem()->prepareDirectoryStructure($cachePath, $pathLevel);
			$path = $cachePath;
		}

		if (true === $this->optimized) {
			return $this->saveOptimizedImage($path);
		}

		return $this->saveNormalImage($path);
	}

	protected function saveNormalImage($path = null) {
		return $this->getImage()->save($path, $this->getImageOptions());
	}

	protected function saveOptimizedImage($path = null) {
		copy($this->optimizedImage, $path);
		return true;
	}

	protected function showImage($extension) {
		if (true === $this->optimized) {
			return $this->showOptimizedImage($extension);
		}
		return $this->showNormalImage($extension);
	}

	protected function showNormalImage($extension) {
		ob_clean();
		ob_start();
		$imageOption = $this->getImageOptions();
		echo $this->getImage()->get($extension, $imageOption);
		$content = ob_get_clean();
		$mime    = $this->getMimeType($extension);
		return response($content, 200, [
			'Content-Type' => $mime,
		]);
	}

	protected function showOptimizedImage($extension) {
		ob_clean();
		ob_start();
		$handle = fopen($this->optimizedImage, "r");
		echo stream_get_contents($handle);
		unlink($this->optimizedImage);
		fclose($handle);
		$content = ob_get_clean();
		$mime    = $this->getMimeType($extension);
		return response($content, 200, [
			'Content-Type' => $mime,
		]);
	}

	private function getMimeType($extension) {
		static $mimeTypes = [
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'gif'  => 'image/gif',
			'png'  => 'image/png',
			'wbmp' => 'image/vnd.wap.wbmp',
			'xbm'  => 'image/xbm',
		];
		return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : $mimeTypes['png'];
	}

	protected function process() {
		if (true === $this->processed) {
			return $this;
		}

		$config = $this->getConfig();
		$params = $this->getParameters();
		$params->disableOperates($config->disable_operates);
		$url          = $this->getUrl();
		$urlImageName = $url->getUrlImageName();
		$newImageName = $params->toString();

		//Keep unique url
		if ($urlImageName !== $newImageName) {
			$this->redirect($newImageName);
		}

		//Dummy file will replace source file
		$dummy = $params->getDummy();
		if ($this->sourceFileExist()) {
			if ($dummy) {
				throw new Exception\IOException(sprintf(
					"Dummy file name conflict with exist file %s", $this->getSourceFile()
				));
			}
		} else {
			if (!$dummy) {
				throw new Exception\IOException(sprintf(
					"Request file not find in %s", $this->getSourceFile()
				));
			}
		}
		if ($dummy) {
			$faker      = $this->getFaker($dummy);
			$sourceFile = $faker->getFile();
		} else {
			$sourceFile = $this->getSourceFile();
		}

		//Start reading file
		$this->getThumber($sourceFile);
		$params->setImageSize(
			$this->getImage()->getSize()->getWidth(),
			$this->getImage()->getSize()->getHeight()
		);
		$newImageName = $params->toString();

		//Keep unique url again when got image width & height
		if ($urlImageName !== $newImageName) {
			$this->redirect($newImageName);
		}

		$this
			->crop()//crop first then resize
			->resize()
			->rotate()
			->filter()
			->blending()
			->layer()
			->quality()
			->optimize();

		$this->processed = true;
		return $this;
	}

	/**
	 * 缩略图处理器
	 * @param null $adapter
	 * @return Imagine\Gd\Imagine|Imagine\Gmagick\Imagine|Imagine\Imagick\Imagine
	 */
	protected function createThumber($adapter = null) {
		$adapter = $adapter ? $adapter : strtolower($this->config->get('adapter'));
		switch ($adapter) {
			case 'gd':
				$thumber = new Imagine\Gd\Imagine();
				break;
			case 'imagick':
				$thumber = new Imagine\Imagick\Imagine();
				break;
			case 'gmagick':
				$thumber = new Imagine\Gmagick\Imagine();
				break;
			default:
				$thumber = new Imagine\Gd\Imagine();
		}
		return $thumber;
	}

	protected function createFont($font, $size, $color) {
		$thumberClass = get_class($this->getThumber());
		$classPart    = explode('\\', $thumberClass);
		$classPart[2] = 'Font';
		$fontClass    = implode('\\', $classPart);
		return new $fontClass($font, $size, $color);
	}


	protected function crop() {
		$params = $this->getParameters();
		$crop   = $params->getCrop();
		if (!$crop) {
			return $this;
		}

		$image = $this->getImage();
		if ($crop === 'face') {
			if (false === Feature\FaceDetect::isSupport()) {
				throw new Exception\BadFunctionCallException(sprintf('No support face detection feature'));
			}

			$feature  = new Feature\FaceDetect($this->config->face_detect->bin, $this->config->face_detect->cascade);
			$faceData = $feature->filterDump($this->getImage());

			if (!$faceData || $faceData->faces < 1) {
				return $this;
			}

			if ($this->config->face_detect->draw_border) {
				foreach ($faceData->data as $data) {
					$x = $data->x + $data->w / 2;
					$y = $data->y + $data->h / 2;
					$image->draw()->ellipse(new Point($x, $y), new Box($data->w, $data->h), new Color('fff'));
				}
			}

			$width  = $params->getWidth();
			$height = $params->getHeight();
			if ($width && $height) {
				$newX        = $x - $width / 2 > 0 ? $x - $width / 2 : 0;
				$newY        = $y - $height / 2 > 0 ? $y - $height / 2 : 0;
				$this->image = $image->crop(new Imagine\Image\Point($newX, $newY), new Imagine\Image\Box($width, $height));

			}
			return $this;
		}

		$gravity = $params->getGravity();
		if (false === is_numeric($crop)) {
			return $this;
		}

		$gravity = $gravity ? $gravity : $crop;

		$x = $params->getX();
		$y = $params->getY();

		$imageWidth  = $image->getSize()->getWidth();
		$imageHeight = $image->getSize()->getHeight();

		$x = $x !== null ? $x : ($imageWidth - $crop) / 2;
		$y = $y !== null ? $y : ($imageHeight - $gravity) / 2;

		$this->image = $image->crop(new Imagine\Image\Point($x, $y), new Imagine\Image\Box($crop, $gravity));
		return $this;
	}

	protected function resize() {
		$params  = $this->getParameters();
		$percent = $params->getPercent();

		if ($percent) {
			$this->resizeByPercent();
		} else {
			$this->resizeBySize();
		}
		return $this;
	}

	protected function resizeBySize() {
		$params = $this->getParameters();

		$width     = $params->getWidth();
		$height    = $params->getHeight();
		$maxWidth  = $this->config->max_width;
		$maxHeight = $this->config->max_height;

		$image       = $this->getImage();
		$imageWidth  = $image->getSize()->getWidth();
		$imageHeight = $image->getSize()->getHeight();

		//No size input, require size limit from config
		if (!$width && !$height) {
			if (!$maxWidth && !$maxHeight) {
				return $this;
			}

			if ($maxWidth && $imageWidth > $maxWidth || $maxHeight && $imageHeight > $maxHeight) {
				$width  = $maxWidth && $imageWidth > $maxWidth ? $maxWidth : $width;
				$height = $maxHeight && $imageHeight > $maxHeight ? $maxHeight : $height;

				//If only width or height, resize by image size radio
				$width  = $width ? $width : ceil($height * $imageWidth / $imageHeight);
				$height = $height ? $height : ceil($width * $imageHeight / $imageWidth);
			} else {
				return $this;
			}

		} else {
			if ($width === $imageWidth || $height === $imageHeight) {
				return $this;
			}

			//If only width or height, resize by image size radio
			$width  = $width ? $width : ceil($height * $imageWidth / $imageHeight);
			$height = $height ? $height : ceil($width * $imageHeight / $imageWidth);

			$allowStretch = $this->config->allow_stretch;

			if (!$allowStretch) {
				$width  = $width > $maxWidth ? $maxWidth : $width;
				$width  = $width > $imageWidth ? $imageWidth : $width;
				$height = $height > $maxHeight ? $maxHeight : $height;
				$height = $height > $imageHeight ? $imageHeight : $height;
			}
		}

		$size = new Imagine\Image\Box($width, $height);
		$crop = $params->getCrop();
		if ($crop === 'fill') {
			$mode = Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;
		} else {
			$mode = Imagine\Image\ImageInterface::THUMBNAIL_INSET;
		}
		$this->image = $image->thumbnail($size, $mode);
		return $this;
	}

	protected function resizeByPercent() {
		$params  = $this->getParameters();
		$percent = $this->params->getPercent();

		if (!$percent || $percent == 100) {
			return $this;
		}

		$image       = $this->getImage();
		$imageWidth  = $image->getSize()->getWidth();
		$imageHeight = $image->getSize()->getHeight();

		$box         = new Imagine\Image\Box($imageWidth, $imageHeight);
		$mode        = Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;
		$box         = $box->scale($percent / 100);
		$this->image = $image->thumbnail($box, $mode);
		return $this;
	}


	protected function rotate() {
		$rotate = $this->getParameters()->getRotate();
		if ($rotate) {
			$image = $this->getImage();
			$image->rotate($rotate);
		}
		return $this;
	}

	protected function filter() {
		$filter = $this->getParameters()->getFilter();
		if (!$filter) {
			return $this;
		}

		/** @type Imagine\Image\ImageInterface $image */
		$image   = $this->getImage();
		$effects = $this->getImage()->effects('EvaThumber\\' . get_class($image) . '\\Effects');
		$blend   = 'EvaThumber\\' . get_class($image) . '\\Blend::layer';

		switch ($filter) {
			case 'gray':
				$effects->grayscale();
				break;
			case 'gamma':
				$effects->gamma(0.7);
				break;
			case 'negative':
				$effects->negative();
				break;
			case 'sharp':
				//only in imagine develop version
				$effects->sharpen();
				break;
			case 'carve':
				$layer = $image->copy();
				$image->effects('EvaThumber\\' . get_class($layer) . '\\Effects')->mosaic()->borderline()->emboss();
				$image->paste($layer, new Point(0, 0), 100, $blend . 'VividLight');
				break;
			case 'softenface':
				$layer = $image->copy();
				$image->effects('EvaThumber\\' . get_class($layer) . '\\Effects')->gaussBlur();
				$image->paste($layer, new Point(0, 0), 100, $blend . 'Screen');
				$effects->brightness(-10);
				break;
			case 'lomo':

				break;
			default:
		}
		return $this;
	}

	protected function quality() {
		$quality = $this->getParameters()->getQuality();
		if ($quality) {
			$this->imageOptions['quality'] = $quality;
		}
		return $this;
	}

	protected function border() {
		return $this;
	}

	protected function layer() {
		$config = $this->config->watermark;
		if (!$config || !$config->enable) {
			return $this;
		}

		$textLayer = false;
		$text      = $config->text;
		if ($config->layer_file) {
			$waterLayer  = $this->createThumber()->open($config->layer_file);
			$layerWidth  = $waterLayer->getSize()->getWidth();
			$layerHeight = $waterLayer->getSize()->getHeight();
		} else {
			if (!$text || !$config->font_file || !$config->font_size || !$config->font_color) {
				return $this;
			}

			if ($config->qr_code && Feature\QRCode::isSupport()) {
				$layerFile   = Feature\QRCode::generateQRCodeLayer($text, $config->qr_code_size, $config->qr_code_margin);
				$waterLayer  = $this->createThumber()->open($layerFile);
				$layerWidth  = $waterLayer->getSize()->getWidth();
				$layerHeight = $waterLayer->getSize()->getHeight();
			} else {
				$font        = $this->createFont($config->font_file, $config->font_size, new Imagine\Image\Color($config->font_color));
				$layerBox    = $font->box($text);
				$layerWidth  = $layerBox->getWidth();
				$layerHeight = $layerBox->getHeight();
				$textLayer   = true;
			}
		}

		$image       = $this->getImage();
		$imageWidth  = $image->getSize()->getWidth();
		$imageHeight = $image->getSize()->getHeight();

		$x        = 0;
		$y        = 0;
		$position = $config->position;
		switch ($position) {
			case 'tl':
				break;

			case 'tr':
				$x = $imageWidth - $layerWidth;
				break;

			case 'bl':
				$y = $imageHeight - $layerHeight;
				break;

			case 'center':
				$x = ($imageWidth - $layerWidth) / 2;
				$y = ($imageHeight - $layerHeight) / 2;
				break;

			case 'br':
			default:
				$x = $imageWidth - $layerWidth;
				$y = $imageHeight - $layerHeight;
		}
		$point = new Imagine\Image\Point($x, $y);

		if ($textLayer) {
			$this->getImage()->draw()->text($text, $font, $point);
		} else {
			$this->image = $this->getImage()->paste($waterLayer, $point);
		}

		return $this;
	}

	protected function blending() {
		$blendingFile   = $this->config->blending_layer;
		$blendingEffect = $this->getParameters()->getLayer();
		if (!$blendingEffect) {
			return $this;
		}
		if (!$blendingFile) {
			throw new Exception\InvalidArgumentException(sprintf(
				'Blending file not found'
			));
		}

		$image = $this->getImage();
		$layer = $this->createThumber()->open($blendingFile);

		$blendFunc = 'EvaThumber\\' . get_class($image) . '\\Blend::layer' . $blendingEffect;

		if (true !== is_callable($blendFunc)) {
			throw new Exception\InvalidArgumentException(sprintf(
				'Request blending effect %s not exist', $blendingEffect
			));
		}

		$image->paste($layer, new Point(0, 0), 100, $blendFunc);

		return $this;
	}

	protected function optimize() {
		$extension = $this->getParameters()->getExtension();
		if ($extension === 'gif') {
			return $this;
		}

		$config = $this->getConfig();
		if ($extension === 'png' && $config->png_optimize->enable) {
			$featureClass = 'EvaThumber\Feature\Pngout';
			if (false === $featureClass::isSupport()) {
				return $this;
			}

			$feature              = new $featureClass($config->png_optimize->pngout->bin);
			$this->optimizedImage = $feature->filterDump($this->getImage());
			if ($this->optimizedImage) {
				$this->optimized = true;
			}
		}

		return $this;
	}

}
