<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectManforce;
use App\Models\Tenant\ProjectActivityAllocateManforce;
use App\Helpers\AppHelper;
use Illuminate\Support\Facades\Log;

class ManforcesController extends Controller
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

    public function getManforces(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectManforce::with('manforce')
            ->whereProjectId($request->project_id ?? null)
            ->orderBy('id', $orderBy);

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $projectManforce = $query->cursorPaginate($limit)->toArray();
        } else {
            $projectManforce['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($projectManforce['data'])) {
            $results = $projectManforce['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $projectManforce['per_page'],
                'next_page_url' => ltrim(str_replace($projectManforce['path'], "", $projectManforce['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($projectManforce['path'], "", $projectManforce['prev_page_url']), "?cursor=")
            ], 'Project manforce List');
        } else {
            return $this->sendResponse($results, 'Project manforce List');
        }
    }

    public function getManforceDetails(Request $request)
    {
        $projectManforce = ProjectManforce::with('manforce')
            ->select('id', 'project_id', 'manforce_type_id', 'total_manforce', 'cost')
            ->whereId($request->id)
            ->first();

        if (!isset($projectManforce) || empty($projectManforce)) {
            return $this->sendError('Project manforce does not exists.');
        }

        return $this->sendResponse($projectManforce, 'Project manforce details.');
    }

    public function addManforce(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'project_id' => 'required|exists:projects,id',
                'manforce_type_id' => 'required|exists:manforce_types,id',
                'total_manforce' => 'required',
                'cost' => 'required',
                'cost_type' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                }
            }

            if (!in_array($request->cost_type, ProjectManforce::COST_TYPE)) {
                return $this->sendError('Invalid cost type request.');
            }

            $projectManforce = new ProjectManforce();
            $projectManforce->project_id = $request->project_id;
            $projectManforce->manforce_type_id = $request->manforce_type_id;
            $projectManforce->total_manforce = $request->total_manforce;
            $projectManforce->cost = $request->cost;
            $projectManforce->cost_type = $request->cost_type;
            $projectManforce->created_ip = $request->ip();
            $projectManforce->updated_ip = $request->ip();

            if (!$projectManforce->save()) {
                return $this->sendError('Something went wrong while creating the project manforce.');
            }

            return $this->sendResponse([], 'Project manforce created successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateManforce(Request $request, $id = null)
    {
        try {
            $validator = Validator::make($request->all(), [
                'total_manforce' => 'required',
                'cost' => 'required',
                'cost_type' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                }
            }

            if (!in_array($request->cost_type, ProjectManforce::COST_TYPE)) {
                return $this->sendError('Invalid cost type request.');
            }

            $projectManforce = ProjectManforce::whereId($request->id)->first();

            if (!isset($projectManforce) || empty($projectManforce)) {
                return $this->sendError('Project manforce does not exists.');
            }

            if ($request->filled('total_manforce')) $projectManforce->total_manforce = $request->total_manforce;
            if ($request->filled('cost')) $projectManforce->cost = $request->cost;
            if ($request->filled('cost_type')) $projectManforce->cost_type = $request->cost_type;

            $projectManforce->updated_ip = $request->ip();

            if (!$projectManforce->save()) {
                return $this->sendError('Something went wrong while updating the project manforce.');
            }

            return $this->sendResponse([], 'Project manforce updated successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function deleteManforce(Request $request, $id = null)
    {
        try {
            $projectManforce = ProjectManforce::whereId($request->id)->first();

            if (!isset($projectManforce) || empty($projectManforce)) {
                return $this->sendError('Project manforce does not exists.');
            }

            /* check manforce is already used in activity */
            $isUsedManforce = ProjectActivityAllocateManforce::where('project_manforce_id', $request->id)->exists();

            if ($isUsedManforce) {
                return $this->sendError('You can not remove allocated manforce from activity.');
            }

            $projectManforce->delete();

            return $this->sendResponse([], 'Allocated manforce remove from activity successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
