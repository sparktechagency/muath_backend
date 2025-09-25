<?php

use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\StaticPageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// social login (google)
Route::post('/social-login', [AuthController::class, 'socialLogin']);

// static page show
Route::get('pages/{slug}', [StaticPageController::class, 'show']);


Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/get-profile', [AuthController::class, 'getProfile']);
    Route::post('/edit-profile', [SettingsController::class, 'editProfile']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);

    // static page update
    Route::post('pages/{slug}', [StaticPageController::class, 'update']);

    // notification
    Route::get('/get-notifications', [NotificationController::class, 'getNotifications']);
    Route::patch('/read', [NotificationController::class, 'read']);
    Route::patch('/read-all', [NotificationController::class, 'readAll']);
    Route::get('/notification-status', [NotificationController::class, 'status']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::post('/add-product',[ProductController::class,'addProduct']);
        Route::get('/get-products',[ProductController::class,'getProducts']);
        Route::patch('/edit-product/{id?}',[ProductController::class,'editProduct']);
        Route::get('/view-product/{id?}',[ProductController::class,'viewProduct']);
        Route::delete('/delete-product/{id?}',[ProductController::class,'deleteProduct']);
    });

    Route::middleware('user')->prefix('user')->group(function () {
      //
    });
});
