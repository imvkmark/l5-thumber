<?php namespace Imvkmark\L5Thumber\Eva;

use Requests;

class Faker {

	//protected $httpRequest;

	protected $file;

	protected $sourceSite;

	protected $cacher;

	public function __construct($sourceSite = null) {
		if (false === class_exists('Requests')) {
			throw new Exception\BadFunctionCallException(sprintf(
				'Library Requests not installed'
			));
		}

		if ($sourceSite) {
			$this->setSourceSite($sourceSite);
		}
	}

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

	/*
	public function getHttpRequest()
	{
		if($this->httpRequest){
			return $this->httpRequest;
		}

		return $this->httpRequest = new Requests();
	}

	public function setHttpRequest(Requests $httpRequest)
	{
		$this->httpRequest = $httpRequest;
		return $this;
	}
	*/

	public function getSourceSite() {
		return $this->sourceSite;
	}

	public function setSourceSite($sourceSite) {
		$sourceSite = strtolower($sourceSite);
		if (false === in_array($sourceSite, ['flickr', 'picasa', 'so'])) {
			$sourceSite = 'picasa';
		}
		$this->sourceSite = $sourceSite;
		return $this;
	}

	public function getRss() {
		$sourceSite = $this->getSourceSite();
		switch ($sourceSite) {
			case 'flickr':
				$rss = 'http://www.flickr.com/explore?data=1';
				break;
			case 'so':
				$rss = 'http://image.so.com/j?q=风景&src=srp&sn=60&pn=50';
				break;
			case 'picasa':
			default:
				$rss = 'https://picasaweb.google.com/data/feed/api/featured?alt=json';
		}
		return $rss;
	}

	protected function process() {
		$sourceSite = $this->getSourceSite();
		$json       = $this->getRss();
		$request    = Requests::get($json);
		$data       = json_decode($request->body);

		switch ($sourceSite) {
			case 'flickr':
				$entry = $data->photos;
				$count = count($entry);
				$url   = $entry[rand(0, $count - 1)]->sizes->c->url; //use medium size
				break;
			case 'picasa':
				$entry = $data->feed->entry;
				$count = count($entry);
				$url   = $entry[rand(0, $count - 1)]->content->src;
				break;
			case 'so':
			default:
				$entry = $data->list;
				$count = count($entry);
				$url   = $entry[rand(0, $count - 1)]->thumb;
		}
		return $url;
	}

	public function getFile() {
		return $this->process();
	}
}
