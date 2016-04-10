<?php namespace Imvkmark\L5Thumber\Eva\Feature;


use Imvkmark\L5Thumber\Eva\Exception;
use ZipArchive;

class ZipReader implements FeatureInterface {

	public static function isSupport() {
		if (false === class_exists('ZipArchive')) {
			return false;
		}

		return true;
	}

	public static function getStreamPath($filePath, $zipfile, $fileEncoding = null) {
		$filePath = ltrim($filePath, '/');
		$filePath = $fileEncoding ? iconv('UTF-8', $fileEncoding, $filePath) : $filePath;
		return 'zip://' . $zipfile . '#' . $filePath;
	}

	public static function parseStreamPath($steamPath) {
		preg_match('/zip:\/\/([^#]+)#(.+)/', $steamPath, $matches);
		if (count($matches) < 3) {
			return false;
		}
		return [
			'zipfile'   => $matches[1],
			'innerpath' => $matches[2],
		];
	}

	public static function glob($globPath, $fileEncoding = 'UTF-8') {
		$zipStream = self::parseStreamPath($globPath);
		if (!$zipStream) {
			return [];
		}

		$zipfile   = $zipStream['zipfile'];
		$innerpath = $zipStream['innerpath'];
		$zip       = new ZipArchive();
		$res       = $zip->open($zipfile);

		if (!$res) {
			return [];
		}

		$files    = [];
		$i        = 0;
		$numFiles = $zip->numFiles;
		for ($i; $i < $numFiles; $i++) {
			$file     = $zip->statIndex($i);
			$filename = $file['name'];
			$filename = substr($filename, 0, strrpos($filename, '.')) . '.*';
			if ($innerpath === $filename) {
				$files[] = $file;
			}
		}
		return $files;
	}

	public static function read($filePath, $zipfile, $fileEncoding = 'UTF-8') {
		$zip      = new ZipArchive();
		$filePath = ltrim($filePath, '/');
		$filePath = $fileEncoding ? iconv('UTF-8', $fileEncoding, $filePath) : $filePath;
		$res      = $zip->open($zipfile);

		$file = [];
		if ($res) {
			$i        = 0;
			$numFiles = $zip->numFiles;
			for ($i; $i < $numFiles; $i++) {
				$file = $zip->statIndex($i);
				if ($file['name'] == $filePath) {
					break;
				}
				$file = [];
			}
		}

		$sourefile = '';
		if ($file) {
			$fp = $zip->getStream($file['name']);
			if (!$fp) {
				throw new Exception\IOException(sprintf(
					'Not able to read zip inner file %s', iconv($fileEncoding, "UTF-8", $file['name'])
				));
			}
			while (!feof($fp)) {
				$sourefile .= fread($fp, 2);
			}
			fclose($fp);
		}
		$zip->close();

		return $sourefile;
	}
}
