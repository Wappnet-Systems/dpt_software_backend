<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectMachinery;
use App\Helpers\AppHelper;
use App\Models\Tenant\RoleHasSubModule;
use Illuminate\Support\Facades\Log;

class MachineriesController extends Controller
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

    public function getMachineries(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Machinery Management', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectMachinery::whereStatus(ProjectMachinery::STATUS['Active'])
            ->whereProjectId($request->project_id ?? '')
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
        }

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $machineries = $query->cursorPaginate($limit)->toArray();
        } else {
            $machineries['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($machineries['data'])) {
            $results = $machineries['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $machineries['per_page'],
                'next_page_url' => ltrim(str_replace($machineries['path'], "", $machineries['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($machineries['path'], "", $machineries['prev_page_url']), "?cursor=")
            ], 'Machinery List.');
        } else {
            return $this->sendResponse($results, 'Machinery List.');
        }
    }

    public function getDetails(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Machinery Management', RoleHasSubModule::ACTIONS['view'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $machineries = ProjectMachinery::select('id', 'name', 'status')
            ->whereStatus(ProjectMachinery::STATUS['Active'])
            ->whereId($request->id)
            ->first();

        if (!isset($machineries) || empty($machineries)) {
            return $this->sendError('Machinery does not exists.');
        }

        return $this->sendResponse($machineries, 'Machinery details.');
    }

    public function addMachineryCategory(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Machinery Management', RoleHasSubModule::ACTIONS['create'], $user)) {
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

                $machineries = new ProjectMachinery();
                $machineries->project_id = $request->project_id;
                $machineries->name = $request->name;
                $machineries->created_ip = $request->ip();
                $machineries->updated_ip = $request->ip();

                if (!$machineries->save()) {
                    return $this->sendError('Something went wrong while creating the machineries.');
                }

                return $this->sendResponse([], 'Machineries created successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateMachineryCategory(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Machinery Management', RoleHasSubModule::ACTIONS['edit'], $user)) {
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

                $machineries = ProjectMachinery::whereId($request->id)->first();

                if (!isset($machineries) || empty($machineries)) {
                    return $this->sendError('Machinery does not exists.');
                }

                if ($request->filled('name')) $machineries->name = $request->name;
                $machineries->updated_ip = $request->ip();

                if (!$machineries->save()) {
                    return $this->sendError('Something went wrong while updating the machinery');
                }

                return $this->sendResponse([], 'Machinery details updated successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeStatus(Request $request, $id = null)
    {
        try {
            $machineries = ProjectMachinery::whereId($request->id)->first();

            if (!isset($machineries) || empty($machineries)) {
                return $this->sendError('Machinery does not exists.');
            }

            if (!in_array($request->status, ProjectMachinery::STATUS)) {
                return $this->sendError('Invalid status requested.');
            }

            $machineries->deleted_at = null;
            $machineries->status = $request->status;
            $machineries->save();

            if ($machineries->status == ProjectMachinery::STATUS['Deleted']) {
                $machineries->delete();
            }

            return $this->sendResponse($machineries, 'Status changed successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
