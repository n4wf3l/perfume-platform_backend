<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;


Route::post('/login', [AuthController::class, 'login']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);


Route::post('/orders', [OrderController::class, 'store']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/products', [ProductController::class, 'store']);//admin
    Route::put('/products/{id}', [ProductController::class, 'update']);//admin
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);//admin

    Route::post('/categories', [CategoryController::class, 'store']);//admin
    Route::put('/categories/{id}', [CategoryController::class, 'update']);//admin
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);//admin

    Route::get('/orders', [OrderController::class, 'index']); // Optional admin route
    Route::get('/orders/{id}', [OrderController::class, 'show']); // Optional
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);
    });