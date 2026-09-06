<?php

use App\Support\KeJingCatalog;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/pan/create', 'pan.create')->name('pan.create');

Route::get('/kejing', function () {
    return view('kejing.index', ['lessons' => KeJingCatalog::lessons()]);
})->name('kejing');
