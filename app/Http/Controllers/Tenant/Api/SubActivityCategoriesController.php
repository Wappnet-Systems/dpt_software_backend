<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\System\Organization;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Support\Facades\Config;
use App\Models\Tenant\ActivityCategory;
use App\Models\Tenant\ActivitySubCategory;
use App\Models\Tenant\UnitType;

class SubActivityCategoriesController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
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

    public function getSubActivityCategory(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ActivitySubCategory::with('activityCategory')->whereStatus(ActivitySubCategory::STATUS['Active'])->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);

            $query = $query->orWhereHas('activityCategory', function ($query) use ($search) {
                $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%'])
                    ->whereStatus(ActivityCategory::STATUS['Active']);
            });
        }

        if ($request->exists('cursor')) {
            $subActivityCategory = $query->cursorPaginate($limit)->toArray();
        } else {
            $subActivityCategory['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($subActivityCategory['data'])) {
            $results = $subActivityCategory['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'per_page' => $subActivityCategory['per_page'],
                'next_page_url' => $subActivityCategory['next_page_url'],
                'prev_page_url' => $subActivityCategory['prev_page_url']
            ], 'Activity Category List');
        } else {
            return $this->sendResponse($results, 'Activity Category List');
        }
    }

    public function getDetails(Request $request)
    {
        $subActivityCategory = ActivitySubCategory::whereId($request->id)->select('id', 'activity_category_id', 'name', 'unit_type_id', 'status')->first();

        if (!isset($subActivityCategory) || empty($subActivityCategory)) {
            return $this->sendError('Sub activity Category does not exists.');
        }

        return $this->sendResponse($subActivityCategory, 'sub activity Category details.');
    }

    public function addSubActivityCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'activity_category_id' => 'required',
                'unit_type_id' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }
            $activityCategoryId = ActivityCategory::whereId($request->activity_category_id)->get();
            
            $UnitTypeId = UnitType::whereId($request->activity_category_id)->get();

            if (empty($activityCategoryId)) {
                return $this->sendError('Activity category id does not exist.');
            }
            if (empty($UnitTypeId)) {
                return $this->sendError('Unit type does not exist.');
            }

            $subActivityCategory = new ActivitySubCategory();

            $subActivityCategory->name = $request->name;
            $subActivityCategory->activity_category_id = $request->activity_category_id;
            $subActivityCategory->unit_type_id = $request->unit_type_id;
            $subActivityCategory->created_ip = $request->ip();
            $subActivityCategory->updated_ip = $request->ip();

            if (!$subActivityCategory->save()) {
                return $this->sendError('Something went wrong while creating the sub activity category.');
            }

            return $this->sendResponse($subActivityCategory, 'Sub activity category created successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateSubActivityCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required',
                'activity_category_id' => 'required',
                'unit_type_id' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            $subActivityCategory = ActivitySubCategory::whereId($request->id)->first();
            
            if (!isset($subActivityCategory) || empty($subActivityCategory)) {
                return $this->sendError('Sub activity category dose not exists.');
            }

            $activityCategoryId = ActivityCategory::whereId($request->activity_category_id)->get();
            
            $UnitTypeId = UnitType::whereId($request->activity_category_id)->get();

            if (empty($activityCategoryId)) {
                return $this->sendError('Activity category id does not exist.');
            }
            if (empty($UnitTypeId)) {
                return $this->sendError('Unit type does not exist.');
            }

            if ($request->filled('name')) $subActivityCategory->name = $request->name;
            if ($request->filled('activity_category_id')) $subActivityCategory->activity_category_id = $request->activity_category_id;
            if ($request->filled('unit_type_id')) $subActivityCategory->unit_type_id = $request->unit_type_id;

            if (!$subActivityCategory->save()) {
                return $this->sendError('Something went wrong while updating the sub activity category.');
            }

            return $this->sendResponse($subActivityCategory, 'Sub activity category details updated successfully.');
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

            $subActivityCategory = ActivitySubCategory::whereId($request->id)->first();

            if (!isset($subActivityCategory) || empty($subActivityCategory)) {
                return $this->sendError('Sub activity category dose not exists.');
            }

            $subActivityCategory->status = $request->status;
            $subActivityCategory->save();

            if ($subActivityCategory->status == ActivitySubCategory::STATUS['Deleted']) {
                $subActivityCategory->delete();
            }

            return $this->sendResponse($subActivityCategory, 'Status changed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}