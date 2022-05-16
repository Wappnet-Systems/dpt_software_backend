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
use App\Models\Tenant\ActivitySubCategory;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectMainActivity;
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\RoleHasSubModule;
use Illuminate\Support\Facades\Log;

class MainActivitiesController extends Controller
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

    public function getMainActivities(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Activity Settings', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.');
        // }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $request->merge([
            'project_id' => Project::whereUuid($request->project_id ?? '')->value('id')
        ]);

        $query = ProjectMainActivity::with('project', 'activitySubCategory')
            ->whereProjectId($request->project_id ?? '')
            ->whereStatus(ProjectMainActivity::STATUS['Active'])
            ->select('id', 'project_id', 'activity_sub_category_id', 'name', 'status', 'created_by')
            ->orderby('id', $orderBy);

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $proMainActivities = $query->cursorPaginate($limit)->toArray();
        } else {
            $proMainActivities['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($proMainActivities['data'])) {
            $results = $proMainActivities['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $proMainActivities['per_page'],
                'next_page_url' => ltrim(str_replace($proMainActivities['path'], "", $proMainActivities['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($proMainActivities['path'], "", $proMainActivities['prev_page_url']), "?cursor=")
            ], 'Main activities list.');
        } else {
            return $this->sendResponse($results, 'Main activities list.');
        }
    }

    public function getMainActivityDetails(Request $request)
    {
        $user = $request->user();

        /* if (!AppHelper::roleHasSubModulePermission('Activity Settings', RoleHasSubModule::ACTIONS['view'], $user)) {
            return $this->sendError('You have no rights to access this action.');
        } */

        $proMainActivity = ProjectMainActivity::with('project', 'activitySubCategory')
            ->whereId($request->id ?? '')
            ->select('id', 'project_id', 'activity_sub_category_id', 'name', 'status', 'created_by')
            ->first();

        if (!isset($proMainActivity) || empty($proMainActivity)) {
            return $this->sendError('Main Activity does not exists.', [], 404);
        }

        return $this->sendResponse($proMainActivity, 'Main activity details updated successfully.');
    }

    public function addMainActivity(Request $request)
    {
        try {
            $user = $request->user();

            /* if (!AppHelper::roleHasSubModulePermission('Activity Settings', RoleHasSubModule::ACTIONS['create'], $user)) {
                return $this->sendError('You have no rights to access this action.');
            } */

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'activity_sub_category_id' => 'required|exists:activity_sub_categories,id',
                    'name' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                // Create new project activity
                $proMainActivity = new ProjectMainActivity();
                $proMainActivity->project_id = $request->project_id;
                $proMainActivity->activity_sub_category_id = $request->activity_sub_category_id;
                $proMainActivity->name = $request->name;
                $proMainActivity->created_by = $user->id;
                $proMainActivity->created_ip = $request->ip();
                $proMainActivity->updated_ip = $request->ip();

                if (!$proMainActivity->save()) {
                    return $this->sendError('Something went wrong while creating the activity.', [], 500);
                }

                return $this->sendResponse([], 'Main activity created successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateMainActivity(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            /* if (!AppHelper::roleHasSubModulePermission('Activity Settings', RoleHasSubModule::ACTIONS['edit'], $user)) {
                return $this->sendError('You have no rights to access this action.');
            } */

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'exists:projects,id',
                    'activity_sub_category_id' => 'exists:activity_sub_categories,id',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $proMainActivity = ProjectMainActivity::whereId($request->id)->first();

                if (!isset($proMainActivity) || empty($proMainActivity)) {
                    return $this->sendError('Activity does not exists.');
                }

                if ($request->filled('project_id')) $proMainActivity->project_id = $request->project_id;
                if ($request->filled('activity_sub_category_id')) $proMainActivity->activity_sub_category_id = $request->activity_sub_category_id;
                if ($request->filled('name')) $proMainActivity->name = $request->name;
                $proMainActivity->updated_ip = $request->ip();

                if (!$proMainActivity->save()) {
                    return $this->sendError('Something went wrong while creating the activity.', [], 500);
                }

                return $this->sendResponse([], 'Activity updated successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeMainActivityStatus(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'status' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                }
            }

            /* if (!in_array($user->role_id, [User::USER_ROLE['COMPANY_ADMIN']])) {
                return $this->sendError('You have no rights to change activity status.', [], 401);
            } */
            
            if (!in_array($request->status, ProjectMainActivity::STATUS)) {
                return $this->sendError('Invalid status requested.', [], 404);
            }

            $proMainActivity = ProjectMainActivity::whereId($request->id ?? '')->first();

            if (isset($proMainActivity) && !empty($proMainActivity)) {
                $proMainActivity->status = $request->status;

                if ($proMainActivity->status == ProjectMainActivity::STATUS['Deleted']) {
                    if (ProjectActivity::whereProjectMainActivityId($request->id ?? '')->exists()) {
                        return $this->sendError('You can not delete this activity as its assign into sub activities.', [], 400);
                    }

                    $proMainActivity->delete();
                } else {
                    $proMainActivity->delete_at = null;
                }

                $proMainActivity->save();

                return $this->sendResponse($proMainActivity, 'Status changed successfully.');
            }

            return $this->sendError('Main activity does not exists.', [], 404);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
