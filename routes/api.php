<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
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
Route::get('latest-product', [UserController::class, 'productLatest']);
Route::get('top-product', [UserController::class, 'productTop']);
Route::get('sale-product', [UserController::class, 'productSale']);
Route::get('products', [UserController::class, 'allProduct']);
Route::post('products', [AdminController::class, 'storeProduct']);
Route::post('product/delete', [AdminController::class, 'deleteProduct']);
Route::get('product/{slug}', [UserController::class, 'detailProduct']);
Route::post('product/{slug}', [AdminController::class, 'updateProduct']);
Route::get('related-product/{name}', [UserController::class, 'productRelated']);
Route::get('search/{keyword}', [UserController::class, 'search']);

//Category
Route::get('categories', [UserController::class, 'categories']);
Route::get('category/{slug}', [UserController::class, 'categoryProduct']);
Route::get('sub-category/{slug}', [UserController::class, 'subCategoryProduct']);

//Color
Route::get('colors', [AdminController::class, 'colors']);

//Cart
Route::post('cart/{user_id}', [UserController::class, 'addToCart']);
Route::get('cart/{user_id}', [UserController::class, 'cart']);
Route::post('cart-item/delete', [UserController::class, 'deleteCart']);
Route::post('cart-item/edit', [UserController::class, 'editCart']);

//Login
Route::post('login', [UserController::class, 'login']);

//OrderBuy
Route::post('order', [UserController::class, 'order']);
