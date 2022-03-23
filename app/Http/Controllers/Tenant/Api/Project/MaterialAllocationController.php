<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectInventory;
use App\Models\Tenant\ProjectActivityAllocateMaterial;
use App\Helpers\AppHelper;
use App\Models\Tenant\ProjectActivityMaterialUses;
use App\Models\Tenant\RoleHasSubModule;

class MaterialAllocationController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.');
                }

                if (!AppHelper::roleHasModulePermission('Planning and Scheduling', $user)) {
                    return $this->sendError('You have no rights to access this module.');
                }

                $hostnameId = Organization::whereId($user->organization_id)->value('hostname_id');

                $hostname = Hostname::whereId($hostnameId)->first();
                $website = Website::whereId($hostname->website_id)->first();

                $environment = app(\Hyn\Tenancy\Environment::class);
                $hostname = Hostname::whereWebsiteId($website->id)->first();

                $environment->tenant($website);
                $environment->hostname($hostname);

                AppHelper::setDefaultDBConnection();
            }

            return $next($request);
        });
    }

    public function getAllocateMaterials(Request $request)
    {
        $user = $request->user();

        if (!AppHelper::roleHasSubModulePermission('Material Sheet', RoleHasSubModule::ACTIONS['list'], $user)) {
            return $this->sendError('You have no rights to access this action.');
        }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectActivityAllocateMaterial::with('projectActivity', 'projectInventory')
            ->whereProjectActivityId($request->project_activity_id ?? '')
            ->orderby('id', $orderBy);

        if (isset($request->project_inventory_id) && !empty($request->project_inventory_id)) {
            $query = $query->orWhere('project_inventory_id', $request->project_inventory_id);
        }

        if ($request->exists('cursor')) {
            $allocatedMaterial = $query->cursorPaginate($limit)->toArray();
        } else {
            $allocatedMaterial['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($allocatedMaterial['data'])) {
            $results = $allocatedMaterial['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'per_page' => $allocatedMaterial['per_page'],
                'next_page_url' => $allocatedMaterial['next_page_url'],
                'prev_page_url' => $allocatedMaterial['prev_page_url']
            ], 'Activity allocated materials list.');
        } else {
            return $this->sendResponse($results, 'Activity allocated materials list.');
        }
    }

    public function getAllocateMaterialDetails(Request $request)
    {
        $user = $request->user();

        if (!AppHelper::roleHasSubModulePermission('Material Sheet', RoleHasSubModule::ACTIONS['view'], $user)) {
            return $this->sendError('You have no rights to access this action.');
        }

        $allocatedMaterial = ProjectActivityAllocateMaterial::with('projectActivity', 'projectInventory')
            ->whereId($request->id)
            ->first();

        if (!isset($allocatedMaterial) || empty($allocatedMaterial)) {
            return $this->sendError('Material not allocated to the activity.');
        }

        return $this->sendResponse($allocatedMaterial, 'Activity allocated material details.');
    }

    public function addAllocateMaterial(Request $request)
    {
        try {
            $user = $request->user();

            if (!AppHelper::roleHasSubModulePermission('Material Sheet', RoleHasSubModule::ACTIONS['create'], $user)) {
                return $this->sendError('You have no rights to access this action.');
            }

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_activity_id' => 'required|exists:projects_activities,id',
                    'project_inventory_id' => 'required|exists:projects_inventories,id',
                    'quantity' => 'required'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $projectInventory = ProjectInventory::whereId($request->project_inventory_id)->first();

                if (!isset($projectInventory) || empty($projectInventory)) {
                    return $this->sendError('Requested material is not available in inventory.');
                }

                // Check requested quantity not more than inventory remaining quantity
                if ($request->quantity > $projectInventory->remaining_quantity) {
                    return $this->sendError('Requested quantity not more than inventory remaining quantity.');
                }

                // Check already allocated material in activity
                $allocatedMaterial = ProjectActivityAllocateMaterial::whereProjectActivityId($request->project_activity_id)
                    ->whereProjectInventoryId($request->project_inventory_id)
                    ->first();

                // Update exist allocated material or new allocate material in activity
                if (isset($allocatedMaterial) && !empty($allocatedMaterial)) {
                    $allocatedMaterial->cost = ProjectActivityAllocateMaterial::calcAverageCost($allocatedMaterial->remaining_quantity, $allocatedMaterial->cost, $request->quantity, $projectInventory->average_cost);
                    $allocatedMaterial->total_quantity = $allocatedMaterial->total_quantity + $request->quantity;
                    $allocatedMaterial->remaining_quantity = $allocatedMaterial->remaining_quantity + $request->quantity;
                } else {
                    $allocatedMaterial = new ProjectActivityAllocateMaterial();
                    $allocatedMaterial->project_activity_id = $request->project_activity_id;
                    $allocatedMaterial->project_inventory_id = $request->project_inventory_id;
                    $allocatedMaterial->cost = $projectInventory->average_cost;
                    $allocatedMaterial->total_quantity = $request->quantity;
                    $allocatedMaterial->remaining_quantity = $request->quantity;
                    $allocatedMaterial->created_ip = $request->ip();
                }

                $allocatedMaterial->assign_by = $user->id;
                $allocatedMaterial->updated_ip = $request->ip();

                if (!$allocatedMaterial->save()) {
                    return $this->sendError('Something went wrong while creating the allocating material to activity.');
                }

                /** Change remainig quantity after allocate material to activity */
                $projectInventory->remaining_quantity = $projectInventory->remaining_quantity - $request->quantity;
                $projectInventory->save();

                return $this->sendResponse($allocatedMaterial, 'Material allocating into activity successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateAllocateMaterial(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (!AppHelper::roleHasSubModulePermission('Material Sheet', RoleHasSubModule::ACTIONS['edit'], $user)) {
                return $this->sendError('You have no rights to access this action.');
            }

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'quantity' => 'required',
                    'is_increase' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $allocatedMaterial = ProjectActivityAllocateMaterial::whereId($request->id)->first();

                if (!isset($allocatedMaterial) || empty($allocatedMaterial)) {
                    return $this->sendError('Material not allocated to the activity.');
                }

                // Check material is found or not in inventory
                $projectInventory = ProjectInventory::whereId($allocatedMaterial->project_inventory_id)->first();

                if (!isset($projectInventory) || empty($projectInventory)) {
                    return $this->sendError('Requested material is not available in inventory.');
                }

                if ($request->is_increase) {
                    // Find actual quantity from remaining allocated quantity
                    $requestQty = $request->quantity - $allocatedMaterial->remaining_quantity;

                    if ($requestQty > $projectInventory->remaining_quantity) {
                        return $this->sendError('Requested quantity not more than inventory remaining quantity.');
                    }

                    // Update exist allocated material in activity
                    $allocatedMaterial->cost = ProjectActivityAllocateMaterial::calcAverageCost($allocatedMaterial->remaining_quantity, $allocatedMaterial->cost, $requestQty, $projectInventory->average_cost);
                    $allocatedMaterial->total_quantity = $allocatedMaterial->total_quantity + $requestQty;
                    $allocatedMaterial->remaining_quantity = $allocatedMaterial->remaining_quantity + $requestQty;
                    $allocatedMaterial->save();

                    /** Change remainig quantity after allocate material to activity */
                    $projectInventory->remaining_quantity = $projectInventory->remaining_quantity - $requestQty;
                    $projectInventory->save();
                } else {
                    // Find actual quantity from remaining allocated quantity
                    $requestQty = $allocatedMaterial->remaining_quantity - $request->quantity;

                    if ($requestQty > $allocatedMaterial->remaining_quantity) {
                        return $this->sendError('Requested quantity not more than allocated remaining quantity.');
                    }

                    // Update exist allocated material in activity
                    $allocatedMaterial->cost = ProjectActivityAllocateMaterial::reCalcAverageCost($allocatedMaterial->remaining_quantity, $allocatedMaterial->cost, $requestQty, $allocatedMaterial->cost);
                    $allocatedMaterial->total_quantity = $allocatedMaterial->total_quantity - $requestQty;
                    $allocatedMaterial->remaining_quantity = $allocatedMaterial->remaining_quantity - $requestQty;
                    $allocatedMaterial->save();

                    // Update inventory quantity of material
                    $projectInventory->average_cost = ProjectInventory::calcAverageCost($projectInventory->remaining_quantity, $projectInventory->average_cost, $requestQty, $allocatedMaterial->cost);
                    // $projectInventory->total_quantity = $projectInventory->total_quantity + $requestQty;
                    $projectInventory->remaining_quantity = $projectInventory->remaining_quantity + $requestQty;
                    $projectInventory->save();
                }

                return $this->sendResponse($allocatedMaterial, 'Material allocating into activity successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function deleteAllocateMaterial(Request $request)
    {
        try {
            $user = $request->user();

            if (!AppHelper::roleHasSubModulePermission('Material Sheet', RoleHasSubModule::ACTIONS['delete'], $user)) {
                return $this->sendError('You have no rights to access this action.');
            }

            if (isset($user) && !empty($user)) {
                $allocatedMaterial = ProjectActivityAllocateMaterial::whereId($request->id)->first();

                if (!isset($allocatedMaterial) || empty($allocatedMaterial)) {
                    return $this->sendError('Material not allocated to the activity.');
                }

                // Check material is already used in activity
                $isUsedMaterial = ProjectActivityMaterialUses::where('project_allocate_material_id', $request->id)
                    ->where('project_activity_id', $allocatedMaterial->project_activity_id)
                    ->exists();

                if ($isUsedMaterial) {
                    return $this->sendError('You can not remove allocated material from activity.');
                }

                // Check material is found or not in inventory
                $projectInventory = ProjectInventory::whereId($allocatedMaterial->project_inventory_id)->first();

                if (!isset($projectInventory) || empty($projectInventory)) {
                    return $this->sendError('Requested material is not available in inventory.');
                }

                // Update inventory quantity of material
                $projectInventory->average_cost = ProjectInventory::calcAverageCost($projectInventory->remaining_quantity, $projectInventory->average_cost, $allocatedMaterial->quantity, $allocatedMaterial->cost);
                // $projectInventory->total_quantity = $projectInventory->total_quantity + $allocatedMaterial->quantity;
                $projectInventory->remaining_quantity = $projectInventory->remaining_quantity + $allocatedMaterial->quantity;
                $projectInventory->save();

                $allocatedMaterial->delete();

                return $this->sendResponse([], 'Allocated material removed from activity successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
