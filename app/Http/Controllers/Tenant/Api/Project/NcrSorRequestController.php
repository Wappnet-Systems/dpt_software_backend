<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectNcrSorRequest;
use App\Helpers\AppHelper;
use App\Helpers\UploadFile;
use App\Models\Tenant\RoleHasSubModule;

class NcrSorRequestController extends Controller
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

                if (!AppHelper::roleHasModulePermission('Qa/Qc', $user)) {
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

    public function getNcrSorRequest(Request $request)
    {
        $user = $request->user();

        if (!AppHelper::roleHasSubModulePermission('NCR/SOR Request', RoleHasSubModule::ACTIONS['list'], $user)) {
            return $this->sendError('You have no rights to access this action.');
        }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectNcrSorRequest::with('projectActivity')
            ->whereProjectActivityId($request->project_activity_id ?? '')
            ->orderby('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->orWhereHas('project', function ($query) use ($search) {
                $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
            });
        }

        if (isset($request->type) && !empty($request->type)) {
            $query = $query->whereType($request->type);
        }

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $projectNcrSorRequest = $query->cursorPaginate($limit)->toArray();
        } else {
            $projectNcrSorRequest['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($projectNcrSorRequest['data'])) {
            $results = $projectNcrSorRequest['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $projectNcrSorRequest['per_page'],
                'next_page_url' => ltrim(str_replace($projectNcrSorRequest['path'], "", $projectNcrSorRequest['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($projectNcrSorRequest['path'], "", $projectNcrSorRequest['prev_page_url']), "?cursor=")
            ], 'Project ncr/sor request list.');
        } else {
            return $this->sendResponse($results, 'Project ncr/sor request list.');
        }
    }

    public function getNcrSorRequestDetails(Request $request)
    {
        $user = $request->user();

        if (!AppHelper::roleHasSubModulePermission('NCR/SOR Request', RoleHasSubModule::ACTIONS['view'], $user)) {
            return $this->sendError('You have no rights to access this action.');
        }

        $projectNcrSorRequest = ProjectNcrSorRequest::with('projectActivity')
            ->select('id', 'project_id', 'project_activity_id', 'path', 'type', 'status')
            ->whereId($request->id)
            ->first();

        if (!isset($projectNcrSorRequest) || empty($projectNcrSorRequest)) {
            return $this->sendError('Project ncr/sor request does not exist.');
        }

        return $this->sendResponse($projectNcrSorRequest, 'Project ncr/sor request details.');
    }

    public function addNcrSorRequest(Request $request)
    {
        try {
            $user = $request->user();

            if (!AppHelper::roleHasSubModulePermission('NCR/SOR Request', RoleHasSubModule::ACTIONS['create'], $user)) {
                return $this->sendError('You have no rights to access this action.');
            }

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required',
                    'project_activity_id' => 'required',
                    'type' => 'required',
                    // 'path' => sprintf('required|mimes:%s|max:%s', 'doc', config('constants.organizations.projects.ncrsor_request_document.upload_image_max_size'))
                ], [
                    'path.max' => 'The file must not be greater than 8mb.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }
                
                $projectNcrSorRequest = new ProjectNcrSorRequest();
                $projectNcrSorRequest->project_id = $request->project_id;
                $projectNcrSorRequest->project_activity_id = $request->project_activity_id;
                $projectNcrSorRequest->type = $request->type;
                $projectNcrSorRequest->created_by = $user->id;
                $projectNcrSorRequest->created_ip = $request->ip();
                $projectNcrSorRequest->updated_ip = $request->ip();
                if ($request->hasFile('path')) {
                    $ext = "doc";
                    $newname = time().".".$ext; 
                    $dirPath = str_replace([':uid:', ':project_uuid:'], [$user->organization_id, $request->project_id], config('constants.organizations.projects.ncrsor_request_document.file_path'));
                    // return $this->sendResponse($request->file());
                    $projectNcrSorRequest->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');
                }
                // return $this->sendResponse($_FILES);
                
                if (!$projectNcrSorRequest->save()) {
                    return $this->sendError('Something went wrong while creating the project ncr/sor request.');
                }

                return $this->sendResponse([], 'Project ncr/sor request created successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateNcrSorRequest(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (!AppHelper::roleHasSubModulePermission('NCR/SOR Request', RoleHasSubModule::ACTIONS['edit'], $user)) {
                return $this->sendError('You have no rights to access this action.');
            }

            if (isset($user) && !empty($user)) {
                // $validator = Validator::make($request->all(), [
                //     'path' => sprintf('mimes:%s|max:%s', 'doc', config('constants.organizations.projects.ncrsor_request_document.upload_image_max_size'))
                // ], [
                //     'path.max' => 'The file must not be greater than 8mb.',
                // ]);

                // if ($validator->fails()) {
                //     foreach ($validator->errors()->messages() as $key => $value) {
                //         return $this->sendError('Validation Error.', [$key => $value[0]]);
                //     }
                // }

                $projectNcrSorRequest = ProjectNcrSorRequest::whereId($request->id)
                    ->first();

                if (!isset($projectNcrSorRequest) || empty($projectNcrSorRequest)) {
                    return $this->sendError('Project ncr/sor request does not exist.');
                }


                if ($projectNcrSorRequest->status != 1) {
                    return $this->sendError('Unable to update project ncr/sor request.');
                }
                $oldPath = "";
                if ($request->hasFile('path')) {
                    if (isset($projectNcrSorRequest->path) && !empty($projectNcrSorRequest->path)) {
                        $oldPath = $projectNcrSorRequest->path;
                    }
                    
                    $dirPath = str_replace([':uid:', ':project_uuid:'], [$user->organization_id, $projectNcrSorRequest->project_id], config('constants.organizations.projects.ncrsor_request_document.file_path'));
                    
                    $projectNcrSorRequest->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');
                }
                
                $projectNcrSorRequest->updated_ip = $request->ip();
                
                if (!$projectNcrSorRequest->save()) {
                    return $this->sendError('Something went wrong while updating the project ncr/sor request.');
                }
                if($oldPath){
                    $this->uploadFile->deleteFileFromS3($oldPath);
                }
                return $this->sendResponse([], 'Project ncr/sor request updated successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeNcrSorRequestStatus(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'status' => 'required'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                if (!in_array($request->status, ProjectNcrSorRequest::STATUS)) {
                    return $this->sendError('Invalid status requested.');
                }

                $projectNcrSorRequest = ProjectNcrSorRequest::whereId($request->id)
                    ->first();

                if (!isset($projectNcrSorRequest) || empty($projectNcrSorRequest)) {
                    return $this->sendError('Project ncr/sor request does not exist.');
                }
                $projectNcrSorRequest->reject_reasone = null;
                if($request->status == 3){
                    $projectNcrSorRequest->reject_reasone = $request->reject_reasone;
                }

                $projectNcrSorRequest->status = $request->status;
                $projectNcrSorRequest->save();

                return $this->sendResponse($projectNcrSorRequest, 'Status changed successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function deleteNcrSorRequest(Request $request, $id = null)
    {
        $user = $request->user();

        if (!AppHelper::roleHasSubModulePermission('NCR/SOR Request', RoleHasSubModule::ACTIONS['delete'], $user)) {
            return $this->sendError('You have no rights to access this action.');
        }

        $projectNcrSorRequest = ProjectNcrSorRequest::with('projectActivity')
            ->select('id', 'project_id', 'project_activity_id', 'path', 'type', 'status')
            ->whereId($request->id)
            ->first();

        if (!isset($projectNcrSorRequest) || empty($projectNcrSorRequest)) {
            return $this->sendError('Project ncr/sor request does not exist.');
        }

        $projectNcrSorRequest->delete();
        return $this->sendResponse([], 'Project ncr/sor request deleted Successfully.');
    }
}
