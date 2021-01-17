<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {return view('welcome');})->name('home');

Route::post('/upload', 'Controller@upload')->name('upload');
Route::get('/view-result', 'Controller@printResult');
Route::get('/done', function (){return view('done');})->name('done');
Route::post('/reset','Controller@reset')->name('reset');
