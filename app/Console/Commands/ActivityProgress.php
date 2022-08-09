<?php

namespace App\Console\Commands;

use App\Helpers\AppHelper;
use App\Helpers\TenantUtils;
use App\Jobs\SendPushJob;
use App\Models\System\User;
use App\Models\Tenant\MethodStatement;
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\ProjectActivityAllocateManforce;
use App\Models\Tenant\ProjectActivityTrack;
use App\Models\Tenant\ProjectAssignedUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityProgress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dpt:org:activity:progress {org_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the activity progress based on current date and manforce allocation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $orgId = $this->argument('org_id');

        $organization = app(TenantUtils::class)->changeById($orgId);

        if (!isset($organization) || empty($organization)) {
            Log::error("Unable to change site for ( $orgId )");

            return 0;
        }

        // $currDateTime = new \DateTime('2022-07-18 11:00:00 AM');
        $currDateTime = new \DateTime('NOW', new \DateTimeZone('UTC'));
        $currDate = $currDateTime->format('Y-m-d');

        Log::info("ActivityProgress Start At " . $currDateTime->format('Y-m-d H:i:s'));

        $activities = ProjectActivity::where('start_date', '<=', $currDate)
            ->where('actual_end_date', '>=', $currDate)
            ->get();

        if (isset($activities) && !empty($activities)) {
            $assignedUserIds = ProjectAssignedUser::whereProjectId($activities[0]['project_id'] ?? null)->pluck('user_id');

            AppHelper::setDefaultDBConnection(true);

            $assignedProEng = User::whereIn('id', $assignedUserIds)
                ->where('role_id', USER::USER_ROLE['PROJECT_ENGINEER'])
                ->value('id');

            $assiFormanEng = User::select('id', 'role_id', 'name', 'created_by')
                ->whereIn('id', $assignedUserIds)
                ->whereIn('role_id', [USER::USER_ROLE['ENGINEER'], USER::USER_ROLE['FOREMAN']])
                ->get()->toArray();

            AppHelper::setDefaultDBConnection();

            foreach ($activities as $activityKey => $activityVal) {
                $allocatedManforce = ProjectActivityAllocateManforce::whereProjectActivityId($activityVal->id)
                    ->where('date', $currDate)
                    ->where('total_assigned', '>', 0)
                    ->first();

                if (isset($allocatedManforce) && !empty($allocatedManforce)) {
                    Log::info("Is Allocated Manforce ( $activityVal->id ) : True");

                    $activityTrack = new ProjectActivityTrack();
                    $activityTrack->project_activity_id = $activityVal->id;
                    $activityTrack->date = $currDate;
                    $activityTrack->responsible_party = $assignedProEng ?? null;

                    if ($activityTrack->save()) {
                        $activityVal->status = ProjectActivity::STATUS['Start'];
                        $activityVal->save();
                    }

                    foreach ($assiFormanEng as $assiFormanEngKey => $user) {

                        if ($user['role_id'] == USER::USER_ROLE['ENGINEER']) {
                            $methodStatementAssign = MethodStatement::whereNull('project_activity_id')->get();

                            if (count($methodStatementAssign)) {
                                /** Send Push Notification */
                                $title = 'Method Statement Not Assigned Reminder';

                                $message = 'Today you have work on ' . $activityVal->name . ', but still method statement not assigned to activity';

                                $data = [
                                    'type' => 'Method Statement Not Assigned Reminder',
                                    'data' => $user
                                ];

                                dispatch(new SendPushJob($user, $title, $message, $data, 'User'));
                                /** End of Send Push Notification */
                            }
                        }

                        /** Send Push Notification */
                        $title = 'Activity Reminder';

                        $message = 'Today you have work on ' . $activityVal->name;

                        $data = [
                            'type' => 'Activity Reminder',
                            'data' => $user
                        ];

                        dispatch(new SendPushJob($user, $title, $message, $data, 'User'));
                        /** End of Send Push Notification */
                    }
                } else {
                    Log::info("Is Allocated Manforce ( $activityVal->id ) : False");

                    $activityTrack = new ProjectActivityTrack();
                    $activityTrack->project_activity_id = $activityVal->id;
                    $activityTrack->date = $currDate;
                    $activityTrack->status = ProjectActivityTrack::STATUS['Hold'];
                    $activityTrack->responsible_party = $assignedProEng ?? null;

                    if ($activityTrack->save()) {
                        $activityVal->actual_end_date = date('Y-m-d H:i:s', strtotime($activityVal->actual_end_date . ' +1 day'));
                        $activityVal->status = ProjectActivity::STATUS['Hold'];
                        $activityVal->save();
                    }

                    foreach ($assiFormanEng as $user) {
                        /** Send Push Notification */
                        $title = 'Activity Manfoce Allocation Reminder';

                        $message = 'Today you have work on ' . $activityVal->name . ', but still manfoce allocation not allocated to activity';

                        $data = [
                            'type' => 'Activity Manfoce Allocation Reminder',
                            'data' => $user
                        ];
                        
                        dispatch(new SendPushJob($user, $title, $message, $data, 'User'));
                        /** End of Send Push Notification */
                    }
                }
            }
        }

        Log::info("ActivityProgress End At " . $currDateTime->format('Y-m-d H:i:s'));

        return 0;
    }
}
