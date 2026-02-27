<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{AuthController,CategoryController};

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
        return $request->user();
    });
    Route::put('/profile/update', [AuthController::class, 'updateProfile']);
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
});

Route::post('/login', [AuthController::class, 'login']);

