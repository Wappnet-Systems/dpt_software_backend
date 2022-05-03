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
use App\Http\Controllers\Tenant\Api\ActivitySubCategoriesController;
use App\Http\Controllers\Tenant\Api\ProjectsController;
use App\Http\Controllers\Tenant\Api\TimeSlotsController;
use App\Http\Controllers\Tenant\Api\Project\MachineriesController;
use App\Http\Controllers\Tenant\Api\Project\ActivitiesController;
use App\Http\Controllers\Tenant\Api\Project\NonWorkingDaysController;
use App\Http\Controllers\Tenant\Api\Project\ManforcesController;
use App\Http\Controllers\Tenant\Api\Project\GangsController;
use App\Http\Controllers\Tenant\Api\Project\GangsManforcesController;
use App\Http\Controllers\Tenant\Api\Project\MaterialController;
use App\Http\Controllers\Tenant\Api\Project\InventoryStocksController;
use App\Http\Controllers\Tenant\Api\Project\ActivityDocumentController;
use App\Http\Controllers\Tenant\Api\Project\MaterialAllocationController;
use App\Http\Controllers\Tenant\Api\Project\MaterialRaisingRequestsController;
use App\Http\Controllers\Tenant\Api\Project\MaterialTransferRequestsController;
use App\Http\Controllers\Tenant\Api\Project\MachineryAllocationController;
use App\Http\Controllers\Tenant\Api\Project\ManforcesAllocationController;
use App\Http\Controllers\Tenant\Api\Project\InspectionController;
use App\Http\Controllers\Tenant\Api\Project\MethodStatementController;
use App\Http\Controllers\Tenant\Api\Project\ManforceProductivityController;
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
    // Route::get('role/get/lists', [RoleController::class, 'getRoles']);
    // Route::get('role/get/{id}', [RoleController::class, 'getRoleDetails']);
    // Route::post('role/add', [RoleController::class, 'addRole']);
    // Route::put('role/update/{id}', [RoleController::class, 'updateRole']);
    // Route::patch('role/status/change/{id}', [RoleController::class, 'changeRoleStatus']);

    /* Assign Modules Permission to Roles Routes */
    Route::get('role/module/permissions/get/{orgId?}', [RoleController::class, 'getRoleModulePermissions']);
    Route::post('role/module/permissions/change', [RoleController::class, 'changeRoleModulePermissions']);

    /* Assign Sub Modules Permission to Roles Routes */
    Route::get('role/get/lists', [OrganizationRoleController::class, 'getRoles']);
    Route::get('role/get/{id}', [OrganizationRoleController::class, 'getRoleDetails']);
    Route::post('role/add', [OrganizationRoleController::class, 'addRole']);
    Route::put('role/update/{id}', [OrganizationRoleController::class, 'updateRole']);
    Route::patch('role/status/change/{id}', [OrganizationRoleController::class, 'changeRoleStatus']);
    Route::get('role/sub-module/permissions/get/{roleId}', [OrganizationRoleController::class, 'getRoleSubModulePermissions']);
    Route::get('role/sub-module/permissions/by/user/get', [OrganizationRoleController::class, 'getAssignSubModulesByLoginUser']);
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
    Route::get('activity-sub-category/get/lists', [ActivitySubCategoriesController::class, 'getActivitySubCategory']);
    Route::get('activity-sub-category/get/{id}', [ActivitySubCategoriesController::class, 'getDetails']);
    Route::post('activity-sub-category/add', [ActivitySubCategoriesController::class, 'addActivitySubCategory']);
    Route::put('activity-sub-category/update/{id}', [ActivitySubCategoriesController::class, 'updateActivitySubCategory']);
    Route::patch('activity-sub-category/status/change/{id}', [ActivitySubCategoriesController::class, 'changeStatus']);

    /* Machineries Routes */
    Route::get('project/machinery/get/lists', [MachineriesController::class, 'getMachineries']);
    Route::get('project/machinery/get/{id}', [MachineriesController::class, 'getDetails']);
    Route::post('project/machinery/add', [MachineriesController::class, 'addMachineryCategory']);
    Route::put('project/machinery/update/{id}', [MachineriesController::class, 'updateMachineryCategory']);
    Route::patch('project/machinery/status/change/{id}', [MachineriesController::class, 'changeStatus']);

    /* Projects Routes */
    Route::get('project/get/lists', [ProjectsController::class, 'getProjects']);
    Route::get('project/get/{id}', [ProjectsController::class, 'getProjectDetails']);
    Route::post('project/add', [ProjectsController::class, 'addProject']);
    Route::put('project/update/{Uuid}', [ProjectsController::class, 'updateProject']);
    Route::delete('project/delete/{Uuid}', [ProjectsController::class, 'deleteProject']);
    Route::patch('project/status/change/{Uuid}', [ProjectsController::class, 'changeProjectStatus']);

    /* Assign Users to Projects Routes */
    Route::get('project/assign/users/list', [ProjectsController::class, 'assignUsersList']);
    Route::post('project/assign/users', [ProjectsController::class, 'assignUsers']);
    Route::post('project/un-assign/users', [ProjectsController::class, 'unAssignUsers']);

    /* Projects Non Working Days Routes */
    Route::get('project/non-working-day/get/lists', [NonWorkingDaysController::class, 'getNonWorkingDays']);
    Route::get('project/non-working-day/get/{id}', [NonWorkingDaysController::class, 'getNonWorkingDayDetails']);
    Route::post('project/non-working-day/add', [NonWorkingDaysController::class, 'addNonWorkingDay']);
    Route::put('project/non-working-day/update/{id}', [NonWorkingDaysController::class, 'updateNonWorkingDay']);
    Route::patch('project/non-working-day/status/change/{id}', [NonWorkingDaysController::class, 'changeNonWorkingDayStatus']);

    /* Projects Manforces Routes */
    Route::get('project/manforce/get/lists', [ManforcesController::class, 'getManforces']);
    Route::get('project/manforce/get/{id}', [ManforcesController::class, 'getManforceDetails']);
    Route::post('project/manforce/add', [ManforcesController::class, 'addManforce']);
    Route::put('project/manforce/update/{id}', [ManforcesController::class, 'updateManforce']);
    Route::delete('project/manforce/delete/{id}', [ManforcesController::class, 'deleteManforce']);

    /* Projects Gangs Routes */
    Route::get('project/gang/get/lists', [GangsController::class, 'getGangs']);
    Route::get('project/gang/get/{id}', [GangsController::class, 'getGangDetails']);
    Route::post('project/gang/add', [GangsController::class, 'addGang']);
    Route::put('project/gang/update/{id}', [GangsController::class, 'updateGang']);
    Route::patch('project/gang/status/change/{id}', [GangsController::class, 'changeGangStatus']);

    /* Projects Gangs Manforces Routes */
    Route::get('project/gang/manforce/get/lists', [GangsManforcesController::class, 'getGangsManforces']);
    Route::get('project/gang/manforce/get/{id}', [GangsManforcesController::class, 'getGangManforceDetails']);
    Route::post('project/gang/manforce/add', [GangsManforcesController::class, 'addGangManforce']);
    Route::put('project/gang/manforce/update/{id}', [GangsManforcesController::class, 'updateGangManforce']);
    Route::delete('project/gang/manforce/delete/{id}', [GangsManforcesController::class, 'deleteGangManforce']);

    /* Project Materials Routes */
    Route::get('project/material/get/lists', [MaterialController::class, 'getMaterials']);
    Route::get('project/material/get/{id}', [MaterialController::class, 'getMaterialsDetails']);
    Route::post('project/material/add', [MaterialController::class, 'addMaterial']);
    Route::put('project/material/update/{id}', [MaterialController::class, 'updateMaterial']);
    Route::delete('project/material/delete/{id}', [MaterialController::class, 'deleteMaterial']);
    Route::post('project/material/upload/format/file', [MaterialController::class, 'uploadMaterialFormatFile']);
    Route::post('project/material/export/format/file', [MaterialController::class, 'exportMaterialFormatFile']);
    Route::post('project/material/import', [MaterialController::class, 'importMaterial']);

    /* Projects Inventories Stocks Routes */
    Route::get('project/inventory/stock/get/lists', [InventoryStocksController::class, 'getInventoryStocks']);

    /* Projects Inventories Minimum Quantity Update Routes */
    Route::put('project/inventory/minimum-quantity/update/{projectInventoryId}', [InventoryStocksController::class, 'updateMinimunQuntity']);

    /* Projects Activity Management Routes */
    Route::get('project/activity/get/lists', [ActivitiesController::class, 'getActivities']);
    Route::get('project/activity/get/{id}', [ActivitiesController::class, 'getActivityDetails']);
    Route::post('project/activity/add', [ActivitiesController::class, 'addActivity']);
    Route::put('project/activity/update/{id}', [ActivitiesController::class, 'updateActivity']);
    Route::patch('project/activity/status/change/{id}', [ActivitiesController::class, 'changeActivityStatus']);
    Route::delete('project/activity/delete/{id}', [ActivitiesController::class, 'deleteActivity']);

    /* Projects Material Transfer Request Routes */
    Route::get('project/material/transfer/request/get/lists', [MaterialTransferRequestsController::class, 'getMaterialTransferRequests']);
    Route::get('project/material/transfer/request/get/{id}', [MaterialTransferRequestsController::class, 'getMaterialTransferRequestDetails']);
    Route::post('project/material/transfer/request/add', [MaterialTransferRequestsController::class, 'addMaterialTransferRequest']);
    Route::put('project/material/transfer/request/update/{id}', [MaterialTransferRequestsController::class, 'updateMaterialTransferRequest']);
    Route::delete('project/material/transfer/request/delete/{id}', [MaterialTransferRequestsController::class, 'deleteMaterialTransferRequest']);
    Route::patch('project/material/transfer/request/status/change/{id}', [MaterialTransferRequestsController::class, 'changeMaterialTransferRequestStatus']);
    Route::patch('project/material/transfer/request/receiver-status/change/{id}', [MaterialTransferRequestsController::class, 'changeMaterialTransferRequestReceiverStatus']);

    /* Projects Material Raising Request Routes */
    Route::get('project/material/raising/request/get/lists', [MaterialRaisingRequestsController::class, 'getMaterialRaisingRequests']);
    Route::get('project/material/raising/request/get/{id}', [MaterialRaisingRequestsController::class, 'getMaterialRaisingRequestDetails']);
    Route::post('project/material/raising/request/add', [MaterialRaisingRequestsController::class, 'addMaterialRaisingRequest']);
    Route::put('project/material/raising/request/update/{id}', [MaterialRaisingRequestsController::class, 'updateMaterialRaisingRequest']);
    Route::patch('project/material/raising/request/status/change/{id}', [MaterialRaisingRequestsController::class, 'changeMaterialRaisingRequestStatus']);
    Route::delete('project/material/raising/request/delete/{id}', [MaterialRaisingRequestsController::class, 'deleteMaterialRaisingRequest']);

    /* Allocate Material to Project Activities Routes */
    Route::get('project/allocate/material/get/lists', [MaterialAllocationController::class, 'getAllocateMaterials']);
    Route::get('project/allocate/material/get/{id}', [MaterialAllocationController::class, 'getAllocateMaterialDetails']);
    Route::post('project/allocate/material/add', [MaterialAllocationController::class, 'addAllocateMaterial']);
    Route::put('project/allocate/material/update/{id}', [MaterialAllocationController::class, 'updateAllocateMaterial']);
    Route::delete('project/allocate/material/delete/{id}', [MaterialAllocationController::class, 'deleteAllocateMaterial']);

    /* Project Activity Documents Route */
    Route::get('project/activity/document/get/lists', [ActivityDocumentController::class, 'getActivityDocument']);
    Route::get('project/activity/document/get/{id}', [ActivityDocumentController::class, 'getActivityDocumentDetails']);
    Route::post('project/activity/document/add', [ActivityDocumentController::class, 'addActivityDocument']);
    Route::put('project/activity/document/update/{id}', [ActivityDocumentController::class, 'updateActivityDocument']);
    Route::patch('project/activity/document/status/change/{id}', [ActivityDocumentController::class, 'changeActivityDocumentStatus']);
    Route::put('project/activity/document/assign-activity/{id}', [ActivityDocumentController::class, 'assignActivityDocument']);

    /* Time Slots Routes */
    Route::get('time-slot/get/lists', [TimeSlotsController::class, 'getTimeSlots']);

    /* Allocate Machinerie to Project Activities Routes */
    Route::post('project/allocate/machinery/get/lists', [MachineryAllocationController::class, 'getAllocateMachineries']);
    Route::post('project/allocate/machinery/time-slots/get', [MachineryAllocationController::class, 'getAllocateMachineryTimeSlots']);
    Route::post('project/allocate/machinery', [MachineryAllocationController::class, 'allocateMachinery']);
    Route::patch('project/allocate/machinery/delete/{id}', [MachineryAllocationController::class, 'deleteAllocateMachinery']);

    /* Allocate Manforce to Project Activities Routes */
    Route::get('project/allocate/manforce/get/lists', [ManforcesAllocationController::class, 'getAllocateManforces']);
    Route::get('project/allocate/manforce/get/{id}', [ManforcesAllocationController::class, 'getAllocateManforcesDetails']);
    Route::post('project/allocate/manforce/add', [ManforcesAllocationController::class, 'addAllocateManforces']);
    Route::put('project/allocate/manforce/update/{id}', [ManforcesAllocationController::class, 'updateAllocateManforces']);
    Route::delete('project/allocate/manforce/delete/{id}', [ManforcesAllocationController::class, 'deleteAllocateManforces']);

    /* Activity Manpower Routes */
    Route::get('project/activity/manpower/get/lists', [ManforcesAllocationController::class, 'getActivityManpower']);
    
    /* Manforce Productivity Routes */
    Route::get('project/manforce/productivity/get/lists', [ManforceProductivityController::class, 'getManforceProductivity']);

    /* Project Inspection Routes */
    Route::get('project/inspection/get/lists', [InspectionController::class, 'getProjectInspection']);
    Route::get('project/inspection/get/{id}', [InspectionController::class, 'getProjectInspectionDetails']);
    Route::post('project/inspection/add', [InspectionController::class, 'addProjectInspection']);
    Route::put('project/inspection/update/{id}', [InspectionController::class, 'updateProjectInspection']);
    Route::patch('project/inspection/status/change/{id}', [InspectionController::class, 'projectInspectionChangeStatus']);
    Route::delete('project/inspection/delete/{id}', [InspectionController::class, 'deleteProjectInspection']);
    Route::patch('project/inspection/approve-reject/{id}', [InspectionController::class, 'projectInspectionApproveReject']);

    /* Method Statement Routes */
    Route::get('project/method-statements/get/lists', [MethodStatementController::class, 'getMethodStatements']);
    Route::get('project/method-statements/get/{id}', [MethodStatementController::class, 'getmethodStatementDetails']);
    Route::post('project/method-statements/add', [MethodStatementController::class, 'addMethodStatement']);
    Route::put('project/method-statements/update/{id}', [MethodStatementController::class, 'updateMethodStatement']);
    Route::delete('project/method-statements/delete/{id}', [MethodStatementController::class, 'deleteMethodStatement']);
    Route::put('project/method-statements/update-activity/{id}', [MethodStatementController::class, 'updateMethodActivity']);
});
