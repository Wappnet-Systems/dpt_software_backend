<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant\ProjectNonWorkingDay;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;

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

                Config::set('database.default', 'tenant');
            }

            return $next($request);
        });
    }

    public function getNonWorkingDays(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectNonWorkingDay::whereStatus(ProjectNonWorkingDay::STATUS['Active'])
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
        $nonWorkingDays = ProjectNonWorkingDay::select('id', 'projects_id', 'name', 'start_date_time', 'end_date_time', 'status')
            ->whereId($request->id)
            ->first();

        if (!isset($nonWorkingDays) || empty($nonWorkingDays)) {
            return $this->sendError('Non Working Days does not exists.');
        }

        return $this->sendResponse($nonWorkingDays, 'Non Working Days details.');
    }

    public function addNonWorkingDay(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'projects_id' => 'required',
                    'name' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $nonWorkingDays = new ProjectNonWorkingDay();
                $nonWorkingDays->projects_id = $request->projects_id;
                $nonWorkingDays->name = $request->name;
                $nonWorkingDays->start_date_time = date('Y-m-d H:i:s');
                $nonWorkingDays->end_date_time = date('Y-m-d H:i:s');
                $nonWorkingDays->created_by = $user->id;
                $nonWorkingDays->created_ip = $request->ip();
                $nonWorkingDays->updated_ip = $request->ip();

                if (!$nonWorkingDays->save()) {
                    return $this->sendError('Something went wrong while creating the non working days.');
                }

                return $this->sendResponse($nonWorkingDays, 'Non working days created successfully.');
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
                'start_date_time' => 'required',
                'end_date_time' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            $nonWorkingDays = ProjectNonWorkingDay::whereId($request->id)->first();

            if (!isset($nonWorkingDays) || empty($nonWorkingDays)) {
                return $this->sendError('Non Working Days does not exists.');
            }

            if ($request->filled('name')) $nonWorkingDays->name = $request->name;
            if ($request->filled('start_date_time')) $nonWorkingDays->start_date_time = date('Y-m-d H:i:s', strtotime($request->start_date_time));
            if ($request->filled('end_date_time')) $nonWorkingDays->end_date_time = date('Y-m-d H:i:s', strtotime($request->end_date_time));
            $nonWorkingDays->updated_ip = $request->ip();

            if (!$nonWorkingDays->save()) {
                return $this->sendError('Something went wrong while updating the non working days.');
            }

            return $this->sendResponse($nonWorkingDays, 'Non working days details updated successfully.');
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
                return $this->sendError('Non Working Days does not exists.');
            }

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
