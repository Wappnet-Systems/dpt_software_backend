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
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectNonWorkingDay::whereProjectId($request->project_id ?? null)
            ->whereStatus(ProjectNonWorkingDay::STATUS['Active'])
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
        }

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
                'per_page' => $nonWorkingDays['per_page'],
                'next_page_url' => $nonWorkingDays['next_page_url'],
                'prev_page_url' => $nonWorkingDays['prev_page_url']
            ], 'Non working days List');
        } else {
            return $this->sendResponse($results, 'Non working days List');
        }
    }

    public function getNonWorkingDayDetails(Request $request)
    {
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
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'name' => 'required',
                    'start_date_time' => 'required|date_format:Y-m-d H:i:s',
                    'end_date_time' => 'required|date_format:Y-m-d H:i:s',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
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

                return $this->sendResponse($nonWorkingDays, 'Non working day created successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateNonWorkingDay(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required',
                'start_date_time' => 'date_format:Y-m-d H:i:s',
                'end_date_time' => 'date_format:Y-m-d H:i:s',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
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

            return $this->sendResponse($nonWorkingDays, 'Non working day updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeNonWorkingDayStatus(Request $request)
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
            return $this->sendError($e->getMessage());
        }
    }
}
