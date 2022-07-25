<?php

namespace App\Jobs;

use App\Models\System\Organization;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Bus\Queueable;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateOrganizationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $details;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Create new website
            $website = new Website;
            $website->uuid = Organization::generateUuid($this->details['org_domain']);

            if (!app(WebsiteRepository::class)->create($website)) {
                $organization = Organization::whereId($this->details['org_id'])->first();

                $organization->status = Organization::STATUS['Failure'];
                $organization->save();

                Log::error('Something went wrong while creating the organization.');

                return;
            }

            // Create new hostname
            $hostname = new Hostname();
            $hostname->fqdn = $this->details['org_domain'];
            $hostname->website_id = $website->id;

            if (!$hostname->save()) {
                $organization = Organization::whereId($this->details['org_id'])->first();
                $organization->status = Organization::STATUS['Failure'];
                $organization->save();

                Log::error('Something went wrong while creating the organization.');

                return;
            }

            $organization = Organization::whereId($this->details['org_id'])->first();
            $organization->hostname_id = $hostname->id;
            $organization->save();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
