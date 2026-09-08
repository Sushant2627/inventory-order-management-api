<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;

Route::apiResource('products', ProductController::class);

Route::post(
    'products/{product}/stock',
    [ProductController::class, 'updateStock']
);

Route::apiResource('customers', CustomerController::class);

Route::apiResource('orders', OrderController::class)
    ->only(['index', 'store', 'show']);

Route::post(
    'orders/{order}/cancel',
    [OrderController::class, 'cancel']
);