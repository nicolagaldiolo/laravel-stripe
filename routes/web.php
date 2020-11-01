<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\CheckoutBasePrebuildController;
use \App\Http\Controllers\CheckoutBaseCustomController;
use \App\Http\Controllers\PurchaseController;
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

Route::get('/', function () {
    return view('welcome');
});


/* Start Prebuilt checkout base */
Route::get('/checkout-prebuilt-base', function () {
    return view('stripecheckoutbase.prebuilt.checkout');
});

Route::get('/cancel', function () {
    return view('stripecheckoutbase.prebuilt.cancel');
});

Route::get('/success', function () {
    return view('stripecheckoutbase.prebuilt.success');
});

Route::post('/create-session', [CheckoutBasePrebuildController::class, 'createSession']);
/* End Prebuilt checkout base */

/* Start Custom checkout base */
Route::get('/checkout-custom-base', function () {
    return view('stripecheckoutbase.custom.checkout');
});

Route::post('/payment-intent-base', [CheckoutBaseCustomController::class, 'paymentIntent']);


//Route::post('/payment-intent', [PurchaseController::class, 'paymentIntent']);
/* End Custom checkout base */



Route::post('/stripe/webhook', [App\Http\Controllers\WebhooksController::class, 'handle']);

Route::middleware(['auth'])->group(function () {

    Route::get('/checkout', function () {
        return view('checkout');
    });

    Route::post('/create-subscription', [App\Http\Controllers\SubscriptionsController::class, 'createSubscription']);
    Route::post('/create-customer', [App\Http\Controllers\SubscriptionsController::class, 'createCustomer']);
    Route::post('/retry-invoice', [App\Http\Controllers\SubscriptionsController::class, 'retryInvoice']);
});

Auth::routes();
