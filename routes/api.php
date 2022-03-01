<?php

use Illuminate\Http\Request;
use App\Http\Controllers\System\Api\UserController;
use App\Http\Controllers\System\Api\ProfileController;
use App\Http\Controllers\System\Api\ForgotPasswordController;
use App\Http\Controllers\System\Api\ResetPasswordController;
use App\Http\Controllers\System\Api\OrganizationController;
use App\Http\Controllers\System\Api\OrganizationUserController;
use App\Http\Controllers\System\Api\RoleController;
use App\Http\Controllers\Tenant\Api\UnitTypesController;
use App\Http\Controllers\Tenant\Api\MaterialTypesController;
use App\Http\Controllers\Tenant\Api\ManforceTypesController;
use App\Http\Controllers\Tenant\Api\ActivityCategoriesController;
use App\Http\Controllers\Tenant\Api\SubActivityCategoriesController;
use App\Http\Controllers\Tenant\Api\MachineriesController;
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

    /** Organizations Management Routes */
    Route::post('role/get/lists', [RoleController::class, 'getRoles']);
    Route::post('role/get/{id}', [RoleController::class, 'getRoleDetails']);
    Route::post('role/add', [RoleController::class, 'addRole']);
    Route::post('role/update', [RoleController::class, 'updateRole']);
    Route::post('role/status/change', [RoleController::class, 'changeRoleStatus']);
    Route::post('role/module/permissions/get', [RoleController::class, 'getRoleModulePermissions']);
    Route::post('role/module/permissions/change', [RoleController::class, 'changeRoleModulePermissions']);

    /** User Management Routes */
    Route::post('user/get/lists', [OrganizationUserController::class, 'getUsers']);
    Route::post('user/get/{id}', [OrganizationUserController::class, 'getUserDetails']);
    Route::post('user/add', [OrganizationUserController::class, 'addUser']);
    Route::post('user/update', [OrganizationUserController::class, 'updateUser']);
    Route::post('user/status/change', [OrganizationUserController::class, 'changeUserStatus']);

    /* Unit Types Route */
    Route::post('unit-type/get/lists', [UnitTypesController::class, 'getUnitTypes']);
    Route::post('unit-type/get/{id}', [UnitTypesController::class, 'getDetails']);
    Route::post('unit-type/add', [UnitTypesController::class, 'addUnitType']);
    Route::post('unit-type/update', [UnitTypesController::class, 'updateUnitType']);
    Route::post('unit-type/status/change', [UnitTypesController::class, 'changeStatus']);

    /* Material Type Route */
    Route::post('material-type/get/lists', [MaterialTypesController::class, 'getMaterialTypes']);
    Route::post('material-type/get/{id}', [MaterialTypesController::class, 'getDetails']);
    Route::post('material-type/add', [MaterialTypesController::class, 'addMaterialType']);
    Route::post('material-type/update', [MaterialTypesController::class, 'updateMaterialType']);
    Route::post('material-type/status/change', [MaterialTypesController::class, 'changeStatus']);

    /* Manforce Type Route */
    Route::post('manforce-type/get/lists', [ManforceTypesController::class, 'getManforceTypes']);
    Route::post('manforce-type/get/{id}', [ManforceTypesController::class, 'getDetails']);
    Route::post('manforce-type/add', [ManforceTypesController::class, 'addManforceType']);
    Route::post('manforce-type/update', [ManforceTypesController::class, 'updateManforceType']);
    Route::post('manforce-type/status/change', [ManforceTypesController::class, 'changeStatus']);

    /* Activity Categories Type Route */
    Route::post('activity-category/get/lists', [ActivityCategoriesController::class, 'getActivityCategory']);
    Route::post('activity-category/get/{id}', [ActivityCategoriesController::class, 'getDetails']);
    Route::post('activity-category/add', [ActivityCategoriesController::class, 'addActivityCategory']);
    Route::post('activity-category/update', [ActivityCategoriesController::class, 'updateActivityCategory']);
    Route::post('activity-category/status/change', [ActivityCategoriesController::class, 'changeStatus']);

    /* Sub Activity Categories Type Route */
    Route::post('sub-activity-category/get/lists', [SubActivityCategoriesController::class, 'getSubActivityCategory']);
    Route::post('sub-activity-category/get/{id}', [SubActivityCategoriesController::class, 'getDetails']);
    Route::post('sub-activity-category/add', [SubActivityCategoriesController::class, 'addSubActivityCategory']);
    Route::post('sub-activity-category/update', [SubActivityCategoriesController::class, 'updateSubActivityCategory']);
    Route::post('sub-activity-category/status/change', [SubActivityCategoriesController::class, 'changeStatus']);

    /* Machineries Route */
    Route::post('machinery/get/lists', [MachineriesController::class, 'getMachineries']);
    Route::post('machinery/get/{id}', [MachineriesController::class, 'getDetails']);
    Route::post('machinery/add', [MachineriesController::class, 'addMachineryCategory']);
    Route::post('machinery/update', [MachineriesController::class, 'updateMachineryCategory']);
    Route::post('machinery/status/change', [MachineriesController::class, 'changeStatus']);
});
