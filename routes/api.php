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

//User
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('users', [AdminController::class, 'users']);
Route::post('user/{id}', [AdminController::class, 'updateUser']);
Route::post('profile/{id}', [UserController::class, 'updateProfile']);
Route::post('profile/change-password/{id}', [UserController::class, 'changePassword']);

//Product
Route::get('latest-product', [UserController::class, 'productLatest']);
Route::get('top-product', [UserController::class, 'productTop']);
Route::get('sale-product', [UserController::class, 'productSale']);
Route::get('products', [UserController::class, 'allProduct']);
Route::post('products', [AdminController::class, 'storeProduct']);
Route::post('product/delete', [AdminController::class, 'deleteProduct']);
Route::get('product/{slug}', [UserController::class, 'detailProduct']);
Route::post('product/{id}', [AdminController::class, 'updateProduct']);
Route::get('related-product/{name}', [UserController::class, 'productRelated']);
Route::get('search/{keyword}', [UserController::class, 'search']);

//Category
Route::get('categories', [AdminController::class, 'categories']);
Route::post('categories', [AdminController::class, 'storeCategory']);
Route::post('category/delete', [AdminController::class, 'deleteCategory']);
Route::post('category/{id}', [AdminController::class, 'updateCategory']);
Route::get('category/{id}', [UserController::class, 'categoryProduct']);

//Sub Category
Route::get('sub-categories', [AdminController::class, 'subCategories']);
Route::post('sub-categories', [AdminController::class, 'storeSubCategory']);
Route::post('sub-category/delete', [AdminController::class, 'deleteSubCategory']);
Route::get('sub-category/{id}', [UserController::class, 'subCategoryProduct']);
Route::post('sub-category/{id}', [AdminController::class, 'updateSubCategory']);

//Color
Route::get('colors', [AdminController::class, 'colors']);

//Cart
Route::post('cart/{user_id}', [UserController::class, 'addToCart']);
Route::get('cart/{user_id}', [UserController::class, 'cart']);
Route::post('cart-item/delete', [UserController::class, 'deleteCart']);
Route::post('cart-item/edit', [UserController::class, 'editCart']);

//Login
Route::post('login', [UserController::class, 'login']);
Route::middleware('auth:sanctum')->get('logout', [UserController::class, 'logout']);

//OrderBuy
Route::post('order', [UserController::class, 'storeOrder']);
Route::get('orders/{user_id}', [UserController::class, 'ordersUser']);
Route::get('orders', [AdminController::class, 'orders']);
Route::post('order/{id}', [AdminController::class, 'updateOrder']);
Route::get('order-method', [UserController::class, 'orderMethods']);

//Status Order
Route::get('states', [AdminController::class, 'states']);

//Producer
Route::get('producers', [AdminController::class, 'producers']);
Route::post('producers', [AdminController::class, 'storeProducer']);
Route::post('producer/delete', [AdminController::class, 'deleteProducer']);
Route::post('producer/{id}', [AdminController::class, 'updateProducer']);
