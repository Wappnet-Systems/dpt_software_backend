<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\ProjectActivityTrack;
use App\Models\Tenant\ProjectManforce;
use Carbon\Carbon;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ActivitiesDailyProgressController extends Controller
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

    public function getActivitiesDailyProgress(Request $request)
    {
        $user = $request->user();

        $query = ProjectActivityTrack::with('projectActivity')
            ->whereHas('projectActivity', function($query) use($request) {
                $query->whereProjectId($request->project_id);
            })
            ->select('id', 'project_activity_id', 'date', 'completed_area', 'status', 'comment', 'reason', 'responsible_party', 'created_by');

        if (isset($request->date) && !empty($request->date)) {
            $query->where('date', date('Y-m-d', strtotime($request->date)));
        }

        $dailyActivitiesTrack = $query->get();

        return $this->sendResponse($dailyActivitiesTrack, 'Daily Activity Tracking List');
    }

    public function updateActivitiesDailyProgress(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'request_date' => 'required',
                    'activities_track' => 'required'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }
                
                $request->merge(['activities_track' => json_decode(base64_decode($request->activities_track), true)]);

                foreach ($request->activities_track as $activityTrack) {
                    $actTrack = ProjectActivityTrack::whereId($activityTrack['id'])->first();

                    if (isset($actTrack) && !empty($actTrack)) {
                        $actTrack->completed_area = $activityTrack['completed_area'];
                        $actTrack->comment = $activityTrack['comment'] ?? null;
                        $actTrack->reason = $activityTrack['reason'] ?? null;
                        $actTrack->created_by = $user->id;
                        $actTrack->created_ip = $request->ip();
                        $actTrack->updated_ip = $request->ip();

                        if ($actTrack->isDirty('completed_area')) {
                            $proActivity = ProjectActivity::with([
                                    'project',
                                    'allocatedManforce' => function($query) {
                                        $query->whereIsOvertime(false);
                                    }
                                ])
                                ->whereId($activityTrack['project_activity']['id'])
                                ->first();

                            if (isset($proActivity) && !empty($proActivity)) {
                                $proActivity->completed_area = ($proActivity->completed_area - $actTrack->getOriginal('completed_area')) + $actTrack->completed_area;
                                $proActivity->save();
                                
                                $actTrack->save();

                                if (!empty($proActivity->allocatedManforce)) {
                                    $projectManforce = ProjectManforce::whereId($proActivity->allocatedManforce->project_manforce_id)->first();

                                    $workingStartTime = Carbon::parse($proActivity->project->working_start_time);
                                    $workingEndTime = Carbon::parse($proActivity->project->working_end_time);
                                    $duration = $workingStartTime->diffInHours($workingEndTime);
    
                                    // Activity Productivity = (Total output the manforce) / (Total # of hours worked by the workforce)
                                    $proActivity->allocatedManforce->productivity_rate = round($actTrack->completed_area / $duration, 2);

                                    // Total work done by manforce for the activity
                                    $proActivity->allocatedManforce->total_work = ProjectActivityTrack::whereProjectActivityId($proActivity->id)->sum('completed_area');
                                
                                    // Total cost of manforce for the activity
                                    $proActivity->allocatedManforce->total_cost = AppHelper::calculateManforeCost(
                                        $projectManforce->cost,
                                        $projectManforce->cost_type,
                                        $proActivity->allocatedManforce->total_assigned,
                                        $duration,
                                        null
                                    );
                                    
                                    $proActivity->allocatedManforce->save();
                                }
                            }
                        }
                    }
                }
                
                return $this->sendResponse([], 'Activity daily tracking update successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
