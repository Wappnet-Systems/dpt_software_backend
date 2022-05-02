<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectAssignedUser;
use App\Helpers\AppHelper;
use App\Helpers\UploadFile;

class ProjectsController extends Controller
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

    public function getProjects(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $user = $request->user();

        try {
            if (isset($user) && !empty($user)) {
                $query = Project::orderBy('id', $orderBy);

                if ($user->role_id != User::USER_ROLE['COMPANY_ADMIN']) {
                    $assignedProIds = ProjectAssignedUser::whereUserId($user->id ?? null)->pluck('project_id');

                    if (isset($assignedProIds) && !empty($assignedProIds)) {
                        $query->whereIn('id', $assignedProIds);
                    }
                }

                if (isset($request->search) && !empty($request->search)) {
                    $search = trim(strtolower($request->search));

                    $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
                }

                $totalQuery = $query;
                $totalQuery = $totalQuery->count();

                if ($request->exists('cursor')) {
                    $projects = $query->cursorPaginate($limit)->toArray();
                } else {
                    $projects['data'] = $query->get()->toArray();
                }

                $results = [];
                if (!empty($projects['data'])) {
                    $results = $projects['data'];
                }

                if ($request->exists('cursor')) {
                    return $this->sendResponse([
                        'lists' => $results,
                        'total' => $totalQuery,
                        'per_page' => $projects['per_page'],
                        'next_page_url' => ltrim(str_replace($projects['path'], "", $projects['next_page_url']), "?cursor="),
                        'prev_page_url' => ltrim(str_replace($projects['path'], "", $projects['prev_page_url']), "?cursor=")
                    ], 'Projects List.');
                } else {
                    return $this->sendResponse($results, 'Projects List');
                }
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function getProjectDetails(Request $request)
    {
        $project = Project::select('id', 'uuid', 'name', 'logo', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'start_date', 'end_date', 'cost', 'status', 'created_by')
            ->whereUuid($request->id)
            ->first();

        if (!isset($project) || empty($project)) {
            return $this->sendError('Project does not exists.');
        }

        return $this->sendResponse($project, 'Project details.');
    }

    public function addProject(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if (!in_array($user->role_id, [User::USER_ROLE['COMPANY_ADMIN']])) {
                    return $this->sendError('You have no rights to add project.', [], 401);
                } else {
                    $validator = Validator::make($request->all(), [
                        'name' => 'required',
                        'logo' => sprintf('mimes:%s|max:%s', config('constants.upload_image_types'), config('constants.upload_image_max_size')),
                        'address' => 'required',
                        'start_date' => 'required|date',
                        'end_date' => 'required|date',
                        'cost' => 'required',
                    ], [
                        'logo.max' => 'The logo must not be greater than 8mb.',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->messages() as $key => $value) {
                            return $this->sendError('Validation Error.', [$key => $value[0]]);
                        }
                    }

                    $project = new Project();
                    $project->name = $request->name;
                    $project->uuid = AppHelper::generateUuid();
                    $project->address = !empty($request->address) ? $request->address : NULL;
                    $project->lat = !empty($request->lat) ? $request->lat : NULL;
                    $project->long = !empty($request->long) ? $request->long : NULL;
                    $project->city = !empty($request->city) ? $request->city : NULL;
                    $project->state = !empty($request->state) ? $request->state : NULL;
                    $project->country = !empty($request->country) ? $request->country : NULL;
                    $project->zip_code = !empty($request->zip_code) ? $request->zip_code : NULL;
                    $project->start_date = !empty($request->start_date) ? date('Y-m-d', strtotime($request->start_date)) : NULL;
                    $project->end_date = !empty($request->end_date) ? date('Y-m-d', strtotime($request->end_date)) : NULL;
                    $project->cost = !empty($request->cost) ? $request->cost : NULL;
                    $project->created_by = $user->id;
                    $project->created_ip = $request->ip();
                    $project->updated_ip = $request->ip();

                    if (!$project->save()) {
                        return $this->sendError('Something went wrong while creating the project.');
                    }

                    if ($request->hasFile('logo')) {
                        $dirPath = str_replace([':uid:', ':project_uuid:'], [$user->organization_id, $project->id], config('constants.organizations.projects.logo_path'));

                        $project->logo = $this->uploadFile->uploadFileInS3($request, $dirPath, 'logo'/* , "100", "100" */);

                        $project->save();
                    }

                    return $this->sendResponse($project, 'Project created successfully.');
                }
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateProject(Request $request, $Uuid = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $project = Project::whereUuid($request->Uuid)->first();

                if (!isset($project) || empty($project)) {
                    return $this->sendError('Project dose not exists.');
                } else if (!in_array($user->role_id, [User::USER_ROLE['COMPANY_ADMIN']])) {
                    return $this->sendError('You have no rights to update project.', [], 401);
                } else {
                    $validator = Validator::make($request->all(), [
                        'name' => 'required',
                        'logo' => sprintf('mimes:%s|max:%s', config('constants.upload_image_types'), config('constants.upload_image_max_size')),
                        'address' => 'required',
                        'start_date' => 'required|date',
                        'end_date' => 'required|date',
                        'cost' => 'required',
                    ], [
                        'logo.max' => 'The logo must not be greater than 8mb.',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->messages() as $key => $value) {
                            return $this->sendError('Validation Error.', [$key => $value[0]]);
                        }
                    }

                    if ($request->filled('name')) $project->name = $request->name;

                    if ($request->filled('address')) {
                        $project->address = $request->address;
                        $project->lat = $request->lat;
                        $project->long = $request->long;
                    }

                    if ($request->filled('city')) $project->city = $request->city;
                    if ($request->filled('state')) $project->state = $request->state;
                    if ($request->filled('country')) $project->country = $request->country;
                    if ($request->filled('zip_code')) $project->zip_code = $request->zip_code;
                    if ($request->filled('start_date')) $project->start_date = $request->start_date;
                    if ($request->filled('end_date')) $project->end_date = $request->end_date;
                    if ($request->filled('cost')) $project->cost = $request->cost;

                    if ($request->hasFile('logo')) {
                        if (isset($project->logo) && !empty($project->logo)) {
                            $this->uploadFile->deleteFileFromS3($project->logo);
                        }

                        $dirPath = str_replace([':uid:', ':project_uuid:'], [$user->organization_id, $project->id], config('constants.organizations.projects.logo_path'));

                        $project->logo = $this->uploadFile->uploadFileInS3($request, $dirPath, 'logo'/* , "100", "100" */);
                    }

                    $project->updated_ip = $request->ip();
                    $project->save();

                    return $this->sendResponse($project, 'Project Updated Successfully.');
                }
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function deleteProject(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $project = Project::whereUuid($request->Uuid)->first();

                if (!isset($project) || empty($project)) {
                    return $this->sendError('Project dose not exists.');
                }

                if (!in_array($user->role_id, [User::USER_ROLE['MANAGER']])) {
                    return $this->sendError('You have no rights to delete project.', [], 401);
                }

                $project->delete();

                return $this->sendResponse([], 'Project deleted Successfully.');
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeProjectStatus(Request $request, $Uuid = null)
    {
        try {
            $user  = $request->user();

            if (isset($user) && !empty($user)) {
                $project = Project::whereUuid($request->Uuid)->first();

                if (!in_array($request->status, Project::STATUS)) {
                    return $this->sendError('Invalid status requested.');
                }

                if (!isset($project) || empty($project)) {
                    return $this->sendError('Project dose not exists.');
                } else if (!in_array($user->role_id, [User::USER_ROLE['COMPANY_ADMIN'], User::USER_ROLE['CONSTRUCATION_SITE_ADMIN'], User::USER_ROLE['MANAGER']])) {
                    return $this->sendError('You have no rights to change status of project.', [], 401);
                } else {
                    $project->status = $request->status;
                    $project->save();

                    return $this->sendResponse($project, 'Status changed successfully.');
                }
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function assignUsersList(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $user = $request->user();

        try {
            if (isset($user) && !empty($user)) {
                $assignedUserIds = ProjectAssignedUser::whereProjectId($request->project_id ?? null)
                    // ->whereCreatedBy($user->id)
                    ->pluck('user_id');

                AppHelper::setDefaultDBConnection(true);

                $query = User::select('id', 'name', 'email', 'profile_image', 'status', 'role_id', 'organization_id')
                    ->whereIn('id', $assignedUserIds)
                    ->orderBy('id', $orderBy);

                AppHelper::setDefaultDBConnection();

                if (isset($request->search) && !empty($request->search)) {
                    $search = trim(strtolower($request->search));

                    $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
                }

                $totalQuery = $query;
                $totalQuery = $totalQuery->count();

                if ($request->exists('cursor')) {
                    $projects = $query->cursorPaginate($limit)->toArray();
                } else {
                    $projects['data'] = $query->get()->toArray();
                }

                $results = [];
                if (!empty($projects['data'])) {
                    $results = $projects['data'];
                }

                if ($request->exists('cursor')) {
                    return $this->sendResponse([
                        'lists' => $results,
                        'total' => $totalQuery,
                        'per_page' => $projects['per_page'],
                        'next_page_url' => ltrim(str_replace($projects['path'], "", $projects['next_page_url']), "?cursor="),
                        'prev_page_url' => ltrim(str_replace($projects['path'], "", $projects['prev_page_url']), "?cursor=")
                    ], 'Project Assigned Users List');
                } else {
                    return $this->sendResponse($results, 'Project Assigned Users List');
                }
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function assignUsers(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    // 'user_ids' => 'required',
                    'project_id' => 'required|exists:projects,id',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $request->user_ids = !empty($request->user_ids) ? explode(',', $request->user_ids) : [];

                // Assign users to project
                if (empty($request->user_ids)) {
                    $projAssignUser = ProjectAssignedUser::whereProjectId($request->project_id)
                        ->where('user_id', '!=', $user->id)
                        ->delete();
                } else {
                    foreach ($request->user_ids as $userId) {
                        $projAssignUser = ProjectAssignedUser::whereProjectId($request->project_id)
                            ->whereUserId($userId)
                            ->first();

                        if (isset($projAssignUser) && !empty($projAssignUser)) {
                            $projAssignUser->updated_ip = $request->ip();
                            $projAssignUser->save();
                        } else {
                            $projAssignUser = new ProjectAssignedUser();
                            $projAssignUser->user_id = $userId;
                            $projAssignUser->project_id = $request->project_id;
                            $projAssignUser->created_by = $user->id;
                            $projAssignUser->created_ip = $request->ip();
                            $projAssignUser->updated_ip = $request->ip();
                            $projAssignUser->save();
                        }
                    }
                }

                return $this->sendResponse([], 'Users assigned to project successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function unAssignUsers(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'user_ids' => 'required',
                    'project_id' => 'required|exists:projects,id',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $request->user_ids = explode(',', $request->user_ids);

                ProjectAssignedUser::whereProjectId($request->project_id)
                    ->whereIn('user_id', $request->user_ids)
                    ->delete();

                return $this->sendResponse([], 'Users un assigned from project successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
