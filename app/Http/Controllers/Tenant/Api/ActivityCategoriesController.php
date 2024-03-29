<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ActivityCategory;
use App\Models\Tenant\ActivitySubCategory;
use App\Helpers\AppHelper;
use App\Models\Tenant\RoleHasSubModule;
use Illuminate\Support\Facades\Log;

class ActivityCategoriesController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.', [], 401);
                }

                // if (!AppHelper::roleHasModulePermission('Masters', $user)) {
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

    public function getActivityCategory(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Activity Category Management', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ActivityCategory::with('activitySubCategories')
            ->whereStatus(ActivityCategory::STATUS['Active'])
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);

            $query = $query->orWhereHas('activitySubCategories', function ($query) use ($search) {
                $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%'])
                    ->whereStatus(ActivitySubCategory::STATUS['Active']);
            });
        }

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $activityCategory = $query->cursorPaginate($limit)->toArray();
        } else {
            $activityCategory['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($activityCategory['data'])) {
            $results = $activityCategory['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $activityCategory['per_page'],
                'next_page_url' => ltrim(str_replace($activityCategory['path'], "", $activityCategory['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($activityCategory['path'], "", $activityCategory['prev_page_url']), "?cursor=")
            ], 'Activity Category List');
        } else {
            return $this->sendResponse($results, 'Activity Category List');
        }
    }

    public function getDetails(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Activity Category Management', RoleHasSubModule::ACTIONS['view'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $activityCategory = ActivityCategory::whereId($request->id)->first();

        if (!isset($activityCategory) || empty($activityCategory)) {
            return $this->sendError('activity Category does not exists.');
        }

        return $this->sendResponse($activityCategory, 'Activity category details.');
    }

    public function addActivityCategory(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Activity Category Management', RoleHasSubModule::ACTIONS['create'], $user)) {
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

                $activityCategory = new ActivityCategory();
                $activityCategory->name = $request->name;
                $activityCategory->created_ip = $request->ip();
                $activityCategory->updated_ip = $request->ip();

                if (!$activityCategory->save()) {
                    return $this->sendError('Something went wrong while creating the activity category.');
                }

                return $this->sendResponse([], 'Activity category created successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateActivityCategory(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Activity Category Management', RoleHasSubModule::ACTIONS['edit'], $user)) {
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

                $activityCategory = ActivityCategory::whereId($request->id)->first();

                if (!isset($activityCategory) || empty($activityCategory)) {
                    return $this->sendError('Activity category dose not exists.');
                }

                if ($request->filled('name')) $activityCategory->name = $request->name;
                $activityCategory->updated_ip = $request->ip();

                if (!$activityCategory->save()) {
                    return $this->sendError('Something went wrong while updating the activity category.');
                }

                return $this->sendResponse([], 'Activity category details updated successfully.');
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
            // $user = $request->user();
            
            // if ($request->status == ActivityCategory::STATUS['Deleted']) {
            //     if (!AppHelper::roleHasSubModulePermission('Activity Category Management', RoleHasSubModule::ACTIONS['delete'], $user)) {
            //         return $this->sendError('You have no rights to access this action.', [], 401);
            //     }
            // }

            $activityCategory = ActivityCategory::whereId($request->id)->first();

            if (!isset($activityCategory) || empty($activityCategory)) {
                return $this->sendError('Activity category dose not exists.');
            }

            if (!in_array($request->status, ActivityCategory::STATUS)) {
                return $this->sendError('Invalid status requested.');
            }

            $activityCategory->deleted_at = null;
            $activityCategory->status = $request->status;
            $activityCategory->save();

            if ($activityCategory->status == ActivityCategory::STATUS['Deleted']) {
                $activityCategory->delete();
            }

            return $this->sendResponse($activityCategory, 'Status changed successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
