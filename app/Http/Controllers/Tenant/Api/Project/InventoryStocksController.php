<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectInventory;
use App\Helpers\AppHelper;

class InventoryStocksController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.');
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

    public function getInventoryStocks(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectInventory::with('project','materials','unitType')->whereStatus(ProjectInventory::STATUS['Active'])
            ->orderby('id', $orderBy);

        if ($request->exists('cursor')) {
            $projectInventory = $query->cursorPaginate($limit)->toArray();
        } else {
            $projectInventory['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($projectInventory['data'])) {
            $results = $projectInventory['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'per_page' => $projectInventory['per_page'],
                'next_page_url' => $projectInventory['next_page_url'],
                'prev_page_url' => $projectInventory['prev_page_url']
            ], 'Project material List.');
        } else {
            return $this->sendResponse($results, 'Project material List.');
        }
    }
}
