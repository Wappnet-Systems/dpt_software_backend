<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\System\SubModule;
use App\Models\Tenant\RoleHasSubModule;
use App\Helpers\AppHelper;
use App\Models\System\Module;
use App\Models\System\RoleHasModule;

class RoleController extends Controller
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

    public function getRoleSubModulePermissions(Request $request, $roleId = null)
    {
        $user = $request->user();
        
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        AppHelper::setDefaultDBConnection(true);

        $assignModuleIds = RoleHasModule::whereOrganizationId($user->organization_id)->pluck('module_id');

        $query = Module::with('subModule')
            ->whereIn('id', $assignModuleIds)
            ->orderBy('id', $orderBy);

        AppHelper::setDefaultDBConnection();

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $roles = $query->cursorPaginate($limit)->toArray();
        } else {
            $roles['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($roles['data'])) {
            foreach ($roles['data'] as $key => $value) {
                foreach ($value['sub_module'] as $subKey => $subValue) {
                    $roles['data'][$key]['sub_module'][$subKey]['role_has_sub_modules'] = RoleHasSubModule::select('is_list', 'is_create', 'is_edit', 'is_delete', 'is_view', 'is_comment')
                        ->whereRoleId($request->roleId)
                        ->whereSubModuleId($subValue['id'])
                        ->first();

                    if (empty($roles['data'][$key]['sub_module'][$subKey]['role_has_sub_modules'])) {
                        $roles['data'][$key]['sub_module'][$subKey]['role_has_sub_modules'] = [
                            "is_list" => 0,
                            "is_create" => 0,
                            "is_edit" => 0,
                            "is_delete" => 0,
                            "is_view" => 0,
                            "is_comment" => 0
                        ];
                    }
                }
            }

            $results = $roles['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $roles['per_page'],
                'next_page_url' => ltrim(str_replace($roles['path'], "", $roles['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($roles['path'], "", $roles['prev_page_url']), "?cursor=")
            ], 'Organization List');
        } else {
            return $this->sendResponse($results, 'Sub module permissions list');
        }
    }

    public function changeRoleSubModulePermissions(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'role_id' => 'required|exists:system.roles,id',
                    'sub_module_permission' => 'required|json',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                if (in_array($request->role_id, [User::USER_ROLE['SUPER_ADMIN'], User::USER_ROLE['COMPANY_ADMIN']])) {
                    return $this->sendError('You have no rights to add User.');
                }

                $request->merge(['sub_module_permission' => json_decode($request->sub_module_permission, true)]);

                /* if (!isset($request->sub_module_permission) || empty($request->sub_module_permission)) {
                    return $this->sendError('Please choose permissions.');
                } */

                $assignPerQuery = RoleHasSubModule::whereRoleId($request->role_id);

                if ($assignPerQuery->exists()) {
                    $assignPerQuery->delete();
                }

                // Assign sub module permission to role of organization
                foreach ($request->sub_module_permission as $key => $value) {
                    $roleHasSubModule = new RoleHasSubModule();
                    $roleHasSubModule->role_id = $request->role_id;
                    $roleHasSubModule->sub_module_id = $value['sub_module_id'];
                    $roleHasSubModule->is_list = isset($value['is_list']) ? $value['is_list'] : false;
                    $roleHasSubModule->is_create = isset($value['is_create']) ? $value['is_create'] : false;
                    $roleHasSubModule->is_edit = isset($value['is_edit']) ? $value['is_edit'] : false;
                    $roleHasSubModule->is_delete = isset($value['is_delete']) ? $value['is_delete'] : false;
                    $roleHasSubModule->is_view = isset($value['is_view']) ? $value['is_view'] : false;
                    $roleHasSubModule->is_comment = isset($value['is_comment']) ? $value['is_comment'] : false;
                    $roleHasSubModule->save();
                }

                return $this->sendResponse([], 'Sub module permissions assigned successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function getAssignSubModulesByLoginUser(Request $request)
    {
        $user = $request->user();

        $assSubModule = RoleHasSubModule::with('subModule')
            ->whereRoleId($user->role_id ?? null)
            ->get()
            ->toArray();

        return $this->sendResponse($assSubModule, 'Sub module permissions list');
    }
}
