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
use App\Models\Tenant\ProjectRaisingMaterialRequest;
use App\Helpers\AppHelper;

class InventoryStocksController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
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

    public function getInventoryStocks(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectInventory::with('project', 'materials', 'unitType')
            ->whereProjectId($request->project_id ?? '')
            ->whereStatus(ProjectInventory::STATUS['Active'])
            ->orderby('id', $orderBy);

        if ($request->exists('cursor')) {
            $projectInventory = $query->cursorPaginate($limit)->toArray();
        } else {
            $projectInventory['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($projectInventory['data'])) {
            $results = $projectInventory['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'per_page' => $projectInventory['per_page'],
                'next_page_url' => $projectInventory['next_page_url'],
                'prev_page_url' => $projectInventory['prev_page_url']
            ], 'Project material List.');
        } else {
            return $this->sendResponse($results, 'Project inventory material List.');
        }
    }

    public function updateMinimunQuntity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_inventory_id' => 'required',
            'minimum_quantity' => 'required',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                return $this->sendError('Validation Error.', [$key => $value[0]]);
            }
        }

        $projectInventory = ProjectInventory::whereId($request->project_inventory_id)->first();

        if (!isset($projectInventory) && empty($projectInventory)) {
            return $this->sendError('Project inventory does not exist.');
        }

        if ($request->filled('minimum_quantity')) $projectInventory->minimum_quantity = $request->minimum_quantity;

        if (!$projectInventory->save()) {
            return $this->sendError('Something went wrong while updating the project inventory');
        }

        return $this->sendResponse($projectInventory, 'Project inventory updated successfully.');
    }

    /* Projects Raising Material Request */

    public function getMaterialRaisingRequests(Request $request)
    {
        # code...
    }

    public function getMaterialRaisingRequestDetails(Request $request)
    {
        # code...
    }

    public function addMaterialRaisingRequest(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'from_project_id' => 'required|exists:projects,id',
                    'to_project_id' => 'required|exists:projects,id',
                    'material_type_id' => 'required|exists:material_types,id',
                    'unit_type_id' => 'required|exists:unit_types,id',
                    'quantity' => 'required',
                    'cost' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $projectInventory = ProjectInventory::whereProjectId($request->from_project_id)
                    ->whereMaterialTypeId($request->material_type_id)->whereUnitTypeId($request->unit_type_id)
                    ->first();
                $totalQuantity = $projectInventory->total_quantity - $projectInventory->minimum_quantity;

                
                if ($request->quantity > $totalQuantity) {
                    return $this->sendError('Request quantity is not available.');
                }

                $projectRaisingMaterialRequest = new ProjectRaisingMaterialRequest();
                $projectRaisingMaterialRequest->from_project_id = $request->from_project_id;
                $projectRaisingMaterialRequest->to_project_id = $request->to_project_id;
                $projectRaisingMaterialRequest->material_type_id = $request->material_type_id;
                $projectRaisingMaterialRequest->unit_type_id = $request->unit_type_id;
                $projectRaisingMaterialRequest->request_no = rand(000000,999999);
                $projectRaisingMaterialRequest->quantity = $request->quantity;
                $projectRaisingMaterialRequest->cost = $request->cost;
                $projectRaisingMaterialRequest->created_by = $user->id;
                $projectRaisingMaterialRequest->created_ip = $request->ip();
                $projectRaisingMaterialRequest->updated_ip = $request->ip();

                if (!$projectRaisingMaterialRequest->save()) {
                    return $this->sendError('Something went wrong while creating the project raising material request.');
                }

                return $this->sendResponse($projectRaisingMaterialRequest, 'Project raising material request created successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateMaterialRaisingRequest(Request $request)
    {
        # code...
    }

    public function deleteMaterialRaisingRequest(Request $request)
    {
        # code...
    }

    public function changeMaterialRaisingRequestStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            $projectRaisingMaterialRequest = ProjectRaisingMaterialRequest::whereId($request->id)
                ->first();

            if (!isset($projectRaisingMaterialRequest) || empty($projectRaisingMaterialRequest)) {
                return $this->sendError('Project raising material request does not exists.');
            }

            $projectRaisingMaterialRequest->status = $request->status;
            $projectRaisingMaterialRequest->save();

            return $this->sendResponse($projectRaisingMaterialRequest, 'Status changed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
