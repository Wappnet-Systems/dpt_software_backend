<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\ProjectActivityAllocateManforce;
use App\Models\Tenant\ProjectAssignedUser;
use App\Models\Tenant\ProjectManforce;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
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

    public function KpiReports(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {

                $assignedProjectIds = ProjectAssignedUser::whereUserId($user->id)
                    ->pluck('project_id');

                $getStatistics = [];

                if (isset($assignedProjectIds) && count($assignedProjectIds)) {
                    $getStatistics['projects']['yet_to_start'] = Project::whereIn('id', $assignedProjectIds)->whereStatus(Project::STATUS['Yet to Start'])->count();
                    $getStatistics['projects']['in_progress'] = Project::whereIn('id', $assignedProjectIds)->whereStatus(Project::STATUS['In Progress'])->count();
                    $getStatistics['projects']['completed'] = Project::whereIn('id', $assignedProjectIds)->whereStatus(Project::STATUS['Completed'])->count();

                    foreach ($assignedProjectIds as $key => $projectId) {
                        $getStatistics['projects_activities'][$key]['project_name'] = Project::where('id', $projectId)->value('name');
                        $getStatistics['projects_activities'][$key]['Pending'] = ProjectActivity::where('project_id', $projectId)->whereStatus(ProjectActivity::STATUS['Pending'])->count();
                        $getStatistics['projects_activities'][$key]['Start'] = ProjectActivity::where('project_id', $projectId)->whereStatus(ProjectActivity::STATUS['Start'])->count();
                        $getStatistics['projects_activities'][$key]['Hold'] = ProjectActivity::where('project_id', $projectId)->whereStatus(ProjectActivity::STATUS['Hold'])->count();
                        $getStatistics['projects_activities'][$key]['Completed'] = ProjectActivity::where('project_id', $projectId)->whereStatus(ProjectActivity::STATUS['Completed'])->count();
                    }

                    if (isset($getStatistics) && !empty($getStatistics)) {
                        return $this->sendResponse($getStatistics, 'Project reports.');
                    } else {
                        return $this->sendError('No project report found.', [], 200);
                    }
                } else {
                    return $this->sendError('Project does not exists.', [], 400);
                }
            } else {
                return $this->sendError('User does not exists.', [], 400);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function availableManpower(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {

                $getProjectManpower = [];

                $projects = Project::select('id', 'uuid', 'name', 'logo');
                if (isset($request->project_id) && !empty($request->project_id)) {
                    $projects = $projects->whereId($request->project_id ?? '');
                }
                $projects = $projects->get();

                if (isset($projects) && !empty($projects)) {
                    foreach ($projects as $prKey => $projectValue) {
                        $getProjectManpower['projects'][$prKey] = $projectValue->toArray();

                        $totalManpower = ProjectManforce::with(['manforce', 'allocatedManpower' => function ($query) use ($request) {
                            $query->select('id', 'date', 'project_manforce_id', DB::raw("SUM(total_assigned) as working_manpower"))
                                ->groupBy('id', 'date');
                            if (!empty($request->from_date) && !empty($request->to_date)) {
                                $query->whereDate('date', '>=', date('Y-m-d', strtotime($request->from_date)))
                                    ->whereDate('date', '<=', date('Y-m-d', strtotime($request->to_date)));
                            }
                        }])
                            ->select('id', 'project_id', 'manforce_type_id', 'total_manforce', 'cost', 'cost_type')
                            ->whereProjectId($getProjectManpower['projects'][$prKey]['id'] ?? '')
                            ->get();

                        foreach ($totalManpower as $value) {
                            foreach ($value->allocatedManpower as $manpowerValue) {
                                $workingManpower = !empty($manpowerValue) ? (int) $manpowerValue->working_manpower : null;
                                $availableManpower = $value->total_manforce - $workingManpower;

                                $getProjectManpower['projects'][$prKey][$manpowerValue->date][$value->manforce->name]['total_manpower'] = $value->total_manforce;
                                $getProjectManpower['projects'][$prKey][$manpowerValue->date][$value->manforce->name]['working_manpower'] = !empty($workingManpower) ? $workingManpower : null;
                                $getProjectManpower['projects'][$prKey][$manpowerValue->date][$value->manforce->name]['available_manpower'] = !empty($availableManpower) ? $availableManpower : null;
                            }
                        }
                    }
                } else {
                    return $this->sendError('Project does not exists.', [], 400);
                }

                if (isset($getProjectManpower) && !empty($getProjectManpower)) {
                    return $this->sendResponse($getProjectManpower, 'Project manpower list.');
                } else {
                    return $this->sendError('No project manpower found.', [], 200);
                }
            } else {
                return $this->sendError('User does not exists.', [], 400);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
