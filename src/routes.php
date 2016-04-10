<?php
\Route::controller('lm_thumber', '\Imvkmark\L5Thumber\Http\ImageController', [
	'getIndex' => 'lm_thumber.index'
]);
Route::group(['domain' => 'thumber.larframe.com'], function () {
	Route::get('/', ['as' => 'profile', 'uses' => '\Imvkmark\L5Thumber\Http\ImageController@getDomain']);
});