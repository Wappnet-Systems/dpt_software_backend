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
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\ProjectActivityAssignedUser;
use App\Models\Tenant\ProjectMainActivity;
use App\Models\Tenant\RoleHasSubModule;
use Illuminate\Support\Facades\Log;

class ActivitiesController extends Controller
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

    public function getActivities(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Activity Settings', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.');
        // }

        $request->merge([
            'project_id' => Project::whereUuid($request->project_id ?? '')->value('id')
        ]);

        $proActivitiesQuery = ProjectMainActivity::with('parents', 'projectActivities')
            ->whereProjectId($request->project_id ?? '')
            ->whereNull('parent_id')
            ->select('id', 'project_id', 'parent_id', 'name', 'status', 'created_by', 'sort_by')
            ->orderBy('sort_by', 'ASC');

        if ($user->role_id != User::USER_ROLE['MANAGER']) {
            $assignProActivityIds = ProjectActivityAssignedUser::whereUserId($user->id)
                ->pluck('project_activity_id')
                ->toArray();

            $proActivitiesQuery->whereHas('parents.projectActivities', function ($query) use ($assignProActivityIds) {
                $query->orWhereIn('id', $assignProActivityIds)
                    ->orderBy('sort_by', 'ASC');
            });
        }

        $proActivities = $proActivitiesQuery->get();

        return $this->sendResponse($proActivities, 'Activities List');
    }

    public function getActivityDetails(Request $request)
    {
        $user = $request->user();

        /* if (!AppHelper::roleHasSubModulePermission('Activity Settings', RoleHasSubModule::ACTIONS['view'], $user)) {
            return $this->sendError('You have no rights to access this action.');
        } */

        $request->merge([
            'project_id' => Project::whereUuid($request->project_id ?? '')->value('id')
        ]);

        $proActivity = ProjectActivity::with('activitySubCategory', 'unitType', 'manforceType', 'project', 'mainActivity', 'assignedUsers', 'projectInspections')
            ->whereId($request->id)
            ->first();

        if (!isset($proActivity) || empty($proActivity)) {
            return $this->sendError('Activity does not exists.', [], 404);
        }

        return $this->sendResponse($proActivity, 'Activity details get.');
    }

    public function getActivityByMainActivity(Request $request)
    {
        $user = $request->user();

        /* if (!AppHelper::roleHasSubModulePermission('Activity Settings', RoleHasSubModule::ACTIONS['view'], $user)) {
            return $this->sendError('You have no rights to access this action.');
        } */

        $proActivities = ProjectMainActivity::with('project', 'projectActivities')
            ->whereId($request->id)
            ->first();

        if (!isset($proActivities) || empty($proActivities)) {
            return $this->sendError('Activity does not exists.', [], 404);
        }

        return $this->sendResponse($proActivities, 'Sub Activity list by main activity get.');
    }

    public function addActivity(Request $request)
    {
        try {
            $user = $request->user();

            /* if (!AppHelper::roleHasSubModulePermission('Activity Settings', RoleHasSubModule::ACTIONS['create'], $user)) {
                return $this->sendError('You have no rights to access this action.');
            } */

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'project_main_activity_id' => 'required|exists:projects_main_activities,id',
                    'activity_sub_category_id' => 'exists:activity_sub_categories,id',
                    'manforce_type_id' => 'exists:manforce_types,id',
                    'unit_type_id' => 'exists:unit_types,id',
                    'name' => 'required',
                    // 'scaffold_number' => 'required',
                    // 'start_date' => 'required|date_format:Y-m-d',
                    // 'end_date' => 'required|date_format:Y-m-d',
                    // 'location' => 'required',
                    // 'level' => 'required',
                    // 'actual_area' => 'required',
                    // 'cost' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                // Create new project activity
                $proActivity = new ProjectActivity();
                $proActivity->project_id = $request->project_id;
                $proActivity->project_main_activity_id = $request->project_main_activity_id;
                $proActivity->activity_sub_category_id = $request->activity_sub_category_id ?? null;
                $proActivity->manforce_type_id = $request->manforce_type_id ?? null;
                $proActivity->name = $request->name;
                $proActivity->start_date = !empty($request->start_date) ? date('Y-m-d H:i:s', strtotime($request->start_date)) : NULL;
                $proActivity->end_date = !empty($request->end_date) ? date('Y-m-d H:i:s', strtotime($request->end_date)) : NULL;
                $proActivity->actual_start_date = !empty($request->start_date) ? date('Y-m-d H:i:s', strtotime($request->start_date)) : NULL;
                $proActivity->actual_end_date = !empty($request->end_date) ? date('Y-m-d H:i:s', strtotime($request->end_date)) : NULL;
                $proActivity->location = !empty($request->location) ? $request->location : null;
                $proActivity->level = !empty($request->level) ? $request->level : null;
                $proActivity->actual_area = !empty($request->actual_area) ? $request->actual_area : null;
                $proActivity->unit_type_id = $request->unit_type_id ?? null;
                $proActivity->cost = !empty($request->cost) ? $request->cost : null;
                $proActivity->scaffold_requirement = boolval($request->scaffold_requirement);
                $proActivity->helper = boolval($request->helper);
                $proActivity->created_by = $user->id;
                $proActivity->created_ip = $request->ip();
                $proActivity->updated_ip = $request->ip();

                if (!$proActivity->save()) {
                    return $this->sendError('Something went wrong while creating the activity.', [], 500);
                }

                if (isset($request->order_activity_by) && !empty($request->order_activity_by)) {
                    foreach ($request->order_activity_by as $actSort) {
                        $proActivity = ProjectActivity::whereName($actSort['name'])->first();

                        if (isset($proActivity) && !empty($proActivity)) {
                            $proActivity->sort_by = $actSort['index'];
                            $proActivity->save();
                        }
                    }
                }

                return $this->sendResponse([], 'Activity created successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateActivity(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            /* if (!AppHelper::roleHasSubModulePermission('Activity Settings', RoleHasSubModule::ACTIONS['edit'], $user)) {
                return $this->sendError('You have no rights to access this action.');
            } */

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_main_activity_id' => 'exists:projects_main_activities,id',
                    'activity_sub_category_id' => 'exists:activity_sub_categories,id',
                    'manforce_type_id' => 'exists:manforce_types,id',
                    'unit_type_id' => 'exists:unit_types,id',
                    // 'start_date' => 'date_format:Y-m-d',
                    // 'end_date' => 'date_format:Y-m-d',
                    /* 'name' => 'required',
                    'scaffold_number' => 'required',
                    'location' => 'required',
                    'cost' => 'required', */
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                if ($request->boolean('is_remove')) {
                    $proMainActivity = ProjectMainActivity::whereId($request->id)->first();

                    if (isset($proMainActivity) && !empty($proMainActivity)) {
                        $proMainActivity->forcedelete();

                        $proActivity = new ProjectActivity();
                        $proActivity->project_id = $request->project_id;
                        $proActivity->project_main_activity_id = $request->project_main_activity_id;
                        $proActivity->name = $request->name;
                        $proActivity->start_date = !empty($request->start_date) ? date('Y-m-d H:i:s', strtotime($request->start_date)) : NULL;
                        $proActivity->end_date = !empty($request->end_date) ? date('Y-m-d H:i:s', strtotime($request->end_date)) : NULL;
                        $proActivity->actual_start_date = !empty($request->start_date) ? date('Y-m-d H:i:s', strtotime($request->start_date)) : NULL;
                        $proActivity->actual_end_date = !empty($request->end_date) ? date('Y-m-d H:i:s', strtotime($request->end_date)) : NULL;
                        $proActivity->actual_area = !empty($request->actual_area) ? $request->actual_area : null;
                        $proActivity->created_by = $user->id;
                        $proActivity->created_ip = $request->ip();
                        $proActivity->updated_ip = $request->ip();

                        if (!$proActivity->save()) {
                            return $this->sendError('Something went wrong while updating the activity.', [], 500);
                        }
                    }
                } else {
                    $proActivity = ProjectActivity::whereId($request->id)->first();

                    if (!isset($proActivity) || empty($proActivity)) {
                        return $this->sendError('Activity does not exists.', [], 404);
                    }

                    if ($request->filled('project_main_activity_id')) $proActivity->project_main_activity_id = $request->project_main_activity_id;
                    if ($request->filled('activity_sub_category_id')) $proActivity->activity_sub_category_id = $request->activity_sub_category_id ?? null;
                    if ($request->filled('manforce_type_id')) $proActivity->manforce_type_id = $request->manforce_type_id ?? null;
                    if ($request->filled('name')) $proActivity->name = $request->name;
                    if ($request->filled('start_date')) $proActivity->start_date = !empty($request->start_date) ? date('Y-m-d H:i:s', strtotime($request->start_date)) : NULL;
                    if ($request->filled('end_date')) $proActivity->end_date = !empty($request->end_date) ? date('Y-m-d H:i:s', strtotime($request->end_date)) : NULL;
                    if ($request->filled('start_date')) $proActivity->actual_start_date = !empty($request->start_date) ? date('Y-m-d H:i:s', strtotime($request->start_date)) : NULL;
                    if ($request->filled('end_date')) $proActivity->actual_end_date = !empty($request->end_date) ? date('Y-m-d H:i:s', strtotime($request->end_date)) : NULL;
                    if ($request->filled('location')) $proActivity->location = $request->location;
                    if ($request->filled('level')) $proActivity->level = $request->level;
                    if ($request->filled('actual_area')) $proActivity->actual_area = $request->actual_area;
                    if ($request->filled('unit_type_id')) $proActivity->unit_type_id = $request->unit_type_id ?? null;
                    if ($request->filled('cost')) $proActivity->cost = $request->cost;
                    if ($request->filled('scaffold_requirement')) $proActivity->scaffold_requirement = boolval($request->scaffold_requirement);
                    if ($request->filled('helper')) $proActivity->helper = boolval($request->helper);
                    $proActivity->updated_ip = $request->ip();

                    if (!$proActivity->save()) {
                        return $this->sendError('Something went wrong while updating the activity.', [], 500);
                    }
                }

                if (isset($request->order_activity_by) && !empty($request->order_activity_by)) {
                    foreach ($request->order_activity_by as $actSort) {
                        $proActivity = ProjectActivity::whereName($actSort['name'])->first();

                        if (isset($proActivity) && !empty($proActivity)) {
                            $proActivity->sort_by = $actSort['index'];
                            $proActivity->save();
                        }
                    }
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

    public function deleteActivity(Request $request)
    {
        try {
            $user = $request->user();

            /* if (!AppHelper::roleHasSubModulePermission('Activity Settings', RoleHasSubModule::ACTIONS['delete'], $user)) {
                return $this->sendError('You have no rights to access this action.');
            } */

            if (isset($user) && !empty($user)) {
                $proActivity = ProjectActivity::whereId($request->id)->first();

                if (!isset($proActivity) || empty($proActivity)) {
                    return $this->sendError('Activity dose not exists.', [], 404);
                } /* else if (!in_array($user->role_id, [User::USER_ROLE['MANAGER']])) {
                    return $this->sendError('You have no rights to delete project activity.', [], 401);
                } */ else if ($proActivity->status != 1) {
                    return $this->sendError('You can not delete the activity.', [], 400);
                } else {
                    $proActivity->delete();

                    return $this->sendResponse([], 'Activity deleted Successfully.');
                }
            } else {
                return $this->sendError('User does not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeActivityStatus(Request $request, $id = null)
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

            $proActivity = ProjectActivity::whereId($request->id)->first();

            if (!in_array($request->status, ProjectActivity::STATUS)) {
                return $this->sendError('Invalid status requested.', [], 404);
            }

            if (isset($proActivity) && !empty($proActivity)) {
                $proActivity->status = $request->status;
                $proActivity->save();

                return $this->sendResponse($proActivity, 'Status changed successfully.');
            }

            return $this->sendError('Activity does not exists.', [], 404);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateActivityProperty(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            $proMainActivity = ProjectMainActivity::whereId($request->id)->first();

            if (!isset($proMainActivity) || empty($proMainActivity)) {
                return $this->sendError('Activity does not exists.', [], 404);
            }

            if (!isset($request->activities) || empty($request->activities)) {
                return $this->sendError('Invalid request data.', [], 400);
            }

            foreach ($request->activities as $key => $activity) {
                $proActivity = ProjectActivity::whereId($activity['id'] ?? '')->first();

                if (isset($proActivity) && !empty($proActivity)) {
                    if (isset($activity['name']) && !empty($activity['name'])) {
                        $proActivity->name = $activity['name'];
                    }

                    if (isset($activity['start_date']) && !empty($activity['start_date'])) {
                        $proActivity->start_date = date('Y-m-d H:i:s', strtotime($activity['start_date']));
                        $proActivity->actual_start_date = date('Y-m-d H:i:s', strtotime($activity['start_date']));
                    }

                    if (isset($activity['end_date']) && !empty($activity['end_date'])) {
                        $proActivity->end_date = date('Y-m-d H:i:s', strtotime($activity['end_date']));
                        $proActivity->actual_end_date = date('Y-m-d H:i:s', strtotime($activity['end_date']));
                    }

                    $proActivity->activity_sub_category_id = $activity['activity_sub_category_id'] ?? null;
                    $proActivity->manforce_type_id = $activity['manforce_type_id'] ?? null;
                    $proActivity->location = $activity['location'] ?? null;
                    $proActivity->level = $activity['level'] ?? null;
                    $proActivity->actual_area = $activity['actual_area'] ?? null;
                    $proActivity->unit_type_id = $activity['unit_type_id'] ?? null;
                    $proActivity->cost = $activity['cost'] ?? null;
                    $proActivity->scaffold_requirement = boolval($activity['scaffold_requirement']);
                    $proActivity->helper = boolval($activity['helper']);
                    $proActivity->updated_ip = $request->ip();
                    $proActivity->save();

                    if (isset($activity['assigned_users_ids']) && !empty($activity['assigned_users_ids'])) {
                        $projAssignUser = ProjectActivityAssignedUser::whereProjectActivityId($activity['id'])
                            ->where('user_id', '!=', $user->id)
                            ->delete();

                        foreach ($activity['assigned_users_ids'] as $userId) {
                            $projAssignUser = ProjectActivityAssignedUser::whereProjectActivityId($activity['id'])
                                ->whereUserId($userId)
                                ->first();

                            if (isset($projAssignUser) && !empty($projAssignUser)) {
                                $projAssignUser->updated_ip = $request->ip();
                                $projAssignUser->save();
                            } else {
                                $projAssignUser = new ProjectActivityAssignedUser();
                                $projAssignUser->project_activity_id = $activity['id'];
                                $projAssignUser->user_id = $userId;
                                $projAssignUser->created_by = $user->id;
                                $projAssignUser->created_ip = $request->ip();
                                $projAssignUser->updated_ip = $request->ip();
                                $projAssignUser->save();
                            }
                        }
                    }
                }
            }

            return $this->sendResponse([], 'Activity property updated successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    /**
     * daily activity task list
     */
    public function dailyActivityTaskList(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {

                $activityDailyTask = ProjectActivity::select('id', 'project_id', 'project_main_activity_id', 'activity_sub_category_id', 'manforce_type_id', 'name', 'start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'location', 'level', 'actual_area', 'completed_area', 'unit_type_id', 'cost', 'scaffold_requirement', 'helper', 'status', 'productivity_rate', 'created_by', 'sort_by')
                    ->with(['unitType', 'manforceType', 'allocatedManforce', 'allocateMachinery', 'allocateMaterial', 'assignedUsers', 'materialUses', 'activityTrack'])
                    ->whereId($request->activity_id ?? '')
                    ->whereProjectId($request->project_id ?? '');

                if (isset($request->status) && !empty($request->status)) {
                    $activityDailyTask = $activityDailyTask->whereStatus($request->status);
                }

                $activityDailyTaskList = $activityDailyTask->get()->toArray();

                return $this->sendResponse($activityDailyTaskList, 'Activities daily task list.');
            } else {
                return $this->sendError('User does not exist.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
