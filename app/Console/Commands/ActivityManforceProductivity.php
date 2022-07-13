<?php

namespace App\Console\Commands;

use App\Helpers\TenantUtils;
use App\Models\Tenant\ProjectActivity;
use App\Models\Tenant\ProjectManforce;
use App\Models\Tenant\ProjectManforceProductivity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityManforceProductivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dpt:org:activity:manforce:productivity {org_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate average productivity of manforce by activity sub categories';

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

        // $currDateTime = new \DateTime('2022-07-19 11:00:00');
        $currDateTime = new \DateTime('NOW', new \DateTimeZone('UTC'));

        Log::info("ActivityManforceProductivity Start At " . $currDateTime->format('Y-m-d H:i:s'));

        $aveProductivities = ProjectActivity::whereStatus(ProjectActivity::STATUS['Completed'])
            ->select('activity_sub_category_id', 'manforce_type_id', 'unit_type_id', 'project_id', DB::raw("avg(productivity_rate) as productivity_rate"))
            ->groupBy('activity_sub_category_id', 'manforce_type_id', 'unit_type_id', 'project_id')
            ->get();

        if (isset($aveProductivities) && !empty($aveProductivities)) {
            foreach ($aveProductivities as $avgProdKey => $avgProdValue) {
                $manforceProductivity = ProjectManforceProductivity::whereProjectId($avgProdValue->project_id)
                    ->whereActivitySubCategoryId($avgProdValue->activity_sub_category_id)
                    ->whereManforceTypeId($avgProdValue->manforce_type_id)
                    ->whereUnitTypeId($avgProdValue->unit_type_id)
                    ->first();

                if (!isset($manforceProductivity) || empty($manforceProductivity)) {
                    $manforceProductivity = new ProjectManforceProductivity();
                }

                $manforceProductivity->project_id = $avgProdValue->project_id;
                $manforceProductivity->activity_sub_category_id = $avgProdValue->activity_sub_category_id;
                $manforceProductivity->manforce_type_id = $avgProdValue->manforce_type_id;
                $manforceProductivity->unit_type_id = $avgProdValue->unit_type_id;
                $manforceProductivity->productivity_rate = $avgProdValue->productivity_rate;
                $manforceProductivity->save();
            }
        }

        Log::info("ActivityManforceProductivity End At " . $currDateTime->format('Y-m-d H:i:s'));

        return 0;
    }
}
