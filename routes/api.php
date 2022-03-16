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
use App\Http\Controllers\Tenant\Api\TimeSlotsController;
use App\Http\Controllers\Tenant\Api\Project\ActivitiesController;
use App\Http\Controllers\Tenant\Api\Project\NonWorkingDaysController;
use App\Http\Controllers\Tenant\Api\Project\ManforcesController;
use App\Http\Controllers\Tenant\Api\Project\GangsController;
use App\Http\Controllers\Tenant\Api\Project\GangsManforcesController;
use App\Http\Controllers\Tenant\Api\Project\MaterialController;
use App\Http\Controllers\Tenant\Api\Project\InventoryStocksController;
use App\Http\Controllers\Tenant\Api\Project\IFCDrwaingsController;
use App\Http\Controllers\Tenant\Api\Project\MaterialAllocationController;
use App\Http\Controllers\Tenant\Api\Project\MaterialRaisingRequestsController;
use App\Http\Controllers\Tenant\Api\Project\MaterialTransferRequestsController;
use App\Http\Controllers\Tenant\Api\Project\MachineryAllocationController;
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
    Route::get('profile/details/get', [ProfileController::class, 'getProfileDetails']);
    Route::put('profile/details/update', [ProfileController::class, 'updateProfileDetails']);
    Route::post('change-password', [ProfileController::class, 'changePassword']);

    /** Organizations Management Routes */
    Route::get('organization/get/lists', [OrganizationController::class, 'getOrganizations']);
    Route::get('organization/get/{id}', [OrganizationController::class, 'getOrganizationDetails']);
    Route::post('organization/add', [OrganizationController::class, 'addOrganization']);
    Route::put('organization/update/{orgId}', [OrganizationController::class, 'updateOrganization']);
    Route::patch('organization/status/change/{orgId}', [OrganizationController::class, 'changeOrganizationStatus']);

    /** Organizations Management Routes */
    Route::get('role/get/lists', [RoleController::class, 'getRoles']);
    Route::get('role/get/{id}', [RoleController::class, 'getRoleDetails']);
    Route::post('role/add', [RoleController::class, 'addRole']);
    Route::put('role/update/{id}', [RoleController::class, 'updateRole']);
    Route::patch('role/status/change/{id}', [RoleController::class, 'changeRoleStatus']);

    /* Assign Modules Permission to Roles Routes */
    Route::get('role/module/permissions/get/{orgId}', [RoleController::class, 'getRoleModulePermissions']);
    Route::post('role/module/permissions/change', [RoleController::class, 'changeRoleModulePermissions']);

    /* Assign Sub Modules Permission to Roles Routes */
    Route::get('role/sub-module/permissions/get/{roleId}', [OrganizationRoleController::class, 'getRoleSubModulePermissions']);
    Route::post('role/sub-module/permissions/change', [OrganizationRoleController::class, 'changeRoleSubModulePermissions']);

    /** User Management Routes */
    Route::get('user/get/lists', [OrganizationUserController::class, 'getUsers']);
    Route::get('user/get/{id}', [OrganizationUserController::class, 'getUserDetails']);
    Route::post('user/add', [OrganizationUserController::class, 'addUser']);
    Route::put('user/update/{userUuid}', [OrganizationUserController::class, 'updateUser']);
    Route::patch('user/status/change/{userUuid}', [OrganizationUserController::class, 'changeUserStatus']);

    /* Unit Types Routes */
    Route::get('unit-type/get/lists', [UnitTypesController::class, 'getUnitTypes']);
    Route::get('unit-type/get/{id}', [UnitTypesController::class, 'getDetails']);
    Route::post('unit-type/add', [UnitTypesController::class, 'addUnitType']);
    Route::put('unit-type/update/{id}', [UnitTypesController::class, 'updateUnitType']);
    Route::patch('unit-type/status/change/{id}', [UnitTypesController::class, 'changeStatus']);

    /* Material Type Routes */
    Route::get('material-type/get/lists', [MaterialTypesController::class, 'getMaterialTypes']);
    Route::get('material-type/get/{id}', [MaterialTypesController::class, 'getDetails']);
    Route::post('material-type/add', [MaterialTypesController::class, 'addMaterialType']);
    Route::put('material-type/update/{id}', [MaterialTypesController::class, 'updateMaterialType']);
    Route::patch('material-type/status/change/{id}', [MaterialTypesController::class, 'changeStatus']);

    /* Manforce Type Routes */
    Route::get('manforce-type/get/lists', [ManforceTypesController::class, 'getManforceTypes']);
    Route::get('manforce-type/get/{id}', [ManforceTypesController::class, 'getDetails']);
    Route::post('manforce-type/add', [ManforceTypesController::class, 'addManforceType']);
    Route::put('manforce-type/update/{id}', [ManforceTypesController::class, 'updateManforceType']);
    Route::patch('manforce-type/status/change/{id}', [ManforceTypesController::class, 'changeStatus']);

    /* Activity Categories Type Routes */
    Route::get('activity-category/get/lists', [ActivityCategoriesController::class, 'getActivityCategory']);
    Route::get('activity-category/get/{id}', [ActivityCategoriesController::class, 'getDetails']);
    Route::post('activity-category/add', [ActivityCategoriesController::class, 'addActivityCategory']);
    Route::put('activity-category/update/{id}', [ActivityCategoriesController::class, 'updateActivityCategory']);
    Route::patch('activity-category/status/change/{id}', [ActivityCategoriesController::class, 'changeStatus']);

    /* Sub Activity Categories Type Routes */
    Route::get('sub-activity-category/get/lists', [SubActivityCategoriesController::class, 'getSubActivityCategory']);
    Route::get('sub-activity-category/get/{id}', [SubActivityCategoriesController::class, 'getDetails']);
    Route::post('sub-activity-category/add', [SubActivityCategoriesController::class, 'addSubActivityCategory']);
    Route::put('sub-activity-category/update/{id}', [SubActivityCategoriesController::class, 'updateSubActivityCategory']);
    Route::patch('sub-activity-category/status/change/{id}', [SubActivityCategoriesController::class, 'changeStatus']);

    /* Machineries Routes */
    Route::get('machinery/get/lists', [MachineriesController::class, 'getMachineries']);
    Route::get('machinery/get/{id}', [MachineriesController::class, 'getDetails']);
    Route::post('machinery/add', [MachineriesController::class, 'addMachineryCategory']);
    Route::put('machinery/update/{id}', [MachineriesController::class, 'updateMachineryCategory']);
    Route::patch('machinery/status/change/{id}', [MachineriesController::class, 'changeStatus']);

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

    /* Projects Raising Material Request Routes */
    Route::post('project/material/transfer/request/get/lists', [MaterialTransferRequestsController::class, 'getMaterialTransferRequests']);
    Route::post('project/material/transfer/request/get/{id}', [MaterialTransferRequestsController::class, 'getMaterialTransferRequestDetails']);
    Route::post('project/material/transfer/request/add', [MaterialTransferRequestsController::class, 'addMaterialTransferRequest']);
    Route::post('project/material/transfer/request/update', [MaterialTransferRequestsController::class, 'updateMaterialTransferRequest']);
    Route::delete('project/material/transfer/request/delete/{id}', [MaterialTransferRequestsController::class, 'deleteMaterialTransferRequest']);
    Route::post('project/material/transfer/request/status/change', [MaterialTransferRequestsController::class, 'changeMaterialTransferRequestStatus']);

    /* Projects Material Raising Request Routes */
    Route::post('project/material/raising/request/get/lists', [MaterialRaisingRequestsController::class, 'getMaterialRaisingRequests']);
    Route::post('project/material/raising/request/get/{id}', [MaterialRaisingRequestsController::class, 'getMaterialRaisingRequestDetails']);
    Route::post('project/material/raising/request/add', [MaterialRaisingRequestsController::class, 'addMaterialRaisingRequest']);
    Route::post('project/material/raising/request/update', [MaterialRaisingRequestsController::class, 'updateMaterialRaisingRequest']);
    Route::post('project/material/raising/request/status/change', [MaterialRaisingRequestsController::class, 'changeMaterialRaisingRequestStatus']);

    /* Allocate Material to Project Activities Routes */
    Route::post('project/allocate/material/get/lists', [MaterialAllocationController::class, 'getAllocateMaterials']);
    Route::post('project/allocate/material/get/{id}', [MaterialAllocationController::class, 'getAllocateMaterialDetails']);
    Route::post('project/allocate/material/add', [MaterialAllocationController::class, 'addAllocateMaterial']);
    Route::post('project/allocate/material/update', [MaterialAllocationController::class, 'updateAllocateMaterial']);
    Route::delete('project/allocate/material/delete/{id}', [MaterialAllocationController::class, 'deleteAllocateMaterial']);

    /* Project IFC Drawings Route */
    Route::post('project/ifc/drawing/get/lists', [IFCDrwaingsController::class, 'getIFCDrwaings']);
    Route::post('project/ifc/drawing/get/{id}', [IFCDrwaingsController::class, 'getIFCDrwaingDetails']);
    Route::post('project/ifc/drawing/add', [IFCDrwaingsController::class, 'addIFCDrwaing']);
    Route::post('project/ifc/drawing/update', [IFCDrwaingsController::class, 'updateIFCDrwaing']);
    Route::post('project/ifc/drawing/delete', [IFCDrwaingsController::class, 'deleteIFCDrwaing']);
    Route::post('project/ifc/drawing/status/change', [IFCDrwaingsController::class, 'changeIFCDrwaingStatus']);

    /* Time Slots Routes */
    Route::get('time-slot/get/lists', [TimeSlotsController::class, 'getTimeSlots']);

    /* Allocate Machinerie to Project Activities Routes */
    Route::post('project/allocate/machinery/get/lists', [MachineryAllocationController::class, 'getAllocateMachineries']);
    Route::post('project/allocate/machinery', [MachineryAllocationController::class, 'allocateMachinery']);
    Route::post('project/allocate/machinery/delete', [MachineryAllocationController::class, 'deleteAllocateMachinery']);
});
