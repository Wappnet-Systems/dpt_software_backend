<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectActivityDocument;
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\RoleHasSubModule;
use App\Helpers\AppHelper;
use App\Helpers\UploadFile;

class ActivityDocumentController extends Controller
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

    public function getActivityDocument(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Upload Drawings', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.');
        // }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectActivityDocument::with('projectActivity')
            ->whereProjectId($request->project_id ?? '')
            ->whereStatus(ProjectActivityDocument::STATUS['Active'])
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
            $projectActivityDocument = $query->cursorPaginate($limit)->toArray();
        } else {
            $projectActivityDocument['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($projectActivityDocument['data'])) {
            $results = $projectActivityDocument['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $projectActivityDocument['per_page'],
                'next_page_url' => ltrim(str_replace($projectActivityDocument['path'], "", $projectActivityDocument['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($projectActivityDocument['path'], "", $projectActivityDocument['prev_page_url']), "?cursor=")
            ], 'Project activity document list');
        } else {
            return $this->sendResponse($results, 'Project activity document list');
        }
    }

    public function getActivityDocumentDetails(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Upload Drawings', RoleHasSubModule::ACTIONS['view'], $user)) {
        //     return $this->sendError('You have no rights to access this action.');
        // }

        $projectActivityDocument = ProjectActivityDocument::with('projectActivity')
            ->select('id', 'project_id', 'name', 'path', 'location', 'area', 'file_type', 'type', 'status')
            ->whereId($request->id)
            ->first();

        if (!isset($projectActivityDocument) || empty($projectActivityDocument)) {
            return $this->sendError('Project activity document does not exist.');
        }

        return $this->sendResponse($projectActivityDocument, 'Project activity document details.');
    }

    public function addActivityDocument(Request $request)
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

                $projectActivityDocument = new ProjectActivityDocument();
                $projectActivityDocument->project_id = $request->project_id;
                $projectActivityDocument->name = $request->name;
                $projectActivityDocument->location = $request->location;
                $projectActivityDocument->area = $request->area;
                $projectActivityDocument->type = $request->type;
                $projectActivityDocument->created_by = $user->id;
                $projectActivityDocument->created_ip = $request->ip();
                $projectActivityDocument->updated_ip = $request->ip();

                if ($request->hasFile('path')) {
                    $dirPath = str_replace([':uid:', ':project_uuid:'], [$user->organization_id, $request->project_id], config('constants.organizations.projects.activity_document.file_path'));

                    $projectActivityDocument->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');

                    if ($request->file('path')->getClientOriginalExtension() === 'pdf') {
                        $projectActivityDocument->file_type = ProjectActivityDocument::FILE_TYPE['PDF'];
                    } else {
                        $projectActivityDocument->file_type = ProjectActivityDocument::FILE_TYPE['Image'];
                    }
                }

                if (!$projectActivityDocument->save()) {
                    return $this->sendError('Something went wrong while creating the project activity document.');
                }

                return $this->sendResponse($projectActivityDocument, 'Project activity document created successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateActivityDocument(Request $request, $id = null)
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

                $projectActivityDocument = ProjectActivityDocument::whereId($request->id)
                    ->first();

                if (!isset($projectActivityDocument) || empty($projectActivityDocument)) {
                    return $this->sendError('Project activity document does not exist.');
                }

                if ($request->filled('name')) $projectActivityDocument->name = $request->name;
                if ($request->filled('location')) $projectActivityDocument->location = $request->location;
                if ($request->filled('area')) $projectActivityDocument->area = $request->area;
                if ($request->filled('type')) $projectActivityDocument->type = $request->type;

                if ($request->hasFile('path')) {
                    if (isset($projectActivityDocument->path) && !empty($projectActivityDocument->path)) {
                        $this->uploadFile->deleteFileFromS3($projectActivityDocument->path);
                    }

                    $dirPath = str_replace([':uid:', ':project_uuid:'], [$user->organization_id, $request->project_id], config('constants.organizations.projects.activity_document.file_path'));

                    $projectActivityDocument->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');

                    if ($request->file('path')->getClientOriginalExtension() === 'pdf') {
                        $projectActivityDocument->file_type = ProjectActivityDocument::FILE_TYPE['PDF'];
                    } else {
                        $projectActivityDocument->file_type = ProjectActivityDocument::FILE_TYPE['Image'];
                    }
                }
                $projectActivityDocument->updated_ip = $request->ip();

                if (!$projectActivityDocument->save()) {
                    return $this->sendError('Something went wrong while updating the project activity document.');
                }

                return $this->sendResponse($projectActivityDocument, 'Project activity document updated successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeActivityDocumentStatus(Request $request, $id = null)
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

                if (!in_array($request->status, ProjectActivityDocument::STATUS)) {
                    return $this->sendError('Invalid status requested.');
                }

                $projectActivityDocument = ProjectActivityDocument::whereId($request->id)
                    ->first();

                if (!isset($projectActivityDocument) || empty($projectActivityDocument)) {
                    return $this->sendError('Project activity document does not exist.');
                }

                if ($request->status == ProjectActivityDocument::STATUS['Deleted']) {
                    // if (!AppHelper::roleHasSubModulePermission('Upload Drawings', RoleHasSubModule::ACTIONS['delete'], $user)) {
                    //     return $this->sendError('You have no rights to access this action.');
                    // }

                    $projectActivityDocument = ProjectActivityDocument::whereId($request->id)
                        ->whereNull('project_activity_id')
                        ->first();

                    if (!isset($projectActivityDocument) || empty($projectActivityDocument)) {
                        return $this->sendError('You can not delete assigned project activity document.');
                    }

                    $projectActivityDocument->delete();
                } else {
                    $projectActivityDocument->deleted_at = null;
                    $projectActivityDocument->status = $request->status;
                    $projectActivityDocument->save();
                }

                return $this->sendResponse($projectActivityDocument, 'Status changed successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function assignActivityDocument(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $projectActivityDocument = ProjectActivityDocument::whereId($request->id)
                    ->first();

                if (!isset($projectActivityDocument) || empty($projectActivityDocument)) {
                    return $this->sendError('Project activity document does not exist.');
                }

                $projectActivityDocument->project_activity_id = !empty($request->project_activity_id) ? $request->project_activity_id : NULL;

                if (!$projectActivityDocument->save()) {
                    return $this->sendError('Something went wrong while updating the project activity document.');
                }

                return $this->sendResponse($projectActivityDocument, 'Project activity document updated successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
