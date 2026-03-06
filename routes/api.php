<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{AuthController,CategoryController,ProductController};
use Illuminate\Support\Facades\Storage;


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
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::prefix('categories')->group(function () {
        Route::get('/categories-with-child',[CategoryController::class, 'categoryWithChild']);
        Route::get('/',[CategoryController::class, 'index']);
        Route::post('/add',[CategoryController::class, 'store']);
        Route::get('/edit/{id}',[CategoryController::class, 'edit']);
        Route::put('/update/{id}',[CategoryController::class, 'update']);
        Route::delete('/delete/{id}',[CategoryController::class, 'destroy']);
    });
    Route::prefix('products')->group(function () {
        Route::get('/',[ProductController::class, 'index']);
        Route::post('/add',[ProductController::class, 'store']);
        Route::get('/edit/{id}',[ProductController::class, 'edit']);
        Route::put('/update/{id}',[ProductController::class, 'update']);
        Route::delete('/delete/{id}',[ProductController::class, 'destroy']);
    });
});

Route::post('/login', [AuthController::class, 'login']);

