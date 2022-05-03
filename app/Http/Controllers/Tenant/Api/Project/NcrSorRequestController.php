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
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\RoleHasSubModule;
use App\Helpers\AppHelper;
use App\Helpers\UploadFile;

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

                // if (!AppHelper::roleHasModulePermission('Design Team', $user)) {
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

    public function getNcrSorRequest(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Upload Drawings', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.');
        // }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectNcrSorRequest::with('projectActivity')
            ->whereProjectId($request->project_id ?? '')
            ->orderby('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);

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
            ], 'Project Ncr/Sor Request list');
        } else {
            return $this->sendResponse($results, 'Project Ncr/Sor Request list');
        }
    }

    public function getNcrSorRequestDetails(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Upload Drawings', RoleHasSubModule::ACTIONS['view'], $user)) {
        //     return $this->sendError('You have no rights to access this action.');
        // }

        $projectNcrSorRequest = ProjectNcrSorRequest::with('projectActivity')
            ->select('id', 'project_id', 'name', 'path', 'location', 'area', 'file_type', 'type', 'status')
            ->whereId($request->id)
            ->first();

        if (!isset($projectNcrSorRequest) || empty($projectNcrSorRequest)) {
            return $this->sendError('Project Ncr/Sor Request does not exist.');
        }

        return $this->sendResponse($projectNcrSorRequest, 'Project Ncr/Sor Request details.');
    }

    public function addNcrSorRequest(Request $request)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Upload Drawings', RoleHasSubModule::ACTIONS['create'], $user)) {
            //     return $this->sendError('You have no rights to access this action.');
            // }

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'name' => 'required',
                    'path' => sprintf('required|mimes:%s|max:%s', 'pdf,jpeg,jpg,bmp,png', config('constants.organizations.projects.activity_document.upload_image_max_size')),
                    'location' => 'required',
                    'area' => 'required',
                    'type' => 'required',
                ], [
                    'path.max' => 'The file must not be greater than 10mb.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $projectNcrSorRequest = new ProjectNcrSorRequest();
                $projectNcrSorRequest->project_id = $request->project_id;
                $projectNcrSorRequest->name = $request->name;
                $projectNcrSorRequest->location = $request->location;
                $projectNcrSorRequest->area = $request->area;
                $projectNcrSorRequest->type = $request->type;
                $projectNcrSorRequest->created_by = $user->id;
                $projectNcrSorRequest->created_ip = $request->ip();
                $projectNcrSorRequest->updated_ip = $request->ip();

                if ($request->hasFile('path')) {
                    $dirPath = str_replace([':uid:', ':project_uuid:'], [$user->organization_id, $request->project_id], config('constants.organizations.projects.activity_document.file_path'));

                    $projectNcrSorRequest->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');

                }

                if (!$projectNcrSorRequest->save()) {
                    return $this->sendError('Something went wrong while creating the project activity document.');
                }

                return $this->sendResponse($projectNcrSorRequest, 'Project Ncr/Sor Request created successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateNcrSorRequest(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Upload Drawings', RoleHasSubModule::ACTIONS['edit'], $user)) {
            //     return $this->sendError('You have no rights to access this action.');
            // }

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    'path' => sprintf('mimes:%s|max:%s', 'pdf,jpeg,jpg,bmp,png', config('constants.organizations.projects.activity_document.upload_image_max_size')),
                    'location' => 'required',
                    'area' => 'required',
                    'type' => 'required',
                ], [
                    'path.max' => 'The file must not be greater than 10mb.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $projectNcrSorRequest = ProjectNcrSorRequest::whereId($request->id)
                    ->first();

                if (!isset($projectNcrSorRequest) || empty($projectNcrSorRequest)) {
                    return $this->sendError('Project Ncr/Sor Request does not exist.');
                }

                if ($request->filled('name')) $projectNcrSorRequest->name = $request->name;
                if ($request->filled('location')) $projectNcrSorRequest->location = $request->location;
                if ($request->filled('area')) $projectNcrSorRequest->area = $request->area;
                if ($request->filled('type')) $projectNcrSorRequest->type = $request->type;

                if ($request->hasFile('path')) {
                    if (isset($projectNcrSorRequest->path) && !empty($projectNcrSorRequest->path)) {
                        $this->uploadFile->deleteFileFromS3($projectNcrSorRequest->path);
                    }

                    $dirPath = str_replace([':uid:', ':project_uuid:'], [$user->organization_id, $request->project_id], config('constants.organizations.projects.activity_document.file_path'));

                    $projectNcrSorRequest->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');

                }
                $projectNcrSorRequest->updated_ip = $request->ip();

                if (!$projectNcrSorRequest->save()) {
                    return $this->sendError('Something went wrong while updating the project activity document.');
                }

                return $this->sendResponse($projectNcrSorRequest, 'Project Ncr/Sor Request updated successfully.');
            } else {
                return $this->sendError('User not exists.');
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
                    return $this->sendError('Project Ncr/Sor Request does not exist.');
                }

                if ($request->status == ProjectNcrSorRequest::STATUS['Deleted']) {
                    // if (!AppHelper::roleHasSubModulePermission('Upload Drawings', RoleHasSubModule::ACTIONS['delete'], $user)) {
                    //     return $this->sendError('You have no rights to access this action.');
                    // }

                    $projectNcrSorRequest = ProjectNcrSorRequest::whereId($request->id)
                        ->whereNull('project_activity_id')
                        ->first();

                    if (!isset($projectNcrSorRequest) || empty($projectNcrSorRequest)) {
                        return $this->sendError('You can not delete assigned project activity document.');
                    }

                    $projectNcrSorRequest->delete();
                } else {
                    $projectNcrSorRequest->deleted_at = null;
                    $projectNcrSorRequest->status = $request->status;
                    $projectNcrSorRequest->save();
                }

                return $this->sendResponse($projectNcrSorRequest, 'Status changed successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

}
