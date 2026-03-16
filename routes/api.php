<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{AuthController, CategoryController, ProductController, CartController};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;


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
Route::get('/products/{id}/images/{filename}', function ($id, $filename) {
    $path = storage_path("app/public/products/$id/$filename");

    if (!file_exists($path)) {
        abort(404);
    }

    return Response::file($path, [
        'Access-Control-Allow-Origin' => 'http://localhost:5173', // allow your dev frontend
        'Access-Control-Allow-Methods' => 'GET',
    ]);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        $user = $request->user();
    
        $user->profile_image = $user->profile_image 
            ? asset(Storage::url($user->profile_image)) 
            : asset('images/default-avatar.png');
        return response()->json($user);
    });
    Route::put('/profile/update', [AuthController::class, 'updateProfile']);
    Route::post('/update-image', [AuthController::class, 'updateProfileImage']);
    Route::post('/change-password', [AuthController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::prefix('categories')->group(function () {
        Route::get('/categories-with-child',[CategoryController::class, 'categoryWithChild']);
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/add', [CategoryController::class, 'store']);
        Route::get('/edit/{id}', [CategoryController::class, 'edit']);
        Route::put('/update/{id}', [CategoryController::class, 'update']);
        Route::delete('/delete/{id}', [CategoryController::class, 'destroy']);
    });
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/add', [ProductController::class, 'store']);
        Route::get('/edit/{id}', [ProductController::class, 'edit']);
        Route::put('/update/{id}', [ProductController::class, 'update']);
        Route::delete('/delete/{id}', [ProductController::class, 'destroy']);
        Route::post('/upload-images', [ProductController::class, 'uploadProductImage']);
        Route::get('/{id}/images', [ProductController::class, 'getImages']);
        Route::delete('/delete-image/{id}', [ProductController::class, 'deleteImage']);
        Route::get('/brands', [ProductController::class, 'getBrand']);

    });
    Route::post('/cart-items', [CartController::class, 'cartStore']);
    Route::get('/cart-count', [CartController::class, 'cartCount']);
    Route::delete('/cart-items/delete/{id}', [CartController::class, 'cartItemDelete']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
