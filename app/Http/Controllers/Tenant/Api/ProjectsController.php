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
use Illuminate\Support\Facades\Log;

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

                if (!in_array($user->role_id, [User::USER_ROLE['COMPANY_ADMIN'], User::USER_ROLE['CONSTRUCATION_SITE_ADMIN'], User::USER_ROLE['MANAGER']])) {
                    $query->whereStatus(Project::STATUS['In Progress']);
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
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
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
                        'working_start_time' => 'required|date_format:H:i',
                        'working_end_time' => 'required|date_format:H:i',
                        'cost' => 'required',
                    ], [
                        'logo.max' => 'The logo must not be greater than 5mb.',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->messages() as $key => $value) {
                            return $this->sendError('Validation Error.', [$key => $value[0]], 400);
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
                    $project->working_start_time = !empty($request->working_start_time) ? date('H:i:s', strtotime($request->working_start_time)) : NULL;
                    $project->working_end_time = !empty($request->working_end_time) ? date('H:i:s', strtotime($request->working_end_time)) : NULL;
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

                    return $this->sendResponse([], 'Project created successfully.');
                }
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
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
                        'working_start_time' => 'required|date_format:H:i',
                        'working_end_time' => 'required|date_format:H:i',
                        'cost' => 'required',
                    ], [
                        'logo.max' => 'The logo must not be greater than 5mb.',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->messages() as $key => $value) {
                            return $this->sendError('Validation Error.', [$key => $value[0]], 400);
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
                    if ($request->filled('working_start_time')) $project->working_start_time = $request->working_start_time;
                    if ($request->filled('working_end_time')) $project->working_end_time = $request->working_end_time;
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

                    return $this->sendResponse([], 'Project Updated Successfully.');
                }
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
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

                if (!in_array($user->role_id, [User::USER_ROLE['COMPANY_ADMIN']])) {
                    return $this->sendError('You have no rights to delete project.', [], 401);
                }

                $project->delete();

                return $this->sendResponse([], 'Project deleted Successfully.');
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeProjectStatus(Request $request, $Uuid = null)
    {
        try {
            $user  = $request->user();

            if (isset($user) && !empty($user)) {
                $project = Project::whereUuid($request->Uuid)->first();

                if (!in_array($request->status, Project::STATUS)) {
                    return $this->sendError('Invalid status requested.', [], 404);
                }

                if (!isset($project) || empty($project)) {
                    return $this->sendError('Project dose not exists.', [], 404);
                } else if (!in_array($user->role_id, [User::USER_ROLE['COMPANY_ADMIN']])) {
                    return $this->sendError('You have no rights to change status of project.', [], 401);
                } else {
                    $project->status = $request->status;
                    $project->save();

                    return $this->sendResponse($project, 'Status changed successfully.');
                }
            } else {
                return $this->sendError('User does not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
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

                if (isset(USER::USER_ROLE_GROUP[$user->role_id])) {
                    $query->whereIn('role_id', USER::USER_ROLE_GROUP[$user->role_id]);
                } else {
                    $query->where('id', null);
                }

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
                return $this->sendError('User does not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
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
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $projAssignUser = ProjectAssignedUser::whereProjectId($request->project_id)
                    ->where('user_id', '!=', $user->id)
                    ->whereCreatedBy($user->id)
                    ->delete();

                $request->user_ids = !empty($request->user_ids) ? explode(',', $request->user_ids) : [];

                foreach ($request->user_ids as $userId) {
                    $projAssignUser = ProjectAssignedUser::whereProjectId($request->project_id)
                        ->whereUserId($userId)
                        ->whereCreatedBy($user->id)
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

                return $this->sendResponse([], 'Users assigned to project successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function nonAssignUsersList(Request $request)
    {
        try {
            $loggedUser = $request->user();

            if (isset($loggedUser) && !empty($loggedUser)) {
                AppHelper::setDefaultDBConnection(true);

                $query = User::with('role', 'organization')
                    ->where('role_id', '!=', User::USER_ROLE['SUPER_ADMIN'])
                    ->where('id', '!=', $loggedUser->id);

                if (isset(USER::USER_ROLE_GROUP[$loggedUser->role_id])) {
                    $query->whereIn('role_id', USER::USER_ROLE_GROUP[$loggedUser->role_id]);
                } else {
                    $query->where('id', null);
                }

                if (isset($loggedUser->organization_id) && !empty($loggedUser->organization_id)) {
                    $query = $query->WhereHas('organization', function ($query) use ($loggedUser) {
                        $query->whereId($loggedUser->organization_id);
                    });
                }

                $users = $query->get()->toArray();

                AppHelper::setDefaultDBConnection();

                $results = [];

                foreach ($users as $user) {
                    // Check user has not assigned to any projects
                    if (!ProjectAssignedUser::whereUserId($user['id'] ?? '')->exists()) {
                        array_push($results, $user);

                        // Check user has already assigned to requested project
                    } else if (ProjectAssignedUser::whereUserId($user['id'] ?? '')->whereProjectId($request->project_id)->exists()) {
                        array_push($results, $user);
                    } else {
                        // Check loggedin user role and manager role
                        if ($loggedUser->role_id == User::USER_ROLE['COMPANY_ADMIN'] && $user['role_id'] == User::USER_ROLE['CONSTRUCATION_SITE_ADMIN']) {
                            $isShow = true;
                        } else if ($loggedUser->role_id == User::USER_ROLE['CONSTRUCATION_SITE_ADMIN'] && $user['role_id'] == User::USER_ROLE['MANAGER']) {
                            $isShow = true;
                        } else {
                            // Check user has assigned to any project with project status completed
                            $isShow = ProjectAssignedUser::with('project')
                                ->whereHas('project', function ($query) {
                                    $query->whereStatus(Project::STATUS['Completed']);
                                })
                                ->whereUserId($user['id'] ?? '')
                                ->exists();
                        }

                        if ($isShow) {
                            array_push($results, $user);
                        }
                    }
                }

                return $this->sendResponse($results, 'User List');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
