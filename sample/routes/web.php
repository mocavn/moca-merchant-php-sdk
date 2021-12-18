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

Route::get('/merchants/sample', function () {
    return view('welcome');
})->name('home');
Route::get('/', function () {
   return view('welcome');
})->name('sample.home');

Route::get('/merchants/sample/card', function(){
    return view('card');
})->name('card');

Route::get('/card', function(){
   return view('card');
})->name('sample.card');

Route::get('/webUrl', 'DemoControllers@__invoke')->name('sample.pay');
Route::get('/merchants/sample/webUrl', 'DemoControllers@__invoke')->name('pay');

Route::get('/result', 'DemoControllers@GetResponse')->name('sample.result');
Route::get('/merchants/sample/result', 'DemoControllers@GetResponse')->name('result');
