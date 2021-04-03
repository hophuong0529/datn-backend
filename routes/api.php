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

//Product
Route::get('latest-product', [HomeController::class, 'productLatest']);
Route::get('top-product', [HomeController::class, 'productTop']);
Route::get('sale-product', [HomeController::class, 'productSale']);
Route::get('products', [HomeController::class, 'allProduct']);
Route::post('products', [AdminController::class, 'storeProduct']);
Route::post('product/delete', [AdminController::class, 'deleteProduct']);
Route::get('product/{slug}', [HomeController::class, 'detailProduct']);
Route::post('product/{slug}', [AdminController::class, 'updateProduct']);
Route::get('related-product/{name}', [HomeController::class, 'productRelated']);
Route::get('search/{keyword}', [HomeController::class, 'search']);

//Category
Route::get('categories', [HomeController::class, 'categories']);
Route::get('category/{slug}', [HomeController::class, 'categoryProduct']);
Route::get('sub-category/{slug}', [HomeController::class, 'subCategoryProduct']);

//Color
Route::get('colors', [AdminController::class, 'colors']);

//Cart
Route::post('cart/{user_id}', [HomeController::class, 'addToCart']);
Route::get('cart/{user_id}', [HomeController::class, 'cart']);
Route::post('cart-item/delete', [HomeController::class, 'deleteCart']);
Route::post('cart-item/edit', [HomeController::class, 'editCart']);

//Login
Route::post('login', [HomeController::class, 'login']);

//Order
Route::post('order', [HomeController::class, 'order']);
