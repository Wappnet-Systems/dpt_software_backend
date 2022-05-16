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
use App\Models\Tenant\ProjectMaterialRaisingRequest;
use App\Helpers\AppHelper;
use Illuminate\Support\Facades\Log;

class MaterialRaisingRequestsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.', [], 401);
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

    public function getMaterialRaisingRequests(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectMaterialRaisingRequest::with('project', 'materialType', 'unitType')
            ->whereProjectId($request->project_id ?? '')
            ->orderby('id', $orderBy);

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $materialRaisingRequest = $query->cursorPaginate($limit)->toArray();
        } else {
            $materialRaisingRequest['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($materialRaisingRequest['data'])) {
            $results = $materialRaisingRequest['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $materialRaisingRequest['per_page'],
                'next_page_url' => ltrim(str_replace($materialRaisingRequest['path'], "", $materialRaisingRequest['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($materialRaisingRequest['path'], "", $materialRaisingRequest['prev_page_url']), "?cursor=")
            ], 'Project material List.');
        } else {
            return $this->sendResponse($results, 'Project material raising request List.');
        }
    }

    public function getMaterialRaisingRequestDetails(Request $request)
    {
        $materialRaisingRequest = ProjectMaterialRaisingRequest::with('project', 'materialType', 'unitType')
            ->whereId($request->id)
            ->first();

        if (!isset($materialRaisingRequest) || empty($materialRaisingRequest)) {
            return $this->sendError('Project material raising request does not exist.');
        }

        return $this->sendResponse($materialRaisingRequest, 'Project material raising request details.');
    }

    public function addMaterialRaisingRequest(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'material_type_id' => 'required|exists:material_types,id',
                    'unit_type_id' => 'required|exists:unit_types,id',
                    'quantity' => 'required|numeric|gt:0'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $materialRaisingRequest = new ProjectMaterialRaisingRequest();
                $materialRaisingRequest->project_id = $request->project_id;
                $materialRaisingRequest->material_type_id = $request->material_type_id;
                $materialRaisingRequest->unit_type_id = $request->unit_type_id;
                $materialRaisingRequest->quantity = $request->quantity;
                $materialRaisingRequest->created_by = $user->id;
                $materialRaisingRequest->created_ip = $request->ip();
                $materialRaisingRequest->updated_ip = $request->ip();

                if (!$materialRaisingRequest->save()) {
                    return $this->sendError('Something went wrong while creating the project material raising request.');
                }

                return $this->sendResponse([], 'Project material raising request created successfully.');
            } else {
                return $this->sendError('User does not exist');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateMaterialRaisingRequest(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'material_type_id' => 'required|exists:material_types,id',
                    'unit_type_id' => 'required|exists:unit_types,id',
                    'quantity' => 'required|numeric|gt:0'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $materialRaisingRequest = ProjectMaterialRaisingRequest::whereId($request->id)
                    ->first();

                if (!isset($materialRaisingRequest) || empty($materialRaisingRequest)) {
                    return $this->sendError('Project material raising request does not exist.');
                }

                $materialRaisingRequest->material_type_id = $request->material_type_id;
                $materialRaisingRequest->unit_type_id = $request->unit_type_id;
                $materialRaisingRequest->quantity = $request->quantity;
                $materialRaisingRequest->updated_ip = $request->ip();

                if (!$materialRaisingRequest->save()) {
                    return $this->sendError('Something went wrong while creating the project material raising request.');
                }

                return $this->sendResponse([], 'Project material raising request updated successfully.');
            } else {
                return $this->sendError('User does not exist');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function deleteMaterialRaisingRequest(Request $request)
    {
        try {
            $user = $request->user();
            if (isset($user) && !empty($user)) {
                $materialRaisingRequest = ProjectMaterialRaisingRequest::whereId($request->id)
                    ->first();

                if (!isset($materialRaisingRequest) || empty($materialRaisingRequest)) {
                    return $this->sendError('Project material raising request does not exist.');
                }

                if ($materialRaisingRequest->status == ProjectMaterialRaisingRequest::STATUS['Approved']) {
                    return $this->sendError('You can not delete the project material raising request.');
                }

                $materialRaisingRequest->delete();

                return $this->sendResponse([], 'Project material raising request deleted Successfully.');
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeMaterialRaisingRequestStatus(Request $request)
    {
        try {
            $user = $request->user();
            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'status' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $materialRaisingRequest = ProjectMaterialRaisingRequest::whereId($request->id)
                    ->first();

                if (!isset($materialRaisingRequest) || empty($materialRaisingRequest)) {
                    return $this->sendError('Project material raising request does not exist.');
                }

                if (!in_array($request->status, ProjectMaterialRaisingRequest::STATUS)) {
                    return $this->sendError('Invalid status requested.');
                }

                if ($request->status === ProjectMaterialRaisingRequest::STATUS['Rejected']) {
                    if (!isset($request->reject_reasone) || empty($request->reject_reasone)) {
                        return $this->sendError('Validation Error.', ['reject_reasone' => 'The reject reasone field is required.']);
                    }

                    $materialRaisingRequest->reject_reasone = $request->reject_reasone;
                    $materialRaisingRequest->status = $request->status;
                    $materialRaisingRequest->save();
                } elseif ($request->status === ProjectMaterialRaisingRequest::STATUS['Approved']) {
                    $materialRaisingRequest->status = $request->status;
                    $materialRaisingRequest->reject_reasone = null;
                    $materialRaisingRequest->save();
                } else {
                    $materialRaisingRequest->status = $request->status;
                    $materialRaisingRequest->save();
                }

                return $this->sendResponse($materialRaisingRequest, 'Status changed successfully.');
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
