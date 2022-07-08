<?php

namespace App\Console\Commands;

use App\Helpers\TenantUtils;
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\ProjectActivityAllocateManforce;
use App\Models\Tenant\ProjectActivityTrack;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ActivityProductivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dpt:org:activity:productivity {org_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate average productivity of current running project activities';

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

        // $currDateTime = new \DateTime('2022-07-18 11:00:00');
        $currDateTime = new \DateTime('NOW', new \DateTimeZone('UTC'));

        // One day before date
        $oneDayBeforeDateTime = $currDateTime->sub(new \DateInterval('P1D'));
        $oneDayBeforeDate = $oneDayBeforeDateTime->format('Y-m-d');

        Log::info("ActivityProductivity Start At " . $oneDayBeforeDateTime->format('Y-m-d H:i:s'));

        $activities = ProjectActivity::where('start_date', '<=', $oneDayBeforeDate)
            ->where('actual_end_date', '>=', $oneDayBeforeDate)
            ->get();

        if (isset($activities) && !empty($activities)) {
            foreach ($activities as $activityKey => $activityVal) {
                $allocatedManforce = ProjectActivityAllocateManforce::whereProjectActivityId($activityVal->id);

                $activityVal->productivity_rate = 0;

                if ($allocatedManforce->count()) {
                    $activityVal->productivity_rate = $allocatedManforce->sum('productivity_rate') / $allocatedManforce->count();
                }
            }
        }

        Log::info("ActivityProductivity End At " . $oneDayBeforeDateTime->format('Y-m-d H:i:s'));

        return 0;
    }
}
