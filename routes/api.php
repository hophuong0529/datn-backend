<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('latest-product', [HomeController::class, 'productLatest']);

Route::get('top-product', [HomeController::class, 'productTop']);

Route::get('sale-product', [HomeController::class, 'productSale']);

Route::get('products', [HomeController::class, 'allProduct']);
Route::post('products', [AdminController::class, 'storeProduct']);

Route::get('search/{keyword}', [HomeController::class, 'search']);

Route::get('related-product/{name}', [HomeController::class, 'productRelated']);

Route::get('categories', [HomeController::class, 'categories']);
Route::get('colors', [AdminController::class, 'colors']);


Route::post('product/delete', [AdminController::class, 'deleteProduct']);

Route::get('product/{slug}', [HomeController::class, 'detailProduct']);
Route::post('product/{slug}', [AdminController::class, 'updateProduct']);

Route::get('category/{name}', [HomeController::class, 'categoryProduct']);

Route::post('login', [HomeController::class, 'login']);

Route::post('order', [HomeController::class, 'order']);
