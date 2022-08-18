<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\MethodStatement;
use App\Helpers\AppHelper;
use App\Helpers\UploadFile;
use App\Models\Tenant\RoleHasSubModule;
use Illuminate\Support\Facades\Log;

class MethodStatementController extends Controller
{
    protected $uploadFile;

    public function __construct()
    {
        $this->uploadFile = new UploadFile();

        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                if ($user->role_id == User::USER_ROLE['SUPER_ADMIN']) {
                    return $this->sendError('You have no rights to access this module.', [], 401);
                }

                // if (!AppHelper::roleHasModulePermission('HSE', $user)) {
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

    public function getMethodStatements(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Method Statement', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        try {
            $query = MethodStatement::with('projectActivity')
                ->whereProjectId($request->project_id ?? '')
                ->select('id', 'project_id', 'project_activity_id', 'name', 'path', 'updated_at')
                ->orderBy('id', $orderBy);

            $totalQuery = $query;
            $totalQuery = $totalQuery->count();

            if (isset($request->project_activity_id) && !empty($request->project_activity_id)) {
                $query->where(function($query) use($request) {
                    $query->orWhere('project_activity_id', $request->project_activity_id);
                    $query->orWhere('project_activity_id', null);
                });
            }

            if ($request->exists('cursor')) {
                $methodStatement = $query->cursorPaginate($limit)->toArray();
            } else {
                $methodStatement['data'] = $query->get()->toArray();
            }

            $results = [];
            if (!empty($methodStatement['data'])) {
                $results = $methodStatement['data'];
            }

            if ($request->exists('cursor')) {
                return $this->sendResponse([
                    'lists' => $results,
                    'total' => $totalQuery,
                    'per_page' => $methodStatement['per_page'],
                    'next_page_url' => ltrim(str_replace($methodStatement['path'], "", $methodStatement['next_page_url']), "?cursor="),
                    'prev_page_url' => ltrim(str_replace($methodStatement['path'], "", $methodStatement['prev_page_url']), "?cursor=")
                ], 'Method statement List.');
            } else {
                return $this->sendResponse($results, 'Method statement List.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function getMethodStatementDetails(Request $request, $id = null)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Method Statement', RoleHasSubModule::ACTIONS['view'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $methodStatement = MethodStatement::with('projectActivity')
            ->whereId($request->id)
            ->select('id', 'project_id', 'project_activity_id', 'name', 'path', 'updated_at')
            ->first();

        if (!isset($methodStatement) || empty($methodStatement)) {
            return $this->sendError('Method statement does not exists.', [], 404);
        }

        return $this->sendResponse($methodStatement, 'Method statement details.');
    }

    public function addMethodStatement(Request $request)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Method Statement', RoleHasSubModule::ACTIONS['create'], $user)) {
            //     return $this->sendError('You have no rights to access this action.', [], 401);
            // }

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'name' => 'required',
                    'path' => 'required|mimes:pdf,jpg,jpeg,png|max:10240',
                ], [
                    'path.max' => 'The file must not be greater than 10mb.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $methodStatement = new MethodStatement();
                $methodStatement->project_id = $request->project_id;
                $methodStatement->name = $request->name;

                if ($request->hasFile('path')) {
                    $dirPath = str_replace([':uid:'], [$user->organization_id], config('constants.organizations.projects.method_statements.file_path'));

                    $methodStatement->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');
                }

                $methodStatement->created_ip = $request->ip();
                $methodStatement->updated_ip = $request->ip();

                if (!$methodStatement->save()) {
                    return $this->sendError('Something went wrong while creating the method statement.', [], 500);
                }

                return $this->sendResponse([], 'Method statement created successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateMethodStatement(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Method Statement', RoleHasSubModule::ACTIONS['edit'], $user)) {
            //     return $this->sendError('You have no rights to access this action.', [], 401);
            // }

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    'path' => 'mimes:pdf,jpg,jpeg,png|max:10240',
                ], [
                    'path.max' => 'The file must not be greater than 10mb.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $methodStatement = MethodStatement::whereId($request->id)->first();

                if (!isset($methodStatement) || empty($methodStatement)) {
                    return $this->sendError('Method statement does not exists.', [], 404);
                }

                if ($request->filled('name')) $methodStatement->name = $request->name;

                $oldPath = $methodStatement->path;
                if ($request->hasFile('path')) {
                    $dirPath = str_replace([':uid:'], [$user->organization_id], config('constants.organizations.projects.method_statements.file_path'));

                    $methodStatement->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');
                }

                if (!$methodStatement->save()) {
                    return $this->sendError('Something went wrong while updating the method statement.', [], 500);
                }

                if (isset($oldPath) && !empty($oldPath)) {
                    $this->uploadFile->deleteFileFromS3($oldPath);
                }

                return $this->sendResponse([], 'Method statement updated successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function deleteMethodStatement(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Method Statement', RoleHasSubModule::ACTIONS['delete'], $user)) {
            //     return $this->sendError('You have no rights to access this action.', [], 401);
            // }

            if (isset($user) && !empty($user)) {
                $methodStatement = MethodStatement::whereId($request->id)
                    ->whereNull('project_activity_id')
                    ->first();

                if (!isset($methodStatement) || empty($methodStatement)) {
                    return $this->sendError('You can not delete assigned method statement or not exists.', [], 401);
                }

                $methodStatement->delete();

                return $this->sendResponse([], 'Method statement deleted Successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function assignMethodStatementToActivity(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Method Statement', RoleHasSubModule::ACTIONS['assign'], $user)) {
            //     return $this->sendError('You have no rights to access this action.', [], 401);
            // }

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'project_activity_id' => 'required|exists:projects_activities,id',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $request->method_statement_ids = !empty($request->method_statement_ids) ? explode(',', $request->method_statement_ids) : [];

                if (isset($request->method_statement_ids) && !empty($request->method_statement_ids)) {
                    $methodStatement = MethodStatement::whereIn('id', $request->method_statement_ids)
                        ->update([
                            'project_activity_id' => $request->project_activity_id,
                            'updated_ip' => $request->ip()
                        ]);
                } else {
                    $methodStatement = MethodStatement::whereProjectId($request->project_id)
                        ->whereProjectActivityId($request->project_activity_id)
                        ->update([
                            'project_activity_id' => null,
                            'updated_ip' => $request->ip()
                        ]);
                }

                return $this->sendResponse([], 'Assigned activity to method statement successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
