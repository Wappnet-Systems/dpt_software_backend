<?php

namespace App\Console\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\System\Organization;
use Illuminate\Support\Facades\Artisan;

class OrganizationCrons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dpt:org:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run cron for all organization';

    /**
     * Schedule
     */
    protected $_schedule = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->_schedule = new Schedule();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info("------: Start Organization Crons :------");
        
        $organizations = Organization::whereStatus(Organization::STATUS['Active'])->get();

        if (!isset($organizations) || empty($organizations)) {
            Log::info("-: No Organization Found :-");

            return 0;
        }

        foreach ($organizations as $organization) {
            Log::info("Starting ( $organization->id ) cron in background");

            // exec("php artisan dpt:org:acitivity:progress $organization->id > /dev/null &");

            Artisan::call('dpt:org:acitivity:progress', ['org_id' => $organization->id]);
            // $this->_schedule->command(ActivityProgress::class, ['org_id' => $organization->id]);
            // $this->_schedule->command('dpt:org:acitivity:progress 1');

            Log::info("Ending ( $organization->id ) cron in background");
        }

        Log::info("------: End Organization Crons :------");
        
        return 0;
    }
}
