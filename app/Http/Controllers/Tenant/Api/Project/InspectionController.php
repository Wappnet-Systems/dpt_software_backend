<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectInspection;
use App\Helpers\AppHelper;
use App\Helpers\UploadFile;
use App\Jobs\SendPushJob;
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\ProjectActivityAllocateMaterial;
use App\Models\Tenant\RoleHasSubModule;
use Illuminate\Support\Facades\Log;

class InspectionController extends Controller
{
    protected $uploadFile;

    public function __construct()
    {
        $this->uploadFile = new UploadFile();

        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.', [], 401);
                }

                // if (!AppHelper::roleHasModulePermission('Qa/Qc', $user)) {
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

    public function getProjectInspection(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Inspection', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        try {
            $query = ProjectInspection::with('projectActivity')
                ->whereProjectId($request->project_id ?? '')
                ->orderBy('id', $orderBy);

            if (isset($request->inspection_status) && !empty($request->inspection_status)) {
                $query = $query->where('inspection_status', $request->inspection_status);
            }

            if (isset($request->project_activity_id) && !empty($request->project_activity_id)) {
                $query = $query->WhereHas('projectActivity', function ($query) use ($request) {
                    $query->whereId($request->project_activity_id);
                });
            }

            $totalQuery = $query;
            $totalQuery = $totalQuery->count();

            if ($request->exists('cursor')) {
                $projectInspection = $query->cursorPaginate($limit)->toArray();
            } else {
                $projectInspection['data'] = $query->get()->toArray();
            }

            $results = [];
            if (!empty($projectInspection['data'])) {
                $results = $projectInspection['data'];
            }

            if ($request->exists('cursor')) {
                return $this->sendResponse([
                    'lists' => $results,
                    'total' => $totalQuery,
                    'per_page' => $projectInspection['per_page'],
                    'next_page_url' => ltrim(str_replace($projectInspection['path'], "", $projectInspection['next_page_url']), "?cursor="),
                    'prev_page_url' => ltrim(str_replace($projectInspection['path'], "", $projectInspection['prev_page_url']), "?cursor=")
                ], 'Project inspection List.');
            } else {
                return $this->sendResponse($results, 'Project inspection List.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function getProjectInspectionDetails(Request $request, $id = null)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Inspection', RoleHasSubModule::ACTIONS['view'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $projectInspection = ProjectInspection::with('projectActivity')
            ->whereId($request->id)
            ->first();

        if (!isset($projectInspection) || empty($projectInspection)) {
            return $this->sendError('Project inspection does not exists.', [], 404);
        }

        return $this->sendResponse($projectInspection, 'Project inspection details.');
    }

    public function addProjectInspection(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Inspection', RoleHasSubModule::ACTIONS['create'], $user)) {
                //     return $this->sendError('You have no rights to access this action.', [], 401);
                // }

                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'project_activity_id' => 'required|exists:projects_activities,id',
                    'inspection_no' => 'required|unique:projects_inspections,inspection_no',
                    'inspection_date' => 'required|date_format:Y-m-d',
                    'location' => 'required',
                    'document' => 'mimes:pdf|max:10240',
                    'inspection_type' => 'required',
                ], [
                    'document.max' => 'The file must not be greater than 10mb.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                if (isset($request->project_allocate_material_id) && !empty($request->project_allocate_material_id)) {
                    if (!ProjectActivityAllocateMaterial::whereId($request->project_allocate_material_id)->exists()) {
                        return $this->sendError('Allocated material does not exists.', [], 404);
                    }
                }

                if (!in_array($request->inspection_type, ProjectInspection::INC_TYPE)) {
                    return $this->sendError('Invalid inspection type requested.', [], 400);
                }

                if (!in_array($request->type, ProjectInspection::TYPE)) {
                    return $this->sendError('Invalid type requested.', [], 400);
                }

                $projectInspection = new ProjectInspection();
                $projectInspection->project_id = $request->project_id;
                $projectInspection->project_activity_id = $request->project_activity_id;
                $projectInspection->project_allocate_material_id = $request->project_allocate_material_id ?? null;
                $projectInspection->inspection_no = $request->inspection_no;
                $projectInspection->inspection_date = date('Y-m-d', strtotime($request->inspection_date));
                $projectInspection->location = $request->location;
                $projectInspection->inspection_type = $request->inspection_type;
                $projectInspection->type = !empty($request->type) ? $request->type : ProjectInspection::TYPE['Activity'];

                if ($request->hasFile('document')) {
                    $dirPath = str_replace([':uid:'], [$user->organization_id], config('constants.organizations.projects.inspection.document_path'));

                    $projectInspection->document = $this->uploadFile->uploadFileInS3($request, $dirPath, 'document');
                }

                $projectInspection->created_by = $user->id;
                $projectInspection->updated_by = $user->id;
                $projectInspection->created_ip = $request->ip();
                $projectInspection->updated_ip = $request->ip();

                if (!$projectInspection->save()) {
                    return $this->sendError('Something went wrong while creating the project inspection.', [], 500);
                }

                return $this->sendResponse([], 'Project inspection created successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateProjectInspection(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Inspection', RoleHasSubModule::ACTIONS['edit'], $user)) {
                //     return $this->sendError('You have no rights to access this action.', [], 401);
                // }

                $validator = Validator::make($request->all(), [
                    'inspection_no' => 'required|unique:projects_inspections,inspection_no',
                    'inspection_date' => 'required|date_format:Y-m-d',
                    'location' => 'required',
                    'document' => 'mimes:pdf|max:10240',
                    'inspection_type' => 'required',
                ], [
                    'document.max' => 'The file must not be greater than 10mb.'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                if (isset($request->project_allocate_material_id) && !empty($request->project_allocate_material_id)) {
                    if (!ProjectActivityAllocateMaterial::whereId($request->project_allocate_material_id)->exists()) {
                        return $this->sendError('Allocated material does not exists.', [], 404);
                    }
                }

                $projectInspection = ProjectInspection::whereId($request->id)
                    ->where('inspection_status', ProjectInspection::INC_STATUS['Pending'])
                    ->first();

                if (!isset($projectInspection) || empty($projectInspection)) {
                    return $this->sendError('Project inspection does not exists.', [], 404);
                }

                if (!in_array($request->inspection_type, ProjectInspection::INC_TYPE)) {
                    return $this->sendError('Invalid inspection type requested.', [], 400);
                }

                if (!in_array($request->type, ProjectInspection::TYPE)) {
                    return $this->sendError('Invalid type requested.', [], 400);
                }

                if ($projectInspection->project_activity_id != null) {

                    AppHelper::setDefaultDBConnection(true);

                    $assignUsers = User::select('id', 'role_id', 'name', 'created_by')
                        ->whereIn('role_id', [User::USER_ROLE['QA/QC']])
                        ->get()->toArray();

                    foreach ($assignUsers as $assignUserKey => $assignUserValue) {
                        AppHelper::setDefaultDBConnection();

                        $activityVal = ProjectActivity::whereId($projectInspection->project_activity_id)
                            ->value('name');

                        /** Send Push Notification */
                        $title = 'Activity Inspection Updated';
                        $message = $activityVal . ', inspection has been updated by ' . $assignUserValue['name'];
                        $data = [
                            'type' => 'Activity Inspection Updated',
                            'data' => $assignUserValue
                        ];
                        dispatch(new SendPushJob($assignUserValue, $title, $message, $data));
                        /** End of Send Push Notification */
                    }
                }

                if ($request->filled('project_allocate_material_id')) $projectInspection->project_allocate_material_id = $request->project_allocate_material_id ?? null;
                if ($request->filled('inspection_no')) $projectInspection->inspection_no = $request->inspection_no;
                if ($request->filled('inspection_date')) $projectInspection->inspection_date = date('Y-m-d', strtotime($request->inspection_date));
                if ($request->filled('location')) $projectInspection->location = $request->location;
                if ($request->filled('inspection_type')) $projectInspection->inspection_type = $request->inspection_type;
                if ($request->filled('type')) $projectInspection->type = !empty($request->type) ? $request->type : ProjectInspection::TYPE['Activity'];

                if ($request->hasFile('document')) {
                    if (isset($projectInspection->document) && !empty($projectInspection->document)) {
                        $this->uploadFile->deleteFileFromS3($projectInspection->document);
                    }

                    $dirPath = str_replace([':uid:'], [$user->organization_id], config('constants.organizations.projects.inspection.document_path'));

                    $projectInspection->document = $this->uploadFile->uploadFileInS3($request, $dirPath, 'document');
                }

                $projectInspection->updated_by = $user->id;
                $projectInspection->updated_ip = $request->ip();

                if (!$projectInspection->save()) {
                    return $this->sendError('Something went wrong while updating the project inspection', [], 500);
                }

                return $this->sendResponse([], 'Project inspection updated successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function deleteProjectInspection(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Inspection', RoleHasSubModule::ACTIONS['delete'], $user)) {
                //     return $this->sendError('You have no rights to access this action.', [], 401);
                // }

                $projectInspection = ProjectInspection::whereId($request->id)
                    ->where('inspection_status', ProjectInspection::INC_STATUS['Pending'])
                    ->first();

                if (!isset($projectInspection) || empty($projectInspection)) {
                    return $this->sendError('Project inspection does not exists.', [], 404);
                }

                $projectInspection->delete();

                return $this->sendResponse([], 'Project inspection deleted Successfully.');
            } else {
                return $this->sendError('User does not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function projectInspectionChangeStatus(Request $request, $id = null)
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

                if (!in_array($request->status, ProjectInspection::STATUS)) {
                    return $this->sendError('Invalid status requested.', [], 400);
                }

                $projectInspection = ProjectInspection::whereId($request->id)
                    ->where('inspection_status', ProjectInspection::INC_STATUS['Pending'])
                    ->first();

                if (!isset($projectInspection) || empty($projectInspection)) {
                    return $this->sendError('Project inspection does not exists.', [], 404);
                }

                $projectInspection->status = $request->status;
                $projectInspection->updated_by = $user->id;
                $projectInspection->updated_ip = $request->ip();
                $projectInspection->save();

                return $this->sendResponse($projectInspection, 'Status changed successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function projectInspectionApproveReject(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Inspection', RoleHasSubModule::ACTIONS['approve_reject'], $user)) {
                //     return $this->sendError('You have no rights to access this action.', [], 401);
                // }

                $validator = Validator::make($request->all(), [
                    'inspection_status' => 'required'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                if (!in_array($request->inspection_status, ProjectInspection::INC_STATUS)) {
                    return $this->sendError('Invalid status requested.', [], 400);
                }

                $projectInspection = ProjectInspection::whereId($request->id)
                    ->where('inspection_status', ProjectInspection::INC_STATUS['Pending'])
                    ->first();

                if (!isset($projectInspection) || empty($projectInspection)) {
                    return $this->sendError('Project inspection does not exists.', [], 404);
                }

                if ($request->inspection_status == ProjectInspection::INC_STATUS['Rejected']) {
                    $validator = Validator::make($request->all(), [
                        'reason' => 'required'
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->messages() as $key => $value) {
                            return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                        }
                    }
                }

                if ($request->inspection_status == ProjectInspection::INC_STATUS['Approved']) {
                    $projectInspection->inspection_status = ProjectInspection::INC_STATUS['Approved'];
                } elseif ($request->inspection_status == ProjectInspection::INC_STATUS['Rejected']) {
                    $projectInspection->inspection_status = ProjectInspection::INC_STATUS['Rejected'];
                    $projectInspection->reason = $request->reason;
                }

                $projectInspection->approve_reject_date = date('Y-m-d');
                $projectInspection->updated_by = $user->id;
                $projectInspection->updated_ip = $request->ip();
                $projectInspection->save();

                return $this->sendResponse($projectInspection, 'Status changed successfully.');
            } else {
                return $this->sendError('User does not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function addComments(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $projectInspection = ProjectInspection::whereId($request->id)
                    ->where('inspection_status', ProjectInspection::INC_STATUS['Pending'])
                    ->first();

                if (!isset($projectInspection) || empty($projectInspection)) {
                    return $this->sendError('Project inspection does not exists.', [], 404);
                }
                
                $projectInspection->comments = $request->comments;
                $projectInspection->updated_by = $user->id;
                $projectInspection->updated_ip = $request->ip();
                $projectInspection->save();

                return $this->sendResponse($projectInspection, 'Comment added to inspection successfully.');
            } else {
                return $this->sendError('User does not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
