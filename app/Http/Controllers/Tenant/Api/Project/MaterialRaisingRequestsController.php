<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectInventory;
use App\Models\Tenant\ProjectMaterialRaisingRequest;
use App\Helpers\AppHelper;

class MaterialRaisingRequestsController extends Controller
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

    public function getMaterialRaisingRequests(Request $request)
    {
    }

    public function getMaterialRaisingRequestDetails(Request $request)
    {
    }

    public function addMaterialRaisingRequest(Request $request)
    {
    }

    public function updateMaterialRaisingRequest(Request $request)
    {
    }

    public function deleteMaterialRaisingRequest(Request $request)
    {
    }

    public function changeMaterialRaisingRequestStatus(Request $request)
    {
    }
}
