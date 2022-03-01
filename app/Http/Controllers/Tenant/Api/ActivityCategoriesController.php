<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ActivityCategory;
use App\Models\Tenant\ActivitySubCategory;

class ActivityCategoriesController extends Controller
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

    public function getActivityCategory(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ActivityCategory::with('activitySubCategories')->whereStatus(ActivityCategory::STATUS['Active'])->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);

            $query = $query->orWhereHas('activitySubCategories' , function ($query) use ($search){
                $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%'])
                    ->whereStatus(ActivitySubCategory::STATUS['Active']);
            });
        }
        
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
                'per_page' => $activityCategory['per_page'],
                'next_page_url' => $activityCategory['next_page_url'],
                'prev_page_url' => $activityCategory['prev_page_url']
            ], 'Activity Category List');
        } else {
            return $this->sendResponse($results, 'Activity Category List');
        }
    }

    public function getDetails(Request $request)
    {
        $activityCategory = ActivityCategory::whereId($request->id)->first();

        if (!isset($activityCategory) || empty($activityCategory)) {
            return $this->sendError('activity Category does not exists.');
        }

        return $this->sendResponse($activityCategory, 'Activity category details.');
    }

    public function addActivityCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            $activityCategory = new ActivityCategory();
            $activityCategory->name = $request->name;
            $activityCategory->created_ip = $request->ip();
            $activityCategory->updated_ip = $request->ip();

            if (!$activityCategory->save()) {
                return $this->sendError('Something went wrong while creating the activity category.');
            }

            return $this->sendResponse($activityCategory, 'Activity category created successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateActivityCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
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

            return $this->sendResponse($activityCategory, 'Activity category details updated successfully.');

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeStatus(Request $request)
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

            $activityCategory = ActivityCategory::whereId($request->id)->first();

            if (!isset($activityCategory) || empty($activityCategory)) {
                return $this->sendError('Activity category dose not exists.');
            }
            
            $activityCategory->status = $request->status;
            $activityCategory->save();

            if ($activityCategory->status == ActivityCategory::STATUS['Deleted']) {
                $activityCategory->delete();
            }

            return $this->sendResponse($activityCategory, 'Status changed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
