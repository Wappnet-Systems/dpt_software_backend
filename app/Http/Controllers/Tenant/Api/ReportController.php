<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\ProjectAssignedUser;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Http\Request;
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
}
