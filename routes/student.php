<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
|
| Here is where you can register student-specific routes for your application.
|
*/

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    // Student routes will be defined here
});
