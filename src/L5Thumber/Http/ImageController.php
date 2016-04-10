<?php namespace Imvkmark\L5Thumber\Http;

use App\Http\Controllers\Controller;

class ImageController extends Controller {

	public function getIndex() {
		$thumber = app('lemon.l5-thumber.thumber');
		$thumber->show();
	}

	public function getDomain() {
		$thumber = app('lemon.l5-thumber.thumber');
		$thumber->show();
	}
}

