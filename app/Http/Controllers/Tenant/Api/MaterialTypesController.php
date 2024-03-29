<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\MaterialType;
use App\Helpers\AppHelper;
use App\Models\Tenant\RoleHasSubModule;
use Illuminate\Support\Facades\Log;

class MaterialTypesController extends Controller
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

    public function getMaterialTypes(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Material Type Management', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = MaterialType::whereStatus(MaterialType::STATUS['Active'])
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
        }

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $materialTypes = $query->cursorPaginate($limit)->toArray();
        } else {
            $materialTypes['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($materialTypes['data'])) {
            $results = $materialTypes['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $materialTypes['per_page'],
                'next_page_url' => ltrim(str_replace($materialTypes['path'], "", $materialTypes['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($materialTypes['path'], "", $materialTypes['prev_page_url']), "?cursor=")
            ], 'Material Type List');
        } else {
            return $this->sendResponse($results, 'Material Type List');
        }
    }

    public function getDetails(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Material Type Management', RoleHasSubModule::ACTIONS['view'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $materialType = MaterialType::whereId($request->id)->first();

        if (!isset($materialType) || empty($materialType)) {
            return $this->sendError('Material type does not exists.');
        }

        return $this->sendResponse($materialType, 'Material type details.');
    }

    public function addMaterialType(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {

                // if (!AppHelper::roleHasSubModulePermission('Material Type Management', RoleHasSubModule::ACTIONS['create'], $user)) {
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

                $materialType = new MaterialType();
                $materialType->name = $request->name;
                $materialType->created_ip = $request->ip();
                $materialType->updated_ip = $request->ip();

                if (!$materialType->save()) {
                    return $this->sendError('Something went wrong while creating the material type.');
                }

                return $this->sendResponse([], 'Material type created successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateMaterialType(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Material Type Management', RoleHasSubModule::ACTIONS['edit'], $user)) {
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

                $materialType = MaterialType::whereId($request->id)->first();

                if (!isset($materialType) || empty($materialType)) {
                    return $this->sendError('Material type dose not exists.');
                }

                if ($request->filled('name')) $materialType->name = $request->name;
                $materialType->updated_ip = $request->ip();

                if (!$materialType->save()) {
                    return $this->sendError('Something went wrong while updating the material type.');
                }

                return $this->sendResponse([], 'Material type details updated successfully.');
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
            $user = $request->user();

            // if ($request->status == MaterialType::STATUS['Deleted']) {
            //     if (!AppHelper::roleHasSubModulePermission('Material Type Management', RoleHasSubModule::ACTIONS['delete'], $user)) {
            //         return $this->sendError('You have no rights to access this action.', [], 401);
            //     }
            // }

            $materialType = MaterialType::whereId($request->id)->first();

            if (!isset($materialType) || empty($materialType)) {
                return $this->sendError('Material type dose not exists.');
            }

            if (!in_array($request->status, MaterialType::STATUS)) {
                return $this->sendError('Invalid status requested.');
            }

            $materialType->deleted_at = null;
            $materialType->status = $request->status;
            $materialType->save();

            if ($materialType->status == MaterialType::STATUS['Deleted']) {
                $materialType->delete();
            }

            return $this->sendResponse($materialType, 'Status changed successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
