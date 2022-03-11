<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Helpers\AppHelper;
use App\Models\Tenant\ProjectActivity;

class ActivitiesController extends Controller
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

    public function getActivities(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectActivity::with('activitySubCategory', 'ifcDrawing')
            ->whereProjectId($request->project_id ?? '')
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%'. $search .'%']);
        }

        if ($request->exists('cursor')) {
            $proActivities = $query->cursorPaginate($limit)->toArray();
        } else {
            $proActivities['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($proActivities['data'])) {
            $results = $proActivities['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'per_page' => $proActivities['per_page'],
                'next_page_url' => $proActivities['next_page_url'],
                'prev_page_url' => $proActivities['prev_page_url']
            ], 'Activities List');
        } else {
            return $this->sendResponse($results, 'Activities List');
        }
    }

    public function getActivityDetails(Request $request)
    {
        $proActivity = ProjectActivity::with('activitySubCategory', 'ifcDrawing')
            ->whereId($request->id)
            ->first();

        if (!isset($proActivity) || empty($proActivity)) {
            return $this->sendError('Activity does not exists.');
        }

        return $this->sendResponse($proActivity, 'Activity details updated successfully.');
    }

    public function addActivity(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'activity_sub_category_id' => 'required|exists:activity_sub_categories,id',
                    'project_drowing_id' => 'required|exists:projects_ifc_drawings,id',
                    'name' => 'required',
                    'scaffold_number' => 'required',
                    'start_date' => 'required|date_format:Y-m-d H:i:s',
                    'end_date' => 'required|date_format:Y-m-d H:i:s',
                    'location' => 'required',
                    'level' => 'required',
                    'actual_area' => 'required',
                    'cost' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                // Create new project activity
                $proActivity = new ProjectActivity();
                $proActivity->project_id = $request->project_id;
                $proActivity->activity_sub_category_id = $request->activity_sub_category_id;
                $proActivity->project_drowing_id = $request->project_drowing_id;
                $proActivity->name = $request->name;
                $proActivity->scaffold_number = empty($request->scaffold_number) ? $request->scaffold_number : null;
                $proActivity->start_date = !empty($request->start_date) ? date('Y-m-d H:i:s', strtotime($request->start_date)) : NULL;
                $proActivity->end_date = !empty($request->end_date) ? date('Y-m-d H:i:s', strtotime($request->end_date)) : NULL;
                $proActivity->actual_start_date = !empty($request->start_date) ? date('Y-m-d H:i:s', strtotime($request->start_date)) : NULL;
                $proActivity->actual_end_date = !empty($request->end_date) ? date('Y-m-d H:i:s', strtotime($request->end_date)) : NULL;
                $proActivity->location = $request->location;
                $proActivity->level = !empty($request->level) ? $request->level : null;
                $proActivity->actual_area = !empty($request->actual_area) ? $request->actual_area : null;
                $proActivity->cost = !empty($request->cost) ? $request->cost : null;
                $proActivity->created_by = $user->id;
                $proActivity->created_ip = $request->ip();
                $proActivity->updated_ip = $request->ip();

                if (!$proActivity->save()) {
                    return $this->sendError('Something went wrong while creating the activity.');
                }

                return $this->sendResponse($proActivity, 'Activity created successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateActivity(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'id' => 'required',
                    'activity_sub_category_id' => 'exists:activity_sub_categories,id',
                    'project_drowing_id' => 'exists:projects_ifc_drawings,id',
                    'start_date' => 'date_format:Y-m-d H:i:s',
                    'end_date' => 'date_format:Y-m-d H:i:s',
                    /* 'name' => 'required',
                    'scaffold_number' => 'required',
                    'location' => 'required',
                    'cost' => 'required', */
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $proActivity = ProjectActivity::whereId($request->id)->first();

                if (!isset($proActivity) || empty($proActivity)) {
                    return $this->sendError('Activity does not exists.');
                }

                if ($request->filled('activity_sub_category_id')) $proActivity->activity_sub_category_id = $request->activity_sub_category_id;
                if ($request->filled('project_drowing_id')) $proActivity->project_drowing_id = $request->project_drowing_id;
                if ($request->filled('name')) $proActivity->name = $request->name;
                if ($request->filled('scaffold_number')) $proActivity->scaffold_number = $request->scaffold_number;
                if ($request->filled('start_date')) $proActivity->start_date = !empty($request->start_date) ? date('Y-m-d H:i:s', strtotime($request->start_date)) : NULL;
                if ($request->filled('end_date')) $proActivity->end_date = !empty($request->end_date) ? date('Y-m-d H:i:s', strtotime($request->end_date)) : NULL;
                if ($request->filled('actual_start_date')) $proActivity->actual_start_date = !empty($request->start_date) ? date('Y-m-d H:i:s', strtotime($request->start_date)) : NULL;
                if ($request->filled('actual_end_date')) $proActivity->actual_end_date = !empty($request->end_date) ? date('Y-m-d H:i:s', strtotime($request->end_date)) : NULL;
                if ($request->filled('location')) $proActivity->location = $request->location;
                if ($request->filled('level')) $proActivity->level = $request->level;
                if ($request->filled('actual_area')) $proActivity->actual_area = $request->actual_area;
                if ($request->filled('cost')) $proActivity->cost = $request->cost;
                $proActivity->updated_ip = $request->ip();

                if (!$proActivity->save()) {
                    return $this->sendError('Something went wrong while creating the activity.');
                }

                return $this->sendResponse($proActivity, 'Activity updated successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function deleteActivity(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $proActivity = ProjectActivity::whereId($request->id)->first();

                if (!isset($proActivity) || empty($proActivity)) {
                    return $this->sendError('Activity dose not exists.');
                } else if (!in_array($user->role_id, [User::USER_ROLE['COMPANY_ADMIN']])) {
                    return $this->sendError('You have no rights to update User.');
                } else {
                    $proActivity->delete();

                    return $this->sendResponse([], 'Activity deleted Successfully.');
                }
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeActivityStatus(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!in_array($user->role_id, [User::USER_ROLE['COMPANY_ADMIN']])) {
                return $this->sendError('You have no rights to update User.');
            }

            $proActivity = ProjectActivity::whereId($request->id)->first();

            if (isset($proActivity) && !empty($proActivity)) {
                $proActivity->status = $request->status;
                $proActivity->save();

                return $this->sendResponse($proActivity, 'Status changed successfully.');
            }

            return $this->sendError('Activity does not exists.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
