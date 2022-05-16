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
use Illuminate\Support\Facades\Log;

class ActivitySubCategoriesController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.', [], 401);
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

    public function getActivitySubCategory(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ActivitySubCategory::with('activityCategory', 'unitType')
            ->whereStatus(ActivitySubCategory::STATUS['Active'])
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);

            $query = $query->orWhereHas('activityCategory', function ($query) use ($search) {
                $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%'])
                    ->whereStatus(ActivityCategory::STATUS['Active']);
            });
        }

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

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
                'total' => $totalQuery,
                'per_page' => $subActivityCategory['per_page'],
                'next_page_url' => ltrim(str_replace($subActivityCategory['path'], "", $subActivityCategory['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($subActivityCategory['path'], "", $subActivityCategory['prev_page_url']), "?cursor=")
            ], 'Sub Activity Category List');
        } else {
            return $this->sendResponse($results, 'Activity Category List');
        }
    }

    public function getDetails(Request $request)
    {
        $subActivityCategory = ActivitySubCategory::select('id', 'activity_category_id', 'name', 'unit_type_id', 'status')
            ->whereId($request->id)
            ->first();

        if (!isset($subActivityCategory) || empty($subActivityCategory)) {
            return $this->sendError('Sub activity Category does not exists.');
        }

        return $this->sendResponse($subActivityCategory, 'Sub activity category details.');
    }

    public function addActivitySubCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'activity_category_id' => 'required|exists:activity_categories,id',
                'unit_type_id' => 'required|exists:unit_types,id',
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                }
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

            return $this->sendResponse([], 'Sub activity category created successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateActivitySubCategory(Request $request, $id = null)
    {
        try {
            $validator = Validator::make($request->all(), [
                'activity_category_id' => 'required|exists:activity_categories,id',
                'unit_type_id' => 'required|exists:unit_types,id',
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                }
            }

            $subActivityCategory = ActivitySubCategory::whereId($request->id)->first();

            if (!isset($subActivityCategory) || empty($subActivityCategory)) {
                return $this->sendError('Sub activity category dose not exists.');
            }

            if ($request->filled('name')) $subActivityCategory->name = $request->name;
            if ($request->filled('activity_category_id')) $subActivityCategory->activity_category_id = $request->activity_category_id;
            if ($request->filled('unit_type_id')) $subActivityCategory->unit_type_id = $request->unit_type_id;

            if (!$subActivityCategory->save()) {
                return $this->sendError('Something went wrong while updating the sub activity category.');
            }

            return $this->sendResponse([], 'Sub activity category details updated successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeStatus(Request $request, $id = null)
    {
        try {
            $subActivityCategory = ActivitySubCategory::whereId($request->id)->first();

            if (!isset($subActivityCategory) || empty($subActivityCategory)) {
                return $this->sendError('Sub activity category dose not exists.');
            }

            if (!in_array($request->status, ActivityCategory::STATUS)) {
                return $this->sendError('Invalid status requested.');
            }

            $subActivityCategory->deleted_at = null;
            $subActivityCategory->status = $request->status;
            $subActivityCategory->save();

            if ($subActivityCategory->status == ActivitySubCategory::STATUS['Deleted']) {
                $subActivityCategory->delete();
            }

            return $this->sendResponse($subActivityCategory, 'Status changed successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
