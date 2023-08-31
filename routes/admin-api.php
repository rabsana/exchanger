<?php

use Illuminate\Support\Facades\Route;

Route::prefix("admin-api")->name('admin-api.')->group(function () {

    Route::prefix('v1')->name('v1.')->group(function () {

        // public routes
        Route::middleware(config('rabsana-exchanger.adminApiMiddlewares.public', []))->group(function () {
        });

        Route::middleware(config('rabsana-exchanger.adminApiMiddlewares.private', []))->group(function () {
        });


        // 

    });
});
