<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\UnitType;
use App\Helpers\AppHelper;
use App\Models\Tenant\RoleHasSubModule;
use Illuminate\Support\Facades\Log;

class UnitTypesController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.', [], 401);
                }

                // if (!AppHelper::roleHasModulePermission('Masters', $user)) {
                //     return $this->sendError('You have no rights to access this module.', [], 401);
                // }

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

    public function getUnitTypes(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Unit Type Management', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = UnitType::whereStatus(UnitType::STATUS['Active'])
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
        }

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $unitTypes = $query->cursorPaginate($limit)->toArray();
        } else {
            $unitTypes['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($unitTypes['data'])) {
            $results = $unitTypes['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $unitTypes['per_page'],
                'next_page_url' => ltrim(str_replace($unitTypes['path'], "", $unitTypes['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($unitTypes['path'], "", $unitTypes['prev_page_url']), "?cursor=")
            ], 'Unit Type List');
        } else {
            return $this->sendResponse($results, 'Unit Type List');
        }
    }

    public function getDetails(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Unit Type Management', RoleHasSubModule::ACTIONS['view'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $unitTypes = UnitType::select('id', 'name', 'status')
            ->whereStatus(UnitType::STATUS['Active'])
            ->whereId($request->id)
            ->first();

        if (!isset($unitTypes) || empty($unitTypes)) {
            return $this->sendError('Unit type does not exists.');
        }

        return $this->sendResponse($unitTypes, 'Unit type details.');
    }

    public function addUnitType(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {

                // if (!AppHelper::roleHasSubModulePermission('Unit Type Management', RoleHasSubModule::ACTIONS['create'], $user)) {
                //     return $this->sendError('You have no rights to access this action.', [], 401);
                // }

                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $unitType = new UnitType();
                $unitType->name = $request->name;
                $unitType->created_ip = $request->ip();
                $unitType->updated_ip = $request->ip();

                if (!$unitType->save()) {
                    return $this->sendError('Something went wrong while creating the unit Type.');
                }

                return $this->sendResponse([], 'Unit Type created successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateUnitType(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {

                // if (!AppHelper::roleHasSubModulePermission('Unit Type Management', RoleHasSubModule::ACTIONS['edit'], $user)) {
                //     return $this->sendError('You have no rights to access this action.', [], 401);
                // }

                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $unitTypes = UnitType::whereId($request->id)->first();

                if (!isset($unitTypes) || empty($unitTypes)) {
                    return $this->sendError('Unit type does not exists.');
                }

                if ($request->filled('name')) $unitTypes->name = $request->name;
                $unitTypes->updated_ip = $request->ip();

                if (!$unitTypes->save()) {
                    return $this->sendError('Something went wrong while updating the unit Type.');
                }

                return $this->sendResponse([], 'Unit Type details updated successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeStatus(Request $request, $id = null)
    {
        try {
            // $user = $request->user();

            // if ($request->status == UnitType::STATUS['Deleted']) {
            //     if (!AppHelper::roleHasSubModulePermission('Unit Type Management', RoleHasSubModule::ACTIONS['delete'], $user)) {
            //         return $this->sendError('You have no rights to access this action.', [], 401);
            //     }
            // }

            $unitTypes = UnitType::whereId($request->id)->first();

            if (!isset($unitTypes) || empty($unitTypes)) {
                return $this->sendError('Unit type does not exists.');
            }

            if (!in_array($request->status, User::STATUS)) {
                return $this->sendError('Invalid status requested.');
            }

            $unitTypes->deleted_at = null;
            $unitTypes->status = $request->status;
            $unitTypes->save();

            if ($unitTypes->status == UnitType::STATUS['Deleted']) {
                $unitTypes->delete();
            }

            return $this->sendResponse($unitTypes, 'Status changed successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
