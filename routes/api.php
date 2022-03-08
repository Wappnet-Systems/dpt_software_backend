<?php

use Illuminate\Http\Request;
use App\Http\Controllers\System\Api\UserController;
use App\Http\Controllers\System\Api\ProfileController;
use App\Http\Controllers\System\Api\ForgotPasswordController;
use App\Http\Controllers\System\Api\ResetPasswordController;
use App\Http\Controllers\System\Api\OrganizationController;
use App\Http\Controllers\System\Api\OrganizationUserController;
use App\Http\Controllers\System\Api\RoleController;
use App\Http\Controllers\Tenant\Api\RoleController as OrganizationRoleController;
use App\Http\Controllers\Tenant\Api\UnitTypesController;
use App\Http\Controllers\Tenant\Api\MaterialTypesController;
use App\Http\Controllers\Tenant\Api\ManforceTypesController;
use App\Http\Controllers\Tenant\Api\ActivityCategoriesController;
use App\Http\Controllers\Tenant\Api\SubActivityCategoriesController;
use App\Http\Controllers\Tenant\Api\MachineriesController;
use App\Http\Controllers\Tenant\Api\ProjectsController;
use App\Http\Controllers\Tenant\Api\Project\NonWorkingDaysController;
use App\Http\Controllers\Tenant\Api\Project\ManforcesController;
use App\Http\Controllers\Tenant\Api\Project\GangsController;
use App\Http\Controllers\Tenant\Api\Project\GangsManforcesController;
use App\Http\Controllers\Tenant\Api\Project\MaterialController;
use App\Http\Controllers\Tenant\Api\Project\InventoryStocksController;
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

    /* Assign Modules Permission to Roles Routes */
    Route::post('role/module/permissions/get', [RoleController::class, 'getRoleModulePermissions']);
    Route::post('role/module/permissions/change', [RoleController::class, 'changeRoleModulePermissions']);

    /* Assign Sub Modules Permission to Roles Routes */
    Route::post('role/sub-module/permissions/get', [OrganizationRoleController::class, 'getRoleSubModulePermissions']);
    Route::post('role/sub-module/permissions/change', [OrganizationRoleController::class, 'changeRoleSubModulePermissions']);

    /** User Management Routes */
    Route::post('user/get/lists', [OrganizationUserController::class, 'getUsers']);
    Route::post('user/get/{id}', [OrganizationUserController::class, 'getUserDetails']);
    Route::post('user/add', [OrganizationUserController::class, 'addUser']);
    Route::post('user/update', [OrganizationUserController::class, 'updateUser']);
    Route::post('user/status/change', [OrganizationUserController::class, 'changeUserStatus']);

    /* Unit Types Routes */
    Route::post('unit-type/get/lists', [UnitTypesController::class, 'getUnitTypes']);
    Route::post('unit-type/get/{id}', [UnitTypesController::class, 'getDetails']);
    Route::post('unit-type/add', [UnitTypesController::class, 'addUnitType']);
    Route::post('unit-type/update', [UnitTypesController::class, 'updateUnitType']);
    Route::post('unit-type/status/change', [UnitTypesController::class, 'changeStatus']);

    /* Material Type Routes */
    Route::post('material-type/get/lists', [MaterialTypesController::class, 'getMaterialTypes']);
    Route::post('material-type/get/{id}', [MaterialTypesController::class, 'getDetails']);
    Route::post('material-type/add', [MaterialTypesController::class, 'addMaterialType']);
    Route::post('material-type/update', [MaterialTypesController::class, 'updateMaterialType']);
    Route::post('material-type/status/change', [MaterialTypesController::class, 'changeStatus']);

    /* Manforce Type Routes */
    Route::post('manforce-type/get/lists', [ManforceTypesController::class, 'getManforceTypes']);
    Route::post('manforce-type/get/{id}', [ManforceTypesController::class, 'getDetails']);
    Route::post('manforce-type/add', [ManforceTypesController::class, 'addManforceType']);
    Route::post('manforce-type/update', [ManforceTypesController::class, 'updateManforceType']);
    Route::post('manforce-type/status/change', [ManforceTypesController::class, 'changeStatus']);

    /* Activity Categories Type Routes */
    Route::post('activity-category/get/lists', [ActivityCategoriesController::class, 'getActivityCategory']);
    Route::post('activity-category/get/{id}', [ActivityCategoriesController::class, 'getDetails']);
    Route::post('activity-category/add', [ActivityCategoriesController::class, 'addActivityCategory']);
    Route::post('activity-category/update', [ActivityCategoriesController::class, 'updateActivityCategory']);
    Route::post('activity-category/status/change', [ActivityCategoriesController::class, 'changeStatus']);

    /* Sub Activity Categories Type Routes */
    Route::post('sub-activity-category/get/lists', [SubActivityCategoriesController::class, 'getSubActivityCategory']);
    Route::post('sub-activity-category/get/{id}', [SubActivityCategoriesController::class, 'getDetails']);
    Route::post('sub-activity-category/add', [SubActivityCategoriesController::class, 'addSubActivityCategory']);
    Route::post('sub-activity-category/update', [SubActivityCategoriesController::class, 'updateSubActivityCategory']);
    Route::post('sub-activity-category/status/change', [SubActivityCategoriesController::class, 'changeStatus']);

    /* Machineries Routes */
    Route::post('machinery/get/lists', [MachineriesController::class, 'getMachineries']);
    Route::post('machinery/get/{id}', [MachineriesController::class, 'getDetails']);
    Route::post('machinery/add', [MachineriesController::class, 'addMachineryCategory']);
    Route::post('machinery/update', [MachineriesController::class, 'updateMachineryCategory']);
    Route::post('machinery/status/change', [MachineriesController::class, 'changeStatus']);

    /* Projects Routes */
    Route::post('project/get/lists', [ProjectsController::class, 'getProjects']);
    Route::post('project/get/{id}', [ProjectsController::class, 'getProjectDetails']);
    Route::post('project/add', [ProjectsController::class, 'addProject']);
    Route::post('project/update', [ProjectsController::class, 'updateProject']);
    Route::delete('project/delete/{uuid}', [ProjectsController::class, 'deleteProject']);
    Route::post('project/status/change', [ProjectsController::class, 'changeProjectStatus']);

    /* Assign Users to Projects Routes */
    Route::post('project/assign/users/list', [ProjectsController::class, 'assignUsersList']);
    Route::post('project/assign/users', [ProjectsController::class, 'assignUsers']);
    Route::post('project/un-assign/users', [ProjectsController::class, 'unAssignUsers']);

    /* Projects Non Working Days Routes */
    Route::post('project/non-working-day/get/lists', [NonWorkingDaysController::class, 'getNonWorkingDays']);
    Route::post('project/non-working-day/get/{id}', [NonWorkingDaysController::class, 'getNonWorkingDayDetails']);
    Route::post('project/non-working-day/add', [NonWorkingDaysController::class, 'addNonWorkingDay']);
    Route::post('project/non-working-day/update', [NonWorkingDaysController::class, 'updateNonWorkingDay']);
    Route::post('project/non-working-day/status/change', [NonWorkingDaysController::class, 'changeNonWorkingDayStatus']);

    /* Projects Manforces Routes */
    Route::post('project/manforce/get/lists', [ManforcesController::class, 'getManforces']);
    Route::post('project/manforce/get/{id}', [ManforcesController::class, 'getManforceDetails']);
    Route::post('project/manforce/add', [ManforcesController::class, 'addManforce']);
    Route::post('project/manforce/update', [ManforcesController::class, 'updateManforce']);
    Route::post('project/manforce/status/change', [ManforcesController::class, 'changeManforceStatus']);

    /* Projects Gangs Routes */
    Route::post('project/gang/get/lists', [GangsController::class, 'getGangs']);
    Route::post('project/gang/get/{id}', [GangsController::class, 'getGangDetails']);
    Route::post('project/gang/add', [GangsController::class, 'addGang']);
    Route::post('project/gang/update', [GangsController::class, 'updateGang']);
    Route::post('project/gang/status/change', [GangsController::class, 'changeGangStatus']);

    /* Projects Gangs Manforces Routes */
    Route::post('project/gang/manforce/get/lists', [GangsManforcesController::class, 'getGangsManforces']);
    Route::post('project/gang/manforce/get/{id}', [GangsManforcesController::class, 'getGangManforceDetails']);
    Route::post('project/gang/manforce/add', [GangsManforcesController::class, 'addGangManforce']);
    Route::post('project/gang/manforce/update', [GangsManforcesController::class, 'updateGangManforce']);
    Route::delete('project/gang/manforce/delete/{id}', [GangsManforcesController::class, 'deleteGangManforce']);

    /* Project Materials Routes */
    Route::post('project/material/get/lists', [MaterialController::class, 'getMaterials']);
    Route::post('project/material/get/{id}', [MaterialController::class, 'getMaterialsDetails']);
    Route::post('project/material/add', [MaterialController::class, 'addMaterial']);
    Route::post('project/material/update', [MaterialController::class, 'updateMaterial']);
    Route::delete('project/material/delete/{id}', [MaterialController::class, 'deleteMaterial']);
    Route::post('project/material/upload/format/file', [MaterialController::class, 'uploadMaterialFormatFile']);
    Route::post('project/material/export/format/file', [MaterialController::class, 'exportMaterialFormatFile']);
    Route::post('project/material/import', [MaterialController::class, 'importMaterial']);

    /* Projects Inventories Stocks Routes */
    Route::post('project/inventory/stock/get/lists', [InventoryStocksController::class, 'getInventoryStocks']);

    /* Projects Inventories Minimum Quantity Update Routes */
    Route::post('project/inventory/minimum-quantity/update', [InventoryStocksController::class, 'updateMinimunQuntity']);

    /* Projects Activity Management Routes */
    Route::post('project/activity/get/lists', [ActivitiesController::class, 'getActivities']);
    Route::post('project/activity/get/{id}', [ActivitiesController::class, 'getActivityDetails']);
    Route::post('project/activity/add', [ActivitiesController::class, 'addActivity']);
    Route::post('project/activity/update', [ActivitiesController::class, 'updateActivity']);
    Route::post('project/activity/status/change', [ActivitiesController::class, 'changeActivityStatus']);
    Route::delete('project/activity/delete/{id}', [ActivitiesController::class, 'deleteActivity']);

    /* Assign Material to Project Activity Route */
    Route::post('project/activity/get/lists', [ActivitiesController::class, 'getActivities']);
});
