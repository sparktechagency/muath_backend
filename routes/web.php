<?php

use App\Http\Controllers\Api\CloverController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});





// Route for Clover webhook (Clover server → your server)
// Route::post('/webhook/clover', [CloverController::class, 'handleWebhook']);

// Route for user redirect after successful payment
// Route::get('/payment/success', [CloverController::class, 'paymentSuccess']);

// Route for user redirect after failed payment
// Route::get('/payment/fail', [CloverController::class, 'paymentFail']);


// Route::get('/payment/success', function () {
//     // Here you can handle the payment success logic or view
//     return 'payment success page.';  // This will render a success page
// })->name('payment.success');