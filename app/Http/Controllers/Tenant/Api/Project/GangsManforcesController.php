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
use App\Models\Tenant\ProjectGangManforce;
use App\Helpers\AppHelper;
use App\Models\Tenant\RoleHasSubModule;
use Illuminate\Support\Facades\Log;

class GangsManforcesController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.', [], 401);
                }

                if (!AppHelper::roleHasModulePermission('Planning and Scheduling', $user)) {
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

    public function getGangsManforces(Request $request)
    {
        $user = $request->user();

        if (!AppHelper::roleHasSubModulePermission('Manforce Gang Management', RoleHasSubModule::ACTIONS['list'], $user)) {
            return $this->sendError('You have no rights to access this action.', [], 401);
        }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectGangManforce::with('projectGang', 'manforce')
            ->whereGangId($request->gang_id ?? '')
            ->orderBy('id', $orderBy);

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $projectGangManforce = $query->cursorPaginate($limit)->toArray();
        } else {
            $projectGangManforce['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($projectGangManforce['data'])) {
            $results = $projectGangManforce['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $projectGangManforce['per_page'],
                'next_page_url' => ltrim(str_replace($projectGangManforce['path'], "", $projectGangManforce['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($projectGangManforce['path'], "", $projectGangManforce['prev_page_url']), "?cursor=")
            ], 'Project gangs manforce List.');
        } else {
            return $this->sendResponse($results, 'Project gangs manforce List.');
        }
    }

    public function getGangManforceDetails(Request $request)
    {
        $user = $request->user();

        if (!AppHelper::roleHasSubModulePermission('Manforce Gang Management', RoleHasSubModule::ACTIONS['view'], $user)) {
            return $this->sendError('You have no rights to access this action.', [], 401);
        }

        $projectGangManforce = ProjectGangManforce::select('id', 'gang_id', 'manforce_type_id', 'total_manforce')
            ->with('projectGang', 'manforce')
            ->whereId($request->id)
            ->first();

        if (!isset($projectGangManforce) || empty($projectGangManforce)) {
            return $this->sendError('Project gang manforce does not exists.');
        }

        return $this->sendResponse($projectGangManforce, 'Project gang manforce details.');
    }

    public function addGangManforce(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if (!AppHelper::roleHasSubModulePermission('Manforce Gang Management', RoleHasSubModule::ACTIONS['create'], $user)) {
                    return $this->sendError('You have no rights to access this action.', [], 401);
                }

                $validator = Validator::make($request->all(), [
                    'gang_id' => 'required|exists:projects_gangs,id',
                    'manforce_type_id' => 'required|exists:manforce_types,id',
                    'total_manforce' => 'required'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $projectGangManforce = new ProjectGangManforce();
                $projectGangManforce->gang_id = $request->gang_id;
                $projectGangManforce->manforce_type_id = $request->manforce_type_id;
                $projectGangManforce->total_manforce = $request->total_manforce;

                if (!$projectGangManforce->save()) {
                    return $this->sendError('Something went wrong while creating the project gang manforce.');
                }

                return $this->sendResponse([], 'Project gang manforce created successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateGangManforce(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if (!AppHelper::roleHasSubModulePermission('Manforce Gang Management', RoleHasSubModule::ACTIONS['edit'], $user)) {
                    return $this->sendError('You have no rights to access this action.', [], 401);
                }

                $validator = Validator::make($request->all(), [
                    'gang_id' => 'required|exists:projects_gangs,id',
                    'manforce_type_id' => 'required|exists:manforce_types,id',
                    'total_manforce' => 'required'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $projectGangManforce = ProjectGangManforce::whereId($request->id)->first();

                if (!isset($projectGangManforce) || empty($projectGangManforce)) {
                    return $this->sendError('Project gang manforce does not exists.');
                }

                if ($request->filled('gang_id')) $projectGangManforce->gang_id = $request->gang_id;
                if ($request->filled('manforce_type_id')) $projectGangManforce->manforce_type_id = $request->manforce_type_id;
                if ($request->filled('total_manforce')) $projectGangManforce->total_manforce = $request->total_manforce;

                if (!$projectGangManforce->save()) {
                    return $this->sendError('Something went wrong while upadating the project gang manforce.');
                }

                return $this->sendResponse([], 'Project gang manforce updated successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function deleteGangManforce(Request $request)
    {
        try {
            $user = $request->user();

            if (!AppHelper::roleHasSubModulePermission('Manforce Gang Management', RoleHasSubModule::ACTIONS['delete'], $user)) {
                return $this->sendError('You have no rights to access this action.', [], 401);
            }

            $projectGangManforce = ProjectGangManforce::whereId($request->id)->first();

            if (!isset($projectGangManforce) || empty($projectGangManforce)) {
                return $this->sendError('Project gang manforce does not exists.');
            }

            $projectGangManforce->delete();

            return $this->sendresponse([], 'Project gang manforce deleted Successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
