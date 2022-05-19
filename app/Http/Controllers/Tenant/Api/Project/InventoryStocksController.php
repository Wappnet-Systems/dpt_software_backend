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
use App\Helpers\AppHelper;
use App\Models\Tenant\Project;
use App\Models\Tenant\ProjectAssignedUser;

class InventoryStocksController extends Controller
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

    public function getInventoryStocks(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectInventory::with('project', 'materialType', 'unitType')
            ->whereProjectId($request->project_id ?? '')
            ->whereStatus(ProjectInventory::STATUS['Active'])
            ->orderby('id', $orderBy);

        if (isset($request->material_type_id) && !empty($request->material_type_id)) {
            $query = $query->whereHas('materialType', function ($query) use ($request) {
                $query->whereId($request->material_type_id ?? '');
            });
        }

        if (isset($request->unit_type_id) && !empty($request->unit_type_id)) {
            $query = $query->orWhereHas('unitType', function ($query) use ($request) {
                $query->whereId($request->unit_type_id ?? '');
            });
        }

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
            ], 'Project inventory material List.');
        } else {
            return $this->sendResponse($results, 'Project inventory material List.');
        }
    }

    public function updateMinimunQuntity(Request $request, $projectInventoryId = null)
    {
        $validator = Validator::make($request->all(), [
            'minimum_quantity' => 'required|numeric|gt:0',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                return $this->sendError('Validation Error.', [$key => $value[0]], 400);
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

        return $this->sendResponse([], 'Project inventory updated successfully.');
    }

    public function getMinimumQuantity(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectInventory::with('materialType', 'unitType')
            ->whereStatus(ProjectInventory::STATUS['Active'])
            ->whereProjectId($request->project_id ?? '')
            ->where('minimum_quantity', '>', 0)
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
            ], 'Project inventory minimum quantity List.');
        } else {
            return $this->sendResponse($results, 'Project inventory minimum quantity List.');
        }
    }

    public function getMinimumStockAlert(Request $request)
    {
        $user = $request->user();

        if (isset($user) && !empty($user)) {

            $assignedProjectIds = ProjectAssignedUser::whereUserId($user->id)
                ->pluck('project_id');

            $projects = Project::whereIn('id', $assignedProjectIds)->select('id', 'uuid', 'name')->get();

            $minimumQuantityArr = [];

            if (isset($projects) && !empty($projects)) {
                foreach ($projects as $key => $value) {
                    $minimumQuantity = ProjectInventory::whereStatus(ProjectInventory::STATUS['Active'])
                        ->where('project_id', $value->id)
                        ->where('minimum_quantity', '>', 0)->get();

                    if (isset($minimumQuantity) && !empty($minimumQuantity)) {
                        $minimumQuantityArr[$key] = [
                            'project_id' => $value->id,
                            'project_uuid' => $value->uuid,
                            'project_name' => $value->name,
                            'count' => isset($minimumQuantity) ? $minimumQuantity->count() : 0
                        ];
                    }
                }
                if (isset($minimumQuantityArr) && !empty($minimumQuantityArr)) {
                    return $this->sendResponse($minimumQuantityArr, 'Get Minimum stock alert.');
                } else {
                    return $this->sendError('Minimum stock not found.', [], 204);
                }
            } else {
                return $this->sendError('Project does not exist.', [], 404);
            }
        } else {
            return $this->sendError('User does not exists.', [], 404);
        }
    }
}
