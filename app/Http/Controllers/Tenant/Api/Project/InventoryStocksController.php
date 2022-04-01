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
use App\Models\Tenant\ProjectRaisingMaterialRequest;
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

        $query = ProjectInventory::with('project', 'materialType', 'unitType')
            ->whereProjectId($request->project_id ?? '')
            ->whereStatus(ProjectInventory::STATUS['Active'])
            ->orderby('id', $orderBy);

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

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
                'total' => $totalQuery,
                'per_page' => $projectInventory['per_page'],
                'next_page_url' => ltrim(str_replace($projectInventory['path'], "", $projectInventory['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($projectInventory['path'], "", $projectInventory['prev_page_url']), "?cursor=")
            ], 'Project material List.');
        } else {
            return $this->sendResponse($results, 'Project inventory material List.');
        }
    }

    public function updateMinimunQuntity(Request $request, $projectInventoryId = null)
    {
        $validator = Validator::make($request->all(), [
            'minimum_quantity' => 'required',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                return $this->sendError('Validation Error.', [$key => $value[0]]);
            }
        }

        $projectInventory = ProjectInventory::whereId($request->projectInventoryId)->first();

        if (!isset($projectInventory) && empty($projectInventory)) {
            return $this->sendError('Project inventory does not exist.');
        }

        if ($request->filled('minimum_quantity')) $projectInventory->minimum_quantity = $request->minimum_quantity;

        if (!$projectInventory->save()) {
            return $this->sendError('Something went wrong while updating the project inventory');
        }

        return $this->sendResponse($projectInventory, 'Project inventory updated successfully.');
    }
}
