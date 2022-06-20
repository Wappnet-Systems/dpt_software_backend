<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectManforce;
use App\Models\Tenant\ProjectActivityAllocateManforce;
use App\Helpers\AppHelper;
use App\Models\Tenant\ProjectActivity;
use Illuminate\Support\Facades\Log;

class ManforcesAllocationController extends Controller
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

    public function getAllocateManforces(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectActivityAllocateManforce::with('projectActivity')
            ->whereProjectActivityId($request->project_activity_id ?? '')
            ->orderby('id', $orderBy);

        if (isset($request->project_manforce_id) && !empty($request->project_manforce_id)) {
            $query->whereProjectManforceId($request->project_manforce_id ?? '');
        }

        if (isset($request->date) && !empty($request->date)) {
            $query = $query->whereDate('date', '>=', date('Y-m-d', strtotime($request->date)))
                ->whereDate('date', '<=', date('Y-m-d', strtotime($request->date)));
        }

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $allocatedManforces = $query->cursorPaginate($limit)->toArray();
        } else {
            $allocatedManforces['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($allocatedManforces['data'])) {
            $results = $allocatedManforces['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $allocatedManforces['per_page'],
                'next_page_url' => ltrim(str_replace($allocatedManforces['path'], "", $allocatedManforces['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($allocatedManforces['path'], "", $allocatedManforces['prev_page_url']), "?cursor=")
            ], 'Activity allocated manforces list.');
        } else {
            return $this->sendResponse($results, 'Activity allocated manforces list.');
        }
    }

    public function getAllocateManforcesDetails(Request $request, $id = null)
    {
        $allocatedManforces = ProjectActivityAllocateManforce::with('projectActivity', 'projectManforce')
            ->whereId($request->id)
            ->select('id', 'project_activity_id', 'project_manforce_id', 'date', 'start_time', 'end_time', 'total_assigned', 'total_work', 'total_cost', 'productivity_rate', 'assign_by')
            ->first();

        if (!isset($allocatedManforces) || empty($allocatedManforces)) {
            return $this->sendError('Manforce not allocated to the activity.', [], 404);
        }

        return $this->sendResponse($allocatedManforces, 'Activity allocated manforce details.');
    }

    public function addAllocateManforces(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_activity_id' => 'required|exists:projects_activities,id',
                    'project_manforce_id' => 'required|exists:projects_manforces,id',
                    'date' => 'required|date_format:Y-m-d',
                    'total_assigned' => 'required',
                    'total_planned' => 'required',
                    'is_overtime' => 'required|boolean',
                    // 'total_cost' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $checkTotalManforce = ProjectManforce::whereId($request->project_manforce_id)
                    ->select('total_manforce')->first();

                if ($request->total_assigned > $checkTotalManforce->total_manforce) {
                    return $this->sendError('Requested manforce are not available.', [], 400);
                }

                $checkAllocatedManforces = ProjectActivityAllocateManforce::whereProjectActivityId($request->project_activity_id)
                    ->whereProjectManforceId($request->project_manforce_id)
                    ->whereDate('date', date('Y-m-d', strtotime($request->date)))
                    // ->whereTime('start_time', date('H:i:s', strtotime($request->start_time)))
                    // ->whereTime('end_time', date('H:i:s', strtotime($request->end_time)))
                    ->first();

                if (isset($checkAllocatedManforces) && !empty($checkAllocatedManforces)) {
                    $requestManforce = $checkTotalManforce->total_manforce - $checkAllocatedManforces->total_assigned;

                    if ($request->total_assigned > $requestManforce) {
                        return $this->sendError('Total manforce available is ' . $requestManforce . ' so please enter the available manforces.', [], 400);
                    }
                }

                $allocatedManforces = ProjectActivityAllocateManforce::whereProjectActivityId($request->project_activity_id)
                    ->whereProjectManforceId($request->project_manforce_id)
                    ->whereDate('date', date('Y-m-d', strtotime($request->date)))
                    // ->whereTime('start_time', date('H:i:s', strtotime($request->start_time)))
                    ->first();

                if (isset($allocatedManforces) && !empty($allocatedManforces)) {
                    $allocatedManforces->total_assigned = $request->total_assigned;
                } else {
                    $allocatedManforces = new ProjectActivityAllocateManforce();
                    $allocatedManforces->project_activity_id = $request->project_activity_id;
                    $allocatedManforces->project_manforce_id = $request->project_manforce_id;
                    $allocatedManforces->date = date('Y-m-d', strtotime($request->date));
                    $allocatedManforces->total_assigned = $request->total_assigned;
                    $allocatedManforces->total_planned = $request->total_planned;
                    $allocatedManforces->is_overtime = !empty($request->is_overtime) ? $request->is_overtime : false;
                    $allocatedManforces->total_cost = $request->total_cost ?? null;
                    $allocatedManforces->created_ip = $request->ip();
                }

                $allocatedManforces->assign_by = $user->id;
                $allocatedManforces->updated_ip = $request->ip();

                if (!$allocatedManforces->save()) {
                    return $this->sendError('Something went wrong while creating the allocating manforces to activity.', [], 500);
                }

                return $this->sendResponse([], 'Manforces allocating into activity successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateAllocateManforces(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'date' => 'required|date_format:Y-m-d',
                    'total_assigned' => 'required',
                    'total_planned' => 'required',
                    'is_overtime' => 'required|boolean',
                    // 'total_cost' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $allocatedManforces = ProjectActivityAllocateManforce::whereId($request->id)
                    ->first();

                if (!isset($allocatedManforces) || empty($allocatedManforces)) {
                    return $this->sendError('Manforce not allocated to the activity.', [], 400);
                }

                $checkTotalManforce = ProjectManforce::whereId($allocatedManforces->project_manforce_id)
                    ->select('total_manforce')
                    ->first();

                if ($request->total_assigned > $checkTotalManforce->total_manforce) {
                    return $this->sendError('Requested manforce are not available.', [], 400);
                }

                if (date('Y-m-d', strtotime($request->date)) < date('Y-m-d')) {
                    return $this->sendError('You are not update past date manforce allocating activity.', [], 400);
                }

                if (date('Y-m-d') == date('Y-m-d', strtotime($allocatedManforces->date))) {
                    if (date('H:i:s', strtotime($allocatedManforces->start_time)) > date('H:i:s')) {
                        $allocatedManforces->date = date('Y-m-d', strtotime($request->date));
                        $allocatedManforces->total_assigned = $request->total_assigned;
                        $allocatedManforces->total_planned = $request->total_planned;
                        $allocatedManforces->is_overtime = $request->is_overtime;
                        $allocatedManforces->total_cost = $request->total_cost ?? null;
                    }
                } elseif (date('Y-m-d', strtotime($allocatedManforces->date)) >= date('Y-m-d')) {
                    $allocatedManforces->date = date('Y-m-d', strtotime($request->date));
                    $allocatedManforces->total_assigned = $request->total_assigned;
                    $allocatedManforces->total_planned = $request->total_planned;
                    $allocatedManforces->is_overtime = $request->is_overtime;
                    $allocatedManforces->total_cost = $request->total_cost ?? null;
                }

                if (!$allocatedManforces->save()) {
                    return $this->sendError('Something went wrong while updating the manforce allocating activity.', [], 500);
                }

                return $this->sendResponse([], 'Manforce allocating into activity successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function deleteAllocateManforces(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $allocatedManforces = ProjectActivityAllocateManforce::whereId($request->id)
                    ->first();

                if (!isset($allocatedManforces) || empty($allocatedManforces)) {
                    return $this->sendError('Manforce not allocated to the activity.', [], 404);
                }

                if (date('Y-m-d') == date('Y-m-d', strtotime($allocatedManforces->date))) {
                    if (date('H:i:s', strtotime($allocatedManforces->start_time)) > date('H:i:s')) {
                        $allocatedManforces->delete();
                    }
                } elseif (date('Y-m-d', strtotime($allocatedManforces->date)) >= date('Y-m-d')) {
                    $allocatedManforces->delete();
                }

                return $this->sendResponse([], 'Allocated manforce removed from activity successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    /** 
     * Activity manpower APIs
     */
    public function getActivityManpower(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $query = ProjectActivityAllocateManforce::with('projectActivity', 'projectManforce')
                    ->orderBy('id', $orderBy);

                $totalQuery = $query;
                $totalQuery = $totalQuery->count();

                if (isset($request->date) && !empty($request->date)) {
                    $query = $query->whereDate('date', date('Y-m-d', strtotime($request->date)));
                }

                if (isset($request->project_activity_id) && !empty($request->project_activity_id)) {
                    $query = $query->whereHas('projectActivity', function ($query) use ($request) {
                        $query->where('id', $request->project_activity_id ?? '')
                            ->where('project_id', $request->project_id ?? '');
                    });
                } else {
                    $query = $query->whereHas('projectActivity', function ($query) use ($request) {
                        $query->where('project_id', $request->project_id);
                    });
                }

                if ($request->exists('cursor')) {
                    $activityManpower = $query->cursorPaginate($limit)->toArray();
                } else {
                    $activityManpower['data'] = $query->get()->toArray();
                }

                $results = [];
                if (!empty($activityManpower['data'])) {
                    $results = $activityManpower['data'];
                }

                if ($request->exists('cursor')) {
                    return $this->sendResponse([
                        'lists' => $results,
                        'total' => $totalQuery,
                        'per_page' => $activityManpower['per_page'],
                        'next_page_url' => ltrim(str_replace($activityManpower['path'], "", $activityManpower['next_page_url']), "?cursor="),
                        'prev_page_url' => ltrim(str_replace($activityManpower['path'], "", $activityManpower['prev_page_url']), "?cursor=")
                    ], 'Activity manpower list.');
                } else {
                    return $this->sendResponse($results, 'Activity allocated manforces list.');
                }
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    /**
     * 
     * Date wise get project activity
     */
    public function getDateWiseActivityMaforces(Request $request)
    {
        try {
            $user = $request->user();
            

            if (isset($user) && !empty($user)) {

                $projectActivity = ProjectActivity::select('id', 'project_id', 'project_main_activity_id', 'activity_sub_category_id', 'manforce_type_id', 'name', 'start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'location', 'level', 'actual_area', 'completed_area', 'unit_type_id', 'cost', 'scaffold_requirement', 'helper', 'status', 'productivity_rate', 'created_by')
                    ->whereProjectId($request->project_id ?? '');

                if (isset($request->date) && !empty($request->date)) {
                    $projectActivity = $projectActivity->whereDate('start_date', '>=', date('Y-m-d', strtotime($request->date)))
                        ->whereDate('end_date', '<=', date('Y-m-d', strtotime($request->date)));
                }

                $projectActivity = $projectActivity->get();

                if (isset($projectActivity) && !empty($projectActivity)) {
                    foreach ($projectActivity as $key => $projectActivityValue) {
                        foreach ($projectActivityValue as $k1 => $k2) {
                            $projectActivity[$key]['project_manforce'] = ProjectManforce::select('id', 'project_id', 'manforce_type_id', 'total_manforce', 'cost', 'cost_type')
                                ->whereProjectId($projectActivityValue->project_id ?? '')
                                ->whereManforceTypeId($projectActivityValue->manforce_type_id ?? '')
                                ->get();

                            if (!empty($k2)) {
                                $projectActivity[$key]['allocated_manforce'] = ProjectActivityAllocateManforce::select('id', 'project_activity_id', 'project_manforce_id', 'date', 'total_assigned', 'total_planned', 'is_overtime', 'total_work', 'total_cost', 'productivity_rate', 'assign_by')
                                    ->whereProjectActivityId($projectActivityValue->id ?? '')
                                    ->first();
                            }
                        }
                    }
                } else {
                    return $this->sendError('Project activity not exists.', [], 400);
                }

                return $this->sendResponse($projectActivity, 'Project activity manforce allocation list.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
