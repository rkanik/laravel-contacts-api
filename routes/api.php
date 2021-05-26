<?php

// Controllers
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\UsersController;
// Route
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {

    Route::get('unauthorized', function () {

        return response()->json([
            'message' => 'Unauthorized access.',
        ], Response::HTTP_UNAUTHORIZED);

    })->name('api.unauthorized');

    // Auth
    Route::group(['prefix' => '/auth'], function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Passoword
    Route::group(['prefix' => 'password'], function () {
        Route::post('forgot', [PasswordController::class, 'forgot']);
        Route::get('check', [PasswordController::class, 'check']);
        Route::post('reset', [PasswordController::class, 'reset']);
    });

    Route::middleware('auth:api')->group(function () {

        // Me
        Route::group(['prefix' => '/me'], function () {
            Route::get('/', [UsersController::class, 'select']);

            // Contacts
            Route::group(['prefix' => '/contacts'], function () {
                Route::get('/', [ContactsController::class, 'index']);
                Route::post('/', [ContactsController::class, 'insert']);
                Route::post('/import', [ContactsController::class, 'import']);

                Route::get('/{contact_id}', [ContactsController::class, 'select']);
                Route::put('/{contact_id}', [ContactsController::class, 'update']);
                Route::delete('/{contact_id}', [ContactsController::class, 'destroy']);
            });
        });

        // Users
        Route::group(['prefix' => 'users'], function () {

            Route::get('/', [UsersController::class, 'index']);
            Route::post('/', [UsersController::class, 'insert']);

            Route::get('/{id}', [UsersController::class, 'select']);
            Route::put('/{id}', [UsersController::class, 'replace']);
            Route::patch('/{id}', [UsersController::class, 'update']);
            Route::delete('/{id}', [UsersController::class, 'destroy']);

            // Contacts
            Route::group(['prefix' => '/{id}/contacts'], function () {
                Route::get('/', [ContactsController::class, 'index']);
                Route::post('/', [ContactsController::class, 'insert']);

                Route::get('/{contact_id}', [ContactsController::class, 'select']);
                Route::put('/{contact_id}', [ContactsController::class, 'update']);
                Route::delete('/{contact_id}', [ContactsController::class, 'destroy']);
            });
        });

    });
});
