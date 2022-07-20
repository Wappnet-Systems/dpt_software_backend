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
use Carbon\Carbon;
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

    public function getDateWiseActivityManforces(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $projectActivity = ProjectActivity::select('id', 'project_id', 'project_main_activity_id', 'activity_sub_category_id', 'manforce_type_id', 'name', 'start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'location', 'level', 'actual_area', 'completed_area', 'unit_type_id', 'cost', 'scaffold_requirement', 'helper', 'status', 'productivity_rate', 'created_by', 'sort_by')
                    ->whereProjectId($request->project_id ?? '');

                if (isset($request->date) && !empty($request->date)) {
                    $projectActivity = $projectActivity->where('start_date', '<=', date('Y-m-d', strtotime($request->date)))
                        ->where('end_date', '>=', date('Y-m-d', strtotime($request->date)));
                }

                $projectActivity = $projectActivity->get()->toArray();

                if (isset($projectActivity) && !empty($projectActivity)) {
                    foreach ($projectActivity as $proActKey => $proActVal) {
                        $projectActivity[$proActKey]['project_manforce'] = ProjectManforce::/* with([
                                'allocatedManforce' => function($query) use($proActVal) {
                                    $query->whereProjectActivityId($proActVal->id);
                                }
                            ])
                            -> */select('id', 'project_id', 'manforce_type_id', 'total_manforce', 'cost', 'cost_type')
                            ->whereProjectId($proActVal['project_id'] ?? '')
                            // ->allocatedManforces('allocatedManforce', $proActVal->id)
                            ->orderby('id', 'desc')
                            ->get()
                            ->toArray();

                        foreach ($projectActivity[$proActKey]['project_manforce'] as $key => $value) {
                            $projectActivity[$proActKey]['project_manforce'][$key]['allocated_manforce'] = ProjectActivityAllocateManforce::select('id', 'project_activity_id', 'project_manforce_id', 'date', 'total_assigned', 'total_planned', 'is_overtime', 'total_work', 'total_cost', 'productivity_rate', 'assign_by')
                                ->whereProjectActivityId($proActVal['id'])
                                ->whereProjectManforceId($value['id'])
                                ->where('date', date('Y-m-d', strtotime($request->date)))
                                ->where('is_overtime', false)
                                ->first();

                            if (!isset($projectActivity[$proActKey]['project_manforce'][$key]['allocated_manforce']) || empty($projectActivity[$proActKey]['project_manforce'][$key]['allocated_manforce'])) {
                                $projectActivity[$proActKey]['project_manforce'][$key]['allocated_manforce']['total_planned'] = 0;
                                $projectActivity[$proActKey]['project_manforce'][$key]['allocated_manforce']['total_assigned'] = 0;
                            }
                        }
                    }
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

    public function updateActivityAllocationManforce(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'request_date' => 'required',
                    'allocated_manforce' => 'required'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }
                
                $request->merge(['allocated_manforce' => json_decode(base64_decode($request->allocated_manforce), true)]);

                foreach ($request->allocated_manforce as $allocatedManforce) {
                    foreach ($allocatedManforce['project_manforce'] as $manforceValue) {
                        if (isset($manforceValue['allocated_manforce']['id']) && !empty($manforceValue['allocated_manforce']['id'])) {
                            $activityAllocatedManforce = ProjectActivityAllocateManforce::whereId($manforceValue['allocated_manforce']['id'])->first();
                            $activityAllocatedManforce->total_assigned = $manforceValue['allocated_manforce']['total_assigned'];
                            $activityAllocatedManforce->total_planned = $manforceValue['allocated_manforce']['total_planned'];
                            $activityAllocatedManforce->updated_ip = $request->ip();
                            $activityAllocatedManforce->save();
                        } else {
                            if (empty($manforceValue['allocated_manforce']['total_assigned']) && empty($manforceValue['allocated_manforce']['total_planned'])) {
                                continue;
                            }

                            $activityAllocatedManforce = new ProjectActivityAllocateManforce();
                            $activityAllocatedManforce->project_activity_id = $allocatedManforce['id'];
                            $activityAllocatedManforce->project_manforce_id = $manforceValue['id'];
                            $activityAllocatedManforce->date = date('Y-m-d', strtotime($request->request_date));
                            $activityAllocatedManforce->is_overtime = false;
                            $activityAllocatedManforce->total_assigned = $manforceValue['allocated_manforce']['total_assigned'];
                            $activityAllocatedManforce->total_planned = $manforceValue['allocated_manforce']['total_planned'];
                            $activityAllocatedManforce->assign_by = $user->id;
                            $activityAllocatedManforce->created_ip = $request->ip();
                            $activityAllocatedManforce->updated_ip = $request->ip();

                            if ($activityAllocatedManforce->save()) {
                                $projectActivityAllocate = ProjectActivityAllocateManforce::whereProjectActivityId($allocatedManforce['id'])
                                    ->orderBy('date', 'asc')
                                    ->limit(1)
                                    ->first();
                                
                                if (isset($projectActivityAllocate) && !empty($projectActivityAllocate)) {
                                    $projectActivity = ProjectActivity::whereId($projectActivityAllocate->project_activity_id)->first();

                                    $startDate = new Carbon($projectActivity->start_date);
                                    $endDate = new Carbon($projectActivity->end_date);
                                    $duration = $startDate->diffInDays($endDate);

                                    $actualStartDate = new Carbon($projectActivityAllocate->date);
                                    $projectActivity->actual_start_date = $actualStartDate->format('Y-m-d H:i:s');

                                    $actualEndDate = $actualStartDate->addDays($duration);
                                    $projectActivity->actual_end_date = $actualEndDate->format('Y-m-d H:i:s');

                                    $projectActivity->save();
                                }
                            }
                        }
                    }
                }
                
                return $this->sendResponse([], 'Project activities allocate manforce update successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
