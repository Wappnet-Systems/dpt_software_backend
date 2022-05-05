<?php

namespace App\Http\Controllers\System\Api;

use App\Helpers\AppHelper;
use App\Http\Controllers\Controller;
use App\Models\System\Organization;
use Illuminate\Http\Request;
use App\Models\System\PunchDetail;
use App\Models\System\User;
use App\Models\Tenant\Project;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Support\Facades\Log;

class PunchDetailController extends Controller
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

    public function punchInOut(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $orgUser = User::where('user_uuid', $user->user_uuid)
                    ->where('status', User::STATUS['Active'])
                    ->where('role_id', '!=', User::USER_ROLE['SUPER_ADMIN'])
                    ->first();

                if (!isset($orgUser) || empty($orgUser)) {
                    return $this->sendError('User does not exists.');
                }

                $projectId = Project::select('id', 'uuid', 'name', 'logo', 'address', 'lat', 'long', 'city', 'state', 'country', 'zip_code', 'start_date', 'end_date', 'cost', 'status', 'created_by')
                    ->whereUuid($request->project_id)
                    ->first();

                /* echo '<pre>';
                print_r([$user->toArray(), $projectId]);
                echo '</pre>'; */
                die;
            } else {
                return $this->sendError('User does not exists.');
            }
        } catch (\Exception $e) {
            // echo '<pre>'; dd([$e]); echo '</pre>'; die;
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
