<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectGang;
use App\Helpers\AppHelper;
use App\Models\Tenant\RoleHasSubModule;
use Illuminate\Support\Facades\Log;

class GangsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.', [], 401);
                }

                // if (!AppHelper::roleHasModulePermission('Planning and Scheduling', $user)) {
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

    public function getGangs(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Gangs Management', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectGang::whereProjectId($request->project_id ?? null)
            ->whereStatus(ProjectGang::STATUS['Active'])
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
        }

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $projectGangs = $query->cursorPaginate($limit)->toArray();
        } else {
            $projectGangs['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($projectGangs['data'])) {
            $results = $projectGangs['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $projectGangs['per_page'],
                'next_page_url' => ltrim(str_replace($projectGangs['path'], "", $projectGangs['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($projectGangs['path'], "", $projectGangs['prev_page_url']), "?cursor=")
            ], 'Project gangs List');
        } else {
            return $this->sendResponse($results, 'Project gangs List');
        }
    }

    public function getGangDetails(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Gangs Management', RoleHasSubModule::ACTIONS['view'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $projectGangs = ProjectGang::select('id', 'project_id', 'name', 'status')
            ->whereId($request->id)
            ->first();

        if (!isset($projectGangs) || empty($projectGangs)) {
            return $this->sendError('Project gang does not exists.');
        }

        return $this->sendResponse($projectGangs, 'Project gang details.');
    }

    public function addGang(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Gangs Management', RoleHasSubModule::ACTIONS['create'], $user)) {
                //     return $this->sendError('You have no rights to access this action.', [], 401);
                // }

                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'name' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $projectGangs = new ProjectGang();
                $projectGangs->project_id = $request->project_id;
                $projectGangs->name = $request->name;
                $projectGangs->created_by = $user->id;

                if (!$projectGangs->save()) {
                    return $this->sendError('Something went wrong while creating the project gang.');
                }

                return $this->sendResponse([], 'Project gang created successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateGang(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Gangs Management', RoleHasSubModule::ACTIONS['edit'], $user)) {
                //     return $this->sendError('You have no rights to access this action.', [], 401);
                // }

                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $projectGangs = ProjectGang::whereId($request->id)->first();

                if (!isset($projectGangs) || empty($projectGangs)) {
                    return $this->sendError('Project gang does not exists.');
                }

                if ($request->filled('name')) $projectGangs->name = $request->name;

                if (!$projectGangs->save()) {
                    return $this->sendError('Something went wrong while udating the project gang.');
                }

                return $this->sendResponse([], 'Project gang updated successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeGangStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                }
            }

            $projectGangs = ProjectGang::whereId($request->id)->first();

            if (!isset($projectGangs) || empty($projectGangs)) {
                return $this->sendError('Project gang does not exists.');
            }

            if (!in_array($request->status, ProjectGang::STATUS)) {
                return $this->sendError('Invalid status requested.');
            }

            $projectGangs->deleted_at = null;
            $projectGangs->status = $request->status;
            $projectGangs->save();

            if ($projectGangs->status == ProjectGang::STATUS['Deleted']) {
                $projectGangs->delete();
            }

            return $this->sendResponse($projectGangs, 'Status changed successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
