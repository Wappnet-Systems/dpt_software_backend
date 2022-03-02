<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectManforce;

class ManforcesController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.');
                }

                $hostnameId = Organization::whereId($user->organization_id)->value('hostname_id');

                $hostname = Hostname::whereId($hostnameId)->first();
                $website = Website::whereId($hostname->website_id)->first();

                $environment = app(\Hyn\Tenancy\Environment::class);
                $hostname = Hostname::whereWebsiteId($website->id)->first();

                $environment->tenant($website);
                $environment->hostname($hostname);

                Config::set('database.default', 'tenant');
            }

            return $next($request);
        });
    }

    public function getManforces(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectManforce::with('project')->whereStatus(ProjectManforce::STATUS['Active'])
            ->orderBy('id', $orderBy);


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
                'per_page' => $projectManforce['per_page'],
                'next_page_url' => $projectManforce['next_page_url'],
                'prev_page_url' => $projectManforce['prev_page_url']
            ], 'Project manforce List');
        } else {
            return $this->sendResponse($results, 'Project manforce List');
        }
    }

    public function getManforceDetails(Request $request)
    {
        $projectManforce = ProjectManforce::select('id', 'project_id', 'manforce_type_id', 'total_manforce', 'available_manforce', 'productivity_rate', 'cost', 'status')
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
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            $projectManforce = new ProjectManforce();
            $projectManforce->project_id = $request->project_id;
            $projectManforce->manforce_type_id = $request->manforce_type_id;
            $projectManforce->total_manforce = $request->total_manforce;
            $projectManforce->cost = $request->cost;
            $projectManforce->created_ip = $request->ip();
            $projectManforce->updated_ip = $request->ip();

            if (!$projectManforce->save()) {
                return $this->sendError('Something went wrong while creating the project manforce.');
            }

            return $this->sendResponse($projectManforce, 'Project manforce created successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateManforce(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'manforce_type_id' => 'required|exists:manforce_types,id',
                'total_manforce' => 'required',
                'cost' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            $projectManforce = ProjectManforce::whereId($request->id)->first();

            if (!isset($projectManforce) || empty($projectManforce)) {
                return $this->sendError('Project manforce does not exists.');
            }

            if ($request->filled('manforce_type_id')) $projectManforce->manforce_type_id = $request->manforce_type_id;
            if ($request->filled('total_manforce')) $projectManforce->total_manforce = $request->total_manforce;
            if ($request->filled('cost')) $projectManforce->cost = $request->cost;
            $projectManforce->updated_ip = $request->ip();

            if (!$projectManforce->save()) {
                return $this->sendError('Something went wrong while updating the project manforce.');
            }

            return $this->sendResponse($projectManforce, 'Project manforce details updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeManforceStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            $projectManforce = ProjectManforce::whereId($request->id)->first();

            if (!isset($projectManforce) || empty($projectManforce)) {
                return $this->sendError('Project manforce does not exists.');
            }

            $projectManforce->status = $request->status;
            $projectManforce->save();

            if ($projectManforce->status == ProjectManforce::STATUS['Deleted']) {
                $projectManforce->delete();
            }

            return $this->sendResponse($projectManforce, 'Status changed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
