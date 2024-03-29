<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant\ProjectNonWorkingDay;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Helpers\AppHelper;
use App\Models\Tenant\RoleHasSubModule;
use Illuminate\Support\Facades\Log;

class NonWorkingDaysController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.');
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

    public function getNonWorkingDays(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Non Working Day', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectNonWorkingDay::whereProjectId($request->project_id ?? null)
            ->whereStatus(ProjectNonWorkingDay::STATUS['Active'])
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
        }

        if (isset($request->month) && !empty($request->month)) {
            $query = $query->whereMonth('start_date_time', $request->month);
        }

        if (isset($request->year) && !empty($request->year)) {
            $query = $query->whereYear('start_date_time', $request->year);
        }

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $nonWorkingDays = $query->cursorPaginate($limit)->toArray();
        } else {
            $nonWorkingDays['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($nonWorkingDays['data'])) {
            $results = $nonWorkingDays['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $nonWorkingDays['per_page'],
                'next_page_url' => ltrim(str_replace($nonWorkingDays['path'], "", $nonWorkingDays['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($nonWorkingDays['path'], "", $nonWorkingDays['prev_page_url']), "?cursor=")
            ], 'Non working days List');
        } else {
            return $this->sendResponse($results, 'Non working days List');
        }
    }

    public function getNonWorkingDayDetails(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Non Working Day', RoleHasSubModule::ACTIONS['view'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $nonWorkingDays = ProjectNonWorkingDay::select('id', 'project_id', 'name', 'start_date_time', 'end_date_time', 'status')
            ->whereId($request->id)
            ->first();

        if (!isset($nonWorkingDays) || empty($nonWorkingDays)) {
            return $this->sendError('Non working day does not exists.');
        }

        return $this->sendResponse($nonWorkingDays, 'Non working day details.');
    }

    public function addNonWorkingDay(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Non Working Day', RoleHasSubModule::ACTIONS['create'], $user)) {
                //     return $this->sendError('You have no rights to access this action.', [], 401);
                // }

                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'name' => 'required',
                    'start_date_time' => 'required|date_format:Y-m-d',
                    'end_date_time' => 'required|date_format:Y-m-d',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $nonWorkingDays = new ProjectNonWorkingDay();
                $nonWorkingDays->project_id = $request->project_id;
                $nonWorkingDays->name = $request->name;
                $nonWorkingDays->start_date_time = date('Y-m-d H:i:s', strtotime($request->start_date_time));
                $nonWorkingDays->end_date_time = date('Y-m-d H:i:s', strtotime($request->end_date_time));
                $nonWorkingDays->created_by = $user->id;
                $nonWorkingDays->created_ip = $request->ip();
                $nonWorkingDays->updated_ip = $request->ip();

                if (!$nonWorkingDays->save()) {
                    return $this->sendError('Something went wrong while creating the non working day.');
                }

                return $this->sendResponse([], 'Non working day created successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateNonWorkingDay(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Non Working Day', RoleHasSubModule::ACTIONS['edit'], $user)) {
                //     return $this->sendError('You have no rights to access this action.', [], 401);
                // }

                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    'start_date_time' => 'date_format:Y-m-d',
                    'end_date_time' => 'date_format:Y-m-d',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $nonWorkingDays = ProjectNonWorkingDay::whereId($request->id)->first();

                if (!isset($nonWorkingDays) || empty($nonWorkingDays)) {
                    return $this->sendError('Non working day does not exists.');
                }

                if ($request->filled('name')) $nonWorkingDays->name = $request->name;
                if ($request->filled('start_date_time')) $nonWorkingDays->start_date_time = date('Y-m-d H:i:s', strtotime($request->start_date_time));
                if ($request->filled('end_date_time')) $nonWorkingDays->end_date_time = date('Y-m-d H:i:s', strtotime($request->end_date_time));
                $nonWorkingDays->updated_ip = $request->ip();

                if (!$nonWorkingDays->save()) {
                    return $this->sendError('Something went wrong while updating the non working day.');
                }

                return $this->sendResponse([], 'Non working day updated successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeNonWorkingDayStatus(Request $request, $id = null)
    {
        try {
            // $user = $request->user();
            
            // if ($request->status == ProjectNonWorkingDay::STATUS['Deleted']) {
            //     if (!AppHelper::roleHasSubModulePermission('Non Working Day', RoleHasSubModule::ACTIONS['delete'], $user)) {
            //         return $this->sendError('You have no rights to access this action.', [], 401);
            //     }
            // }

            $validator = Validator::make($request->all(), [
                'status' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                }
            }

            $nonWorkingDays = ProjectNonWorkingDay::whereId($request->id)->first();

            if (!isset($nonWorkingDays) || empty($nonWorkingDays)) {
                return $this->sendError('Non working day does not exists.');
            }

            $nonWorkingDays->deleted_at = null;
            $nonWorkingDays->status = $request->status;
            $nonWorkingDays->save();

            if ($nonWorkingDays->status == ProjectNonWorkingDay::STATUS['Deleted']) {
                $nonWorkingDays->delete();
            }

            return $this->sendResponse($nonWorkingDays, 'Status changed successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
