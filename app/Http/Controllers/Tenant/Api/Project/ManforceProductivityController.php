<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectManforceProductivity;
use App\Helpers\AppHelper;

class ManforceProductivityController extends Controller
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

    public function getManforceProductivity(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectManforceProductivity::with('')
            ->whereProjectId($request->project_id ?? '')
            ->orderby('id', $orderBy);

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $ManforceProductivity = $query->cursorPaginate($limit)->toArray();
        } else {
            $ManforceProductivity['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($ManforceProductivity['data'])) {
            $results = $ManforceProductivity['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $ManforceProductivity['per_page'],
                'next_page_url' => ltrim(str_replace($ManforceProductivity['path'], "", $ManforceProductivity['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($ManforceProductivity['path'], "", $ManforceProductivity['prev_page_url']), "?cursor=")
            ], 'Manforce productivity list.');
        } else {
            return $this->sendResponse($results, 'Manforce productivity list.');
        }
    }
}
