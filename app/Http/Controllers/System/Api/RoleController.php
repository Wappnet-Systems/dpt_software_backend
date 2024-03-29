<?php

namespace App\Http\Controllers\System\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\System\User;
use App\Models\System\Role;
use App\Models\System\Module;
use App\Models\System\RoleHasModule;
use App\Models\System\SubModule;
use App\Models\Tenant\RoleHasSubModule;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    public function getRoles(Request $request)
    {
        $user = $request->user();

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = Role::where('status', Role::STATUS['Active'])
            // ->where('id', '!=', User::USER_ROLE['SUPER_ADMIN'])
            // ->where('id', '!=', $user->role_id)
            ->orderBy('id', $orderBy);

        if (isset(USER::USER_ROLE_GROUP[$user->role_id])) {
            $query->whereIn('id', USER::USER_ROLE_GROUP[$user->role_id]);
        }

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
        }

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $roles = $query->cursorPaginate($limit)->toArray();
        } else {
            $roles['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($roles['data'])) {
            $results = $roles['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $roles['per_page'],
                'next_page_url' => ltrim(str_replace($roles['path'], "", $roles['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($roles['path'], "", $roles['prev_page_url']), "?cursor=")
            ], 'Roles List');
        } else {
            return $this->sendResponse($results, 'Roles List');
        }
    }

    public function getRoleDetails(Request $request)
    {
        $role = Role::select('id', 'name', 'status')
            ->where('id', '!=', User::USER_ROLE['SUPER_ADMIN'])
            ->whereId($request->id)
            ->first();

        if (!isset($role) || empty($role)) {
            return $this->sendError('Role does not exists.');
        }

        return $this->sendResponse($role, 'Role details get successfully.');
    }

    public function addRole(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                // Create new organization
                $role = new Role();
                $role->name = $request->name;

                if (!$role->save()) {
                    return $this->sendError('Something went wrong while creating the role.');
                }

                return $this->sendResponse([], 'Role added successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateRole(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $role = Role::whereId($request->id)
                    ->where('id', '!=', User::USER_ROLE['SUPER_ADMIN'])
                    ->first();

                if (!isset($role) || empty($role)) {
                    return $this->sendError('Role does not exists.');
                }

                $role->name = $request->name;

                if (!$role->save()) {
                    return $this->sendError('Something went wrong while creating the role.');
                }

                return $this->sendResponse([], 'Role details updated successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function changeRoleStatus(Request $request, $id = null)
    {
        try {
            $role = Role::whereId($request->id)
                ->where('id', '!=', User::USER_ROLE['SUPER_ADMIN'])
                ->first();

            if (isset($role) && !empty($role)) {
                if (!in_array($request->status, Role::STATUS)) {
                    return $this->sendError('Invalid status requested.');
                }

                if ($request->status == Role::STATUS['Deleted']) {
                    if (User::whereRoleId($request->id)->exists()) {
                        return $this->sendError('You can not delete this role because this role has been assigned to many user.');
                    }

                    $role->delete();
                } else {
                    $role->deleted_at = null;
                }

                $role->status = $request->status;
                $role->save();

                return $this->sendResponse($role, 'Status changed successfully.');
            }

            return $this->sendError('Role does not exists.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function getRoleModulePermissions(Request $request, $orgId = null)
    {
        $user = $request->user();

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = Module::with('subModule')
            ->orderBy('id', 'desc')
            ->isAssigned($request->orgId);

        if ($request->exists('cursor')) {
            $roles = $query->cursorPaginate($limit)->toArray();
        } else {
            $roles['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($roles['data'])) {
            $results = $roles['data'];
        }

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $roles['per_page'],
                'next_page_url' => ltrim(str_replace($roles['path'], "", $roles['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($roles['path'], "", $roles['prev_page_url']), "?cursor=")
            ], 'Roles List');
        } else {
            return $this->sendResponse($results, 'Roles List');
        }
    }

    public function changeRoleModulePermissions(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'module_ids' => 'required',
                    'role_id' => 'required|exists:roles,id',
                    'org_id' => 'required|exists:organizations,id',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $request->module_ids = explode(',', $request->module_ids);

                $isModuleExists = Module::whereIn('id', $request->module_ids)->count();

                if (!isset($isModuleExists) || empty($isModuleExists)) {
                    return $this->sendError('The selected module ids is does not exist.', [], 400);
                }

                $assignPerQuery = RoleHasModule::whereOrganizationId($request->org_id)->whereRoleId($request->role_id);

                if ($assignPerQuery->exists()) {
                    $assignPerQuery->delete();
                }

                // Assign module permission to role of organization
                foreach ($request->module_ids as $moduleId) {
                    $roleHasModule = new RoleHasModule();
                    $roleHasModule->module_id = $moduleId;
                    $roleHasModule->role_id = $request->role_id;
                    $roleHasModule->organization_id = $request->org_id;
                    $roleHasModule->save();
                }

                return $this->sendResponse([], 'Module permissions assigned successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
