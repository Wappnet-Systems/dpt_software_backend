<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\ProjectActivityAllocateManforce;
use App\Models\Tenant\ProjectManforce;
use Carbon\Carbon;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ManforceOvertimeController extends Controller
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

    public function getManforceOvertimeByDate(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $projectActivity = ProjectActivity::with('manforceType')
                    ->select('id', 'project_id', 'project_main_activity_id', 'activity_sub_category_id', 'manforce_type_id', 'name', 'start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'location', 'level', 'actual_area', 'completed_area', 'unit_type_id', 'cost', 'scaffold_requirement', 'helper', 'status', 'productivity_rate', 'created_by', 'sort_by')
                    ->whereProjectId($request->project_id ?? '');

                if (isset($request->date) && !empty($request->date)) {
                    $projectActivity = $projectActivity->where('start_date', '<=', date('Y-m-d', strtotime($request->date)))
                        ->where('actual_end_date', '>=', date('Y-m-d', strtotime($request->date)));
                }

                $projectActivity = $projectActivity->get()->toArray();

                foreach ($projectActivity as $proActKey => $proActVal) {
                    $projectActivity[$proActKey]['project_manforce'] = ProjectManforce::with([
                            'allocatedManforce' => function($query) use($proActVal) {
                                $query->whereProjectActivityId($proActVal['id'])
                                    ->where('is_overtime', true);
                            }
                        ])
                        ->select('id', 'project_id', 'manforce_type_id', 'total_manforce', 'cost', 'cost_type')
                        ->whereProjectId($proActVal['project_id'] ?? '')
                        ->whereManforceTypeId($proActVal['manforce_type_id'] ?? '')
                        ->orderby('id', 'desc')
                        ->first();

                    if (isset($projectActivity[$proActKey]['project_manforce']) && !empty($projectActivity[$proActKey]['project_manforce'])) {
                        $projectActivity[$proActKey]['project_manforce'] = $projectActivity[$proActKey]['project_manforce']->toArray();

                        if (empty($projectActivity[$proActKey]['project_manforce']['allocated_manforce'])) {
                            $projectActivity[$proActKey]['project_manforce']['allocated_manforce']['total_assigned'] = null;
                            $projectActivity[$proActKey]['project_manforce']['allocated_manforce']['overtime_hours'] = null;
                            $projectActivity[$proActKey]['project_manforce']['allocated_manforce']['total_work'] = null;
                        }
                    } else {
                        unset($projectActivity[$proActKey]);
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

    public function updateManforceOvertimeByDate(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'request_date' => 'required',
                    'activities' => 'required'

                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }
                
                $request->merge(['activities' => json_decode(base64_decode($request->activities), true)]);

                foreach ($request->activities as $activity) {
                    $project = Project::whereId($activity['project_id'])->first();
                    $proActivity = ProjectActivity::whereId($activity['id'])->first();
                    $projectManforce = ProjectManforce::whereId($activity['project_manforce']['id'])->first();

                    $overtime = new ProjectActivityAllocateManforce();

                    if (isset($activity['project_manforce']['allocated_manforce']['id']) && !empty($activity['project_manforce']['allocated_manforce']['id'])) {
                        $overtime = ProjectActivityAllocateManforce::whereId($activity['project_manforce']['allocated_manforce']['id'])->first();
                        $overtime->updated_ip = $request->ip();
                    } else {
                        if (empty($activity['project_manforce']['allocated_manforce']['total_assigned']) && empty($activity['project_manforce']['allocated_manforce']['overtime_hours'])) {
                            continue;
                        }

                        $overtime->created_ip = $request->ip();
                        $overtime->updated_ip = $request->ip();
                    }

                    $workingStartTime = Carbon::parse($project->working_start_time);
                    $workingEndTime = Carbon::parse($project->working_end_time);
                    $duration = $workingStartTime->diffInHours($workingEndTime);

                    $overtime->project_activity_id = $activity['id'];
                    $overtime->project_manforce_id = $activity['project_manforce']['id'];
                    $overtime->date = date('Y-m-d', strtotime($request->request_date));
                    $overtime->total_assigned = $activity['project_manforce']['allocated_manforce']['total_assigned'] ?? 0;
                    $overtime->total_planned = 0;
                    $overtime->is_overtime = true;
                    $overtime->overtime_hours = $activity['project_manforce']['allocated_manforce']['overtime_hours'];
                    $overtime->total_work = $activity['project_manforce']['allocated_manforce']['total_work'];

                    $overtime->total_cost = AppHelper::calculateManforeCost(
                        $projectManforce->cost,
                        $projectManforce->cost_type,
                        $activity['project_manforce']['allocated_manforce']['total_assigned'],
                        $duration,
                        $activity['project_manforce']['allocated_manforce']['overtime_hours']
                    );

                    // Activity Productivity = (Total output the manforce) / (Total # of hours worked by the workforce)
                    $overtime->productivity_rate = round($overtime->total_work / $duration, 2);

                    $overtime->assign_by = $user->id;
                    $overtime->created_ip = $request->ip();
                    $overtime->created_ip = $request->ip();

                    $proActivity->completed_area = ($proActivity->completed_area - $overtime->getOriginal('total_work')) + $overtime->total_work;
                    $proActivity->save();

                    $overtime->save();
                }
                
                return $this->sendResponse([], 'Project manforce overtime update successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
