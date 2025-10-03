<?php

use App\Http\Controllers\Api\Admin\BannerController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CloverController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\StaticPageController;
use App\Http\Controllers\Api\User\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// Route::get('pages/{slug}', [StaticPageController::class, 'show']);

Route::get('/get-banner', [BannerController::class, 'getBanner']);
Route::get('/get-categories', [CategoryController::class, 'getCategories']);
Route::get('/get-products', [UserController::class, 'getProducts']);
Route::get('/view-product/{id?}', [UserController::class, 'viewProduct']);

Route::post('/create-checkout', [CloverController::class, 'createCheckout']);
Route::get('/paid-status', [CloverController::class, 'paymentSuccess']);


Route::middleware('auth:api')->group(function () {
  Route::post('/logout', [AuthController::class, 'logout']);
  Route::post('/change-password', [AuthController::class, 'changePassword']);
  Route::get('/get-profile', [AuthController::class, 'getProfile']);
  Route::post('/edit-profile', [SettingsController::class, 'editProfile']);
  Route::post('/update-password', [AuthController::class, 'updatePassword']);


  Route::middleware('admin')->prefix('admin')->group(function () {
    // dashboard
    Route::get('/basic-info', [DashboardController::class, 'basicInfo']);
    // banner 
    Route::post('/banner-update', [BannerController::class, 'bannerUpdate']);
    // product
    Route::post('/add-product', [ProductController::class, 'addProduct']);
    Route::get('/get-products', [ProductController::class, 'getProducts']);
    Route::patch('/edit-product/{id?}', [ProductController::class, 'editProduct']);
    Route::get('/view-product/{id?}', [ProductController::class, 'viewProduct']);
    Route::delete('/delete-product/{id?}', [ProductController::class, 'deleteProduct']);
    // category
    Route::post('/add-category', [CategoryController::class, 'addCategory']);
    Route::get('/view-category/{id?}', [CategoryController::class, 'viewCategory']);
    Route::put('/edit-category/{id?}', [CategoryController::class, 'editCategory']);
    Route::delete('/delete-category/{id?}', [CategoryController::class, 'deleteCategory']);
    // setting
    Route::get('/get-feedbacks', [SettingsController::class, 'getFeedbacks']);
    Route::get('/view-feedback/{id}', [SettingsController::class, 'viewFeedback']);
    Route::delete('/delete-feedback/{id}', [SettingsController::class, 'deleteFeedback']);
    // Route::post('pages/{slug}', [StaticPageController::class, 'update']);
  });

  Route::middleware('user')->prefix('user')->group(function () {
    // Route::post('/add-to-cart', [UserController::class, 'addToCart']);
    // Route::get('/my-cart', [UserController::class, 'myCart']);
    // Route::delete('/clear-my-cart', [UserController::class, 'clearMyCart']);
    // Route::delete('/remove-cart-product/{id?}', [UserController::class, 'removeCartProduct']);
    Route::post('/send-feedback', [UserController::class, 'sendFeedback']);
    // Route::patch('/count-up', [UserController::class, 'countUp']);
    // Route::patch('/count-down', [UserController::class, 'countDown']);
  });
});
