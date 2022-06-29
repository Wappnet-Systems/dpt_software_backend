<?php

namespace App\Console\Commands;

use App\Helpers\AppHelper;
use App\Helpers\TenantUtils;
use App\Models\System\User;
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\ProjectActivityAllocateManforce;
use App\Models\Tenant\ProjectActivityTrack;
use App\Models\Tenant\ProjectAssignedUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ActivityProgress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dpt:org:acitivity:progress {org_id}';

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

        // $currDateTime = new \DateTime('2020-08-29 11:00:00 AM');
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
                } else {
                    Log::info("Is Allocated Manforce ( $activityVal->id ) : False");

                    $activityTrack = new ProjectActivityTrack();
                    $activityTrack->project_activity_id = $activityVal->id;
                    $activityTrack->date = $currDate;
                    $activityTrack->status = ProjectActivityTrack::STATUS['Hold'];
                    $activityTrack->responsible_party = $assignedProEng ?? null;

                    if ($activityTrack->save()) {
                        $activityVal->status = ProjectActivity::STATUS['Hold'];
                        $activityVal->save();
                    }
                }
            }
        }

        Log::info("ActivityProgress End At " . $currDateTime->format('Y-m-d H:i:s'));
        
        return 0;
    }
}