<?php

use Illuminate\Http\Request;
use App\Http\Controllers\System\Api\UserController;
use App\Http\Controllers\System\Api\ProfileController;
use App\Http\Controllers\System\Api\ForgotPasswordController;
use App\Http\Controllers\System\Api\ResetPasswordController;
use App\Http\Controllers\System\Api\OrganizationController;
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

Route::middleware('auth:sanctum')->group(function() {
    Route::post('logout', [ProfileController::class, 'logout']);

    /** Users Profile Routes */
    Route::post('get-profile-details', [ProfileController::class, 'getUserDetails']);
    Route::post('update-profile-details', [ProfileController::class, 'updateUserDetails']);
    Route::post('update-profile', [ProfileController::class, 'updateProfile']);
    Route::post('change-password', [ProfileController::class, 'changePassword']);
    
    /** Register Organization Routes */
    Route::post('organization/get/lists', [OrganizationController::class, 'getOrganizations']);
    Route::post('organization/get/{id}', [OrganizationController::class, 'getOrganizationDetails']);
    Route::post('organization/add', [OrganizationController::class, 'addOrganization']);
    Route::post('organization/update', [OrganizationController::class, 'updateOrganization']);
    Route::post('organization/status/change', [OrganizationController::class, 'changeOrganizationStatus']);
});