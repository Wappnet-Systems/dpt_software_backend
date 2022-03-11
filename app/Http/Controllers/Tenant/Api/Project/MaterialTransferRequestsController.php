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
use App\Models\Tenant\ProjectMaterialTransferRequest;
use App\Helpers\AppHelper;

class MaterialTransferRequestsController extends Controller
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

    public function getMaterialTransferRequests(Request $request)
    {
        $status = !empty($request->status) ? $request->status : ProjectMaterialTransferRequest::STATUS['Pending'];
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectMaterialTransferRequest::with('fromProject', 'toProject', 'unitType', 'materialType')
            ->whereFromProjectId($request->from_project_id ?? '')
            ->whereStatus($status)
            ->orderby('id', $orderBy);

        if (isset($request->to_project_id) && !empty($request->to_project_id)) {
            $query = $query->orWhere('to_project_id', $request->to_project_id);
        }

        if ($request->exists('cursor')) {
            $transferReqs = $query->cursorPaginate($limit)->toArray();
        } else {
            $transferReqs['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($transferReqs['data'])) {
            $results = $transferReqs['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'per_page' => $transferReqs['per_page'],
                'next_page_url' => $transferReqs['next_page_url'],
                'prev_page_url' => $transferReqs['prev_page_url']
            ], 'Raising material request list.');
        } else {
            return $this->sendResponse($results, 'Material transfer request list.');
        }
    }

    public function getMaterialTransferRequestDetails(Request $request)
    {
        $transferReq = ProjectMaterialTransferRequest::with('fromProject', 'toProject', 'unitType', 'materialType')
            ->whereId($request->id)
            ->first();

        if (!isset($transferReq) || empty($transferReq)) {
            return $this->sendError('Material transfer request does not exist.');
        }

        return $this->sendResponse($transferReq, 'Material transfer request details.');
    }

    public function addMaterialTransferRequest(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'from_project_id' => 'required|exists:projects,id',
                    'to_project_id' => 'required|exists:projects,id',
                    'material_type_id' => 'required|exists:material_types,id',
                    'unit_type_id' => 'required|exists:unit_types,id',
                    'quantity' => 'required'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $projectInventory = ProjectInventory::whereProjectId($request->from_project_id)
                    ->whereMaterialTypeId($request->material_type_id)
                    ->whereUnitTypeId($request->unit_type_id)
                    ->first();

                if (!isset($projectInventory) || empty($projectInventory)) {
                    return $this->sendError('Requested quantity is not available.');
                }

                $totalQuantity = $projectInventory->remaining_quantity - $projectInventory->minimum_quantity;

                if ($request->quantity > $totalQuantity) {
                    return $this->sendError('Request quantity is not available.');
                }

                $transferReq = new ProjectMaterialTransferRequest();
                $transferReq->from_project_id = $request->from_project_id;
                $transferReq->to_project_id = $request->to_project_id;
                $transferReq->material_type_id = $request->material_type_id;
                $transferReq->unit_type_id = $request->unit_type_id;
                $transferReq->quantity = $request->quantity;
                $transferReq->cost = $projectInventory->average_cost;
                $transferReq->created_by = $user->id;
                $transferReq->created_ip = $request->ip();
                $transferReq->updated_ip = $request->ip();

                if (!$transferReq->save()) {
                    return $this->sendError('Something went wrong while creating the material raising request.');
                }

                return $this->sendResponse($transferReq, 'Material raising requested successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateMaterialTransferRequest(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'id' => 'required',
                    'from_project_id' => 'exists:projects,id',
                    'to_project_id' => 'exists:projects,id',
                    'material_type_id' => 'exists:material_types,id',
                    'unit_type_id' => 'exists:unit_types,id',
                    'quantity' => 'required'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $transferReq = ProjectMaterialTransferRequest::whereId($request->id)->first();

                if (!isset($transferReq) || empty($transferReq)) {
                    return $this->sendError('Material transfer request does not exist.');
                }

                if ($transferReq->status != ProjectMaterialTransferRequest::STATUS['Pending']) {
                    return $this->sendError('You can not update material transfer request.');
                }

                $projectInventory = ProjectInventory::whereProjectId($request->from_project_id)
                    ->whereMaterialTypeId($request->material_type_id)
                    ->whereUnitTypeId($request->unit_type_id)
                    ->first();

                if (!isset($projectInventory) || empty($projectInventory)) {
                    return $this->sendError('Requested quantity is not available.');
                }

                $totalQuantity = $projectInventory->remaining_quantity - $projectInventory->minimum_quantity;

                if ($request->quantity > $totalQuantity) {
                    return $this->sendError('Request quantity is not available.');
                }

                $transferReq->from_project_id = $request->from_project_id;
                $transferReq->to_project_id = $request->to_project_id;
                $transferReq->material_type_id = $request->material_type_id;
                $transferReq->unit_type_id = $request->unit_type_id;
                $transferReq->quantity = $request->quantity;
                $transferReq->cost = $projectInventory->average_cost;
                $transferReq->created_by = $user->id;
                $transferReq->created_ip = $request->ip();
                $transferReq->updated_ip = $request->ip();

                if (!$transferReq->save()) {
                    return $this->sendError('Something went wrong while creating the material raising request.');
                }

                return $this->sendResponse($transferReq, 'Material raising request updated successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function deleteMaterialTransferRequest(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $transferReq = ProjectMaterialTransferRequest::whereId($request->id)->first();

                if (!isset($transferReq) || empty($transferReq)) {
                    return $this->sendError('Material transfer request does not exist.');
                }

                if ($transferReq->status != ProjectMaterialTransferRequest::STATUS['Pending']) {
                    return $this->sendError('You can not delete material transfer request.');
                }

                $transferReq->delete();

                return $this->sendResponse([], 'Material raising request deleted successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeMaterialTransferRequestStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'status' => 'required'
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            if (!in_array($request->status, ProjectMaterialTransferRequest::STATUS)) {
                return $this->sendError('Invalid status requested.');
            }

            $transferReq = ProjectMaterialTransferRequest::whereId($request->id)->first();

            if (!isset($transferReq) || empty($transferReq)) {
                return $this->sendError('Project material raising request does not exists.');
            }

            if (!in_array($transferReq->status, [ProjectMaterialTransferRequest::STATUS['Pending'], ProjectMaterialTransferRequest::STATUS['Rejected']])) {
                return $this->sendError('Material transfer request already approved.');
            }

            if ($request->status == ProjectMaterialTransferRequest::STATUS['Rejected']) {
                if (!isset($request->reject_reasone) || empty($request->reject_reasone)) {
                    return $this->sendError('Validation Error.', ['reject_reasone' => 'The reject reasone field is required.']);
                }

                $transferReq->reject_reasone = $request->reject_reasone;
                $transferReq->status = $request->status;
                $transferReq->save();
            } else if ($request->status == ProjectMaterialTransferRequest::STATUS['Approved']) {
                // Update inventory of from project
                $fromProjectInventory = ProjectInventory::whereProjectId($transferReq->from_project_id)
                    ->whereMaterialTypeId($transferReq->material_type_id)
                    ->whereUnitTypeId($transferReq->unit_type_id)
                    ->first();

                if (isset($fromProjectInventory) && !empty($fromProjectInventory)) {
                    $fromProjectInventory->average_cost = ProjectInventory::reCalcAverageCost($fromProjectInventory->total_quantity, $fromProjectInventory->average_cost, $transferReq->quantity, $transferReq->cost);
                    $fromProjectInventory->total_quantity = $fromProjectInventory->total_quantity - $transferReq->quantity;
                    $fromProjectInventory->remaining_quantity = $fromProjectInventory->remaining_quantity - $transferReq->quantity;
                    $fromProjectInventory->updated_ip = $request->ip();
                    $fromProjectInventory->save();
                }

                // Update inventory of to project
                $toProjectInventory = ProjectInventory::whereProjectId($transferReq->to_project_id)
                    ->whereMaterialTypeId($transferReq->material_type_id)
                    ->whereUnitTypeId($transferReq->unit_type_id)
                    ->first();

                if (isset($toProjectInventory) && !empty($toProjectInventory)) {
                    $toProjectInventory->average_cost = ProjectInventory::calcAverageCost($toProjectInventory->total_quantity, $toProjectInventory->average_cost, $transferReq->quantity, $transferReq->cost);
                    $toProjectInventory->total_quantity = $toProjectInventory->total_quantity + $transferReq->quantity;
                    $toProjectInventory->remaining_quantity = $toProjectInventory->remaining_quantity + $transferReq->quantity;
                    $toProjectInventory->updated_ip = $request->ip();
                    $toProjectInventory->save();
                } else {
                    $projectInventory = new ProjectInventory();
                    $projectInventory->project_id = $transferReq->to_project_id;
                    $projectInventory->material_type_id = $transferReq->material_type_id;
                    $projectInventory->unit_type_id = $transferReq->unit_type_id;
                    $projectInventory->total_quantity = $transferReq->quantity;
                    $projectInventory->average_cost = $transferReq->cost;
                    $projectInventory->assigned_quantity = 0;
                    $projectInventory->remaining_quantity = $transferReq->quantity;
                    $projectInventory->minimum_quantity = 0;
                    $projectInventory->updated_ip = $request->ip();
                    $projectInventory->save();
                }

                $transferReq->status = $request->status;
                $transferReq->save();
            } else {
                $transferReq->status = $request->status;
                $transferReq->save();
            }

            return $this->sendResponse($transferReq, 'Status changed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
