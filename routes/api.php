<?php

use Illuminate\Http\Request;
use App\Http\Controllers\System\Api\UserController;
use App\Http\Controllers\System\Api\ProfileController;
use App\Http\Controllers\System\Api\ForgotPasswordController;
use App\Http\Controllers\System\Api\ResetPasswordController;
use App\Http\Controllers\System\Api\OrganizationController;
use App\Http\Controllers\System\Api\OrganizationUserController;
use Illuminate\Support\Facades\Route;

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

/** Login, Register, Forgot Password Routes */
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);
Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('reset-password', [ResetPasswordController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [ProfileController::class, 'logout']);

    /** Loggedin Users Profile Routes */
    Route::post('profile/details/get', [ProfileController::class, 'getProfileDetails']);
    Route::post('profile/details/update', [ProfileController::class, 'updateProfileDetails']);
    Route::post('change-password', [ProfileController::class, 'changePassword']);

    /** Organizations Management Routes */
    Route::post('organization/get/lists', [OrganizationController::class, 'getOrganizations']);
    Route::post('organization/get/{id}', [OrganizationController::class, 'getOrganizationDetails']);
    Route::post('organization/add', [OrganizationController::class, 'addOrganization']);
    Route::post('organization/update', [OrganizationController::class, 'updateOrganization']);
    Route::post('organization/status/change', [OrganizationController::class, 'changeOrganizationStatus']);

    /** User Management Routes */
    Route::post('user/get/lists', [OrganizationUserController::class, 'getUsers']);
    Route::post('user/get/{id}', [OrganizationUserController::class, 'getUserDetails']);
    Route::post('user/add', [OrganizationUserController::class, 'addUser']);
    Route::post('user/update', [OrganizationUserController::class, 'updateUser']);
    Route::post('user/status/change', [OrganizationUserController::class, 'changeUserStatus']);
});
