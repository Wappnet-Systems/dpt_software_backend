<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectRaisingInstructionRequest;
use App\Models\Tenant\RoleHasSubModule;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RaisingInstructionRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.', [], 401);
                }

                // if (!AppHelper::roleHasModulePermission('Qs', $user)) {
                //     return $this->sendError('You have no rights to access this module.', [], 401);
                // }

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

    public function getRaisingInstructionRequest(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Raising Site Instruction', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectRaisingInstructionRequest::whereProjectId($request->project_id ?? '')
            ->orderby('id', $orderBy);

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $raisingInstructionRequest = $query->cursorPaginate($limit)->toArray();
        } else {
            $raisingInstructionRequest['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($raisingInstructionRequest['data'])) {
            $results = $raisingInstructionRequest['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $raisingInstructionRequest['per_page'],
                'next_page_url' => ltrim(str_replace($raisingInstructionRequest['path'], "", $raisingInstructionRequest['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($raisingInstructionRequest['path'], "", $raisingInstructionRequest['prev_page_url']), "?cursor=")
            ], 'Project raising instruction request List.');
        } else {
            return $this->sendResponse($results, 'Project raising instruction request List.');
        }
    }

    public function getRaisingInstructionRequestDetails(Request $request, $id = null)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Raising Site Instruction', RoleHasSubModule::ACTIONS['view'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $raisingInstructionRequest = ProjectRaisingInstructionRequest::whereId($request->id)->first();

        if (!isset($raisingInstructionRequest) || empty($raisingInstructionRequest)) {
            return $this->sendError('Project raising instruction request does not exist.', [], 404);
        }

        return $this->sendResponse($raisingInstructionRequest, 'Project raising instruction request details.');
    }

    public function addRaisingInstructionRequest(Request $request)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Raising Site Instruction', RoleHasSubModule::ACTIONS['create'], $user)) {
            //     return $this->sendError('You have no rights to access this action.', [], 401);
            // }

            if (isset($user) && !empty($user)) {
                $validationRules = [
                    'project_id' => 'required|exists:projects,id'
                ];

                if (in_array($user->role_id, [User::USER_ROLE['QS_DEPARTMENT']])) {
                    $validationRules['reference_no'] = 'required';
                }

                if (in_array($user->role_id, [User::USER_ROLE['ENGINEER']])) {
                    $validationRules['message'] = 'required';
                }

                $validator = Validator::make($request->all(), $validationRules);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $raisingInstructionRequest = new ProjectRaisingInstructionRequest();
                $raisingInstructionRequest->project_id = $request->project_id;
                $raisingInstructionRequest->reference_no = $request->reference_no ?? null;
                $raisingInstructionRequest->message = $request->message ?? null;
                $raisingInstructionRequest->created_by = $user->id;
                $raisingInstructionRequest->created_ip = $request->ip();
                $raisingInstructionRequest->updated_ip = $request->ip();

                if (!$raisingInstructionRequest->save()) {
                    return $this->sendError('Something went wrong while creating the project raising instruction request.', [], 500);
                }

                return $this->sendResponse([], 'Project raising instruction request created successfully.');
            } else {
                return $this->sendError('User does not exist', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateRaisingInstructionRequest(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Raising Site Instruction', RoleHasSubModule::ACTIONS['edit'], $user)) {
            //     return $this->sendError('You have no rights to access this action.', [], 401);
            // }

            if (isset($user) && !empty($user)) {
                $validationRules = [];

                if (in_array($user->role_id, [User::USER_ROLE['QS_DEPARTMENT']])) {
                    $validationRules['reference_no'] = 'required';
                }

                if (in_array($user->role_id, [User::USER_ROLE['ENGINEER']])) {
                    $validationRules['message'] = 'required';
                }

                $validator = Validator::make($request->all(), $validationRules);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $raisingInstructionRequest = ProjectRaisingInstructionRequest::whereId($request->id)->first();

                if (!isset($raisingInstructionRequest) || empty($raisingInstructionRequest)) {
                    return $this->sendError('Project raising instruction request does not exist.', [], 404);
                }

                if ($request->filled('reference_no')) $raisingInstructionRequest->reference_no = $request->reference_no ?? null;
                if ($request->filled('message')) $raisingInstructionRequest->message =  $request->message ?? null;
                $raisingInstructionRequest->updated_ip = $request->ip();

                if (!$raisingInstructionRequest->save()) {
                    return $this->sendError('Something went wrong while updating the project raising instruction request.', [], 500);
                }

                return $this->sendResponse([], 'Project raising instruction request updated successfully.');
            } else {
                return $this->sendError('User does not exist', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function deleteRaisingInstructionRequest(Request $request)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Raising Site Instruction', RoleHasSubModule::ACTIONS['delete'], $user)) {
            //     return $this->sendError('You have no rights to access this action.', [], 401);
            // }

            if (isset($user) && !empty($user)) {
                $raisingInstructionRequest = ProjectRaisingInstructionRequest::whereId($request->id)->first();

                if (!isset($raisingInstructionRequest) || empty($raisingInstructionRequest)) {
                    return $this->sendError('Project raising instruction request does not exist.', [], 404);
                }

                if ($raisingInstructionRequest->status == ProjectRaisingInstructionRequest::STATUS['Approved']) {
                    return $this->sendError('You can not delete the project raising instruction request.', [], 400);
                }

                $raisingInstructionRequest->delete();

                return $this->sendResponse([], 'Project raising instruction request deleted Successfully.');
            } else {
                return $this->sendError('User does not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeRaisingInstructionRequestStatus(Request $request)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Raising Site Instruction', RoleHasSubModule::ACTIONS['approve_reject'], $user)) {
            //     return $this->sendError('You have no rights to access this action.', [], 401);
            // }

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'status' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                if (!in_array($request->status, ProjectRaisingInstructionRequest::STATUS)) {
                    return $this->sendError('Invalid status requested.', [], 400);
                }

                $raisingInstructionRequest = ProjectRaisingInstructionRequest::whereId($request->id)->first();

                if (!isset($raisingInstructionRequest) || empty($raisingInstructionRequest)) {
                    return $this->sendError('Project raising instruction request does not exist.', [], 404);
                }

                if ($request->status == ProjectRaisingInstructionRequest::STATUS['Rejected']) {
                    $validator = Validator::make($request->all(), [
                        'reason' => 'required'
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->messages() as $key => $value) {
                            return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                        }
                    }
                }

                if ($request->status == ProjectRaisingInstructionRequest::STATUS['Approved']) {
                    $raisingInstructionRequest->status = ProjectRaisingInstructionRequest::STATUS['Approved'];
                    $raisingInstructionRequest->reason = null;
                } elseif ($request->status == ProjectRaisingInstructionRequest::STATUS['Rejected']) {
                    $raisingInstructionRequest->status = ProjectRaisingInstructionRequest::STATUS['Rejected'];
                    $raisingInstructionRequest->reason = !empty($request->reason) ? $request->reason : null;
                }

                $raisingInstructionRequest->updated_ip = $request->ip();

                if (!$raisingInstructionRequest->save()) {
                    return $this->sendError('Something went wrong while updating the project raising instruction request status.', [], 500);
                }

                return $this->sendResponse($raisingInstructionRequest, 'Status changed successfully.');
            } else {
                return $this->sendError('User does not exist', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
