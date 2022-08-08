<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\MaterialApprovalLog;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MaterialApprovalLogController extends Controller
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

    public function getMaterialApprovalLog(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = MaterialApprovalLog::orderby('id', $orderBy);

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $materialApprovalLog = $query->cursorPaginate($limit)->toArray();
        } else {
            $materialApprovalLog['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($materialApprovalLog['data'])) {
            $results = $materialApprovalLog['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $materialApprovalLog['per_page'],
                'next_page_url' => ltrim(str_replace($materialApprovalLog['path'], "", $materialApprovalLog['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($materialApprovalLog['path'], "", $materialApprovalLog['prev_page_url']), "?cursor=")
            ], 'Project material approval log List.');
        } else {
            return $this->sendResponse($results, 'Project material approval log List.');
        }
    }

    public function getMaterialApprovalLogDetails(Request $request, $id = null)
    {
        $materialApprovalLog = MaterialApprovalLog::whereId($request->id)
            ->first();

        if (!isset($materialApprovalLog) || empty($materialApprovalLog)) {
            return $this->sendError('Project material approval log does not exist.');
        }

        return $this->sendResponse($materialApprovalLog, 'Project material approval log details.');
    }

    public function addMaterialApprovalLog(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    'reference_number' => 'required|unique:material_approval_logs,reference_number',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $materialApprovalLog = new MaterialApprovalLog();
                $materialApprovalLog->name = $request->name;
                $materialApprovalLog->reference_number = $request->reference_number;
                $materialApprovalLog->created_ip = $request->ip();
                $materialApprovalLog->updated_ip = $request->ip();

                if (!$materialApprovalLog->save()) {
                    return $this->sendError('Something went wrong while creating the project material approval log.');
                }

                return $this->sendResponse([], 'Project material approval log created successfully.');
            } else {
                return $this->sendError('User does not exist');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateMaterialApprovalLog(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    'reference_number' => 'required|unique:material_approval_logs,reference_number,' . $id,
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $materialApprovalLog = MaterialApprovalLog::whereId($request->id)
                    ->first();

                if (!isset($materialApprovalLog) || empty($materialApprovalLog)) {
                    return $this->sendError('Project material approval log does not exist.');
                }

                if ($request->filled('name')) $materialApprovalLog->name = $request->name;
                if ($request->filled('reference_number')) $materialApprovalLog->reference_number = $request->reference_number;
                $materialApprovalLog->updated_ip = $request->ip();

                if (!$materialApprovalLog->save()) {
                    return $this->sendError('Something went wrong while updating the project material approval log.');
                }

                return $this->sendResponse([], 'Project material approval log updated successfully.');
            } else {
                return $this->sendError('User does not exist');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeApprovalStatus(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'approval_status' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                if (!in_array($request->approval_status, MaterialApprovalLog::APPROVAL_STATUS)) {
                    return $this->sendError('Invalid approval status requested.');
                }

                $materialApprovalLog = MaterialApprovalLog::whereId($request->id)
                    ->first();

                if (!isset($materialApprovalLog) || empty($materialApprovalLog)) {
                    return $this->sendError('Project material approval log does not exist.');
                }

                if ($request->approval_status == MaterialApprovalLog::APPROVAL_STATUS['Rejected']) {
                    $validator = Validator::make($request->all(), [
                        'reason' => 'required'
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->messages() as $key => $value) {
                            return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                        }
                    }
                }

                if ($request->approval_status == MaterialApprovalLog::APPROVAL_STATUS['Approved']) {
                    $materialApprovalLog->approval_status = MaterialApprovalLog::APPROVAL_STATUS['Approved'];
                    $materialApprovalLog->reason = null;
                } elseif ($request->approval_status == MaterialApprovalLog::APPROVAL_STATUS['Rejected']) {
                    $materialApprovalLog->approval_status = MaterialApprovalLog::APPROVAL_STATUS['Rejected'];
                    $materialApprovalLog->reason = $request->reason;
                }

                $materialApprovalLog->updated_ip = $request->ip();
                $materialApprovalLog->save();

                return $this->sendResponse($materialApprovalLog, 'Status changed successfully.');
            } else {
                return $this->sendError('User does not exist');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeMaterialStatus(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'status' => 'required'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                if (!in_array($request->status, MaterialApprovalLog::STATUS)) {
                    return $this->sendError('Invalid status requested.', [], 400);
                }

                $materialApprovalLog = MaterialApprovalLog::whereId($request->id)
                    ->first();

                if (!isset($materialApprovalLog) || empty($materialApprovalLog)) {
                    return $this->sendError('Project material approval log does not exist.');
                }

                $materialApprovalLog->status = $request->status;
                $materialApprovalLog->updated_ip = $request->ip();
                $materialApprovalLog->save();

                if ($request->status == MaterialApprovalLog::STATUS['Deleted']) {
                    $materialApprovalLog->delete();
                }

                return $this->sendResponse($materialApprovalLog, 'Status changed successfully.');
            } else {
                return $this->sendError('User does not exist');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
