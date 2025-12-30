<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Faculty Routes
|--------------------------------------------------------------------------
|
| Here is where you can register faculty-specific routes for your application.
|
*/

Route::middleware(['auth', 'role:faculty'])->prefix('faculty')->name('faculty.')->group(function () {
    // Faculty routes will be defined here
});
