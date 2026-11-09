<?php

use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

// Uploaded thumbnails (storage/app/public/{products,listings}/...)
Route::get('media/{path}', MediaController::class)
    ->where('path', '(products|listings)/[A-Za-z0-9._-]+')
    ->name('media');

// Vue single-page app: every other non-API URL is handled by vue-router.
Route::view('/{any?}', 'app')
    ->where('any', '^(?!api(/|$)|media/|up$).*$')
    ->name('spa');
