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

    public function getMethodStatements(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        try {
            $query = MethodStatement::whereProjectId($request->project_id ?? '')
                ->select('id', 'project_id', 'project_activity_id', 'path')
                ->orderBy('id', $orderBy);

            $totalQuery = $query;
            $totalQuery = $totalQuery->count();

            if (isset($request->project_activity_id) && !empty($request->project_activity_id)) {
                $query->where('project_activity_id', $request->project_activity_id);
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
            return $this->sendError($e->getMessage());
        }
    }

    public function getmethodStatementDetails(Request $request, $id = null)
    {
        $methodStatement = MethodStatement::whereId($request->id)
            ->select('id', 'project_id', 'project_activity_id', 'path')
            ->first();

        if (!isset($methodStatement) || empty($methodStatement)) {
            return $this->sendError('Method statement does not exists.');
        }

        return $this->sendResponse($methodStatement, 'Method statement details.');
    }

    public function addMethodStatement(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'path' => 'required|mimes:pdf,jpg,jpeg,png|max:10240',
                ], [
                    'path.max' => 'The file must not be greater than 10mb.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $methodStatement = new MethodStatement();
                $methodStatement->project_id = $request->project_id;
                if ($request->hasFile('path')) {
                    $dirPath = str_replace([':uid:'], [$user->organization_id], config('constants.organizations.projects.method_statements.file_path'));

                    $methodStatement->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');
                }

                $methodStatement->created_ip = $request->ip();
                $methodStatement->updated_ip = $request->ip();

                if (!$methodStatement->save()) {
                    return $this->sendError('Something went wrong while creating the method statement.');
                }

                return $this->sendResponse($methodStatement, 'Method statement created successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateMethodStatement(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'path' => 'mimes:pdf,jpg,jpeg,png|max:10240',
                ], [
                    'path.max' => 'The file must not be greater than 10mb.',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $methodStatement = MethodStatement::whereId($request->id)
                    ->first();

                if (!isset($methodStatement) || empty($methodStatement)) {
                    return $this->sendError('Method statement does not exists.');
                }

                $oldPath = $methodStatement->path;

                if ($request->hasFile('path')) {

                    $dirPath = str_replace([':uid:'], [$user->organization_id], config('constants.organizations.projects.method_statements.file_path'));

                    $methodStatement->path = $this->uploadFile->uploadFileInS3($request, $dirPath, 'path');
                }

                if (!$methodStatement->save()) {
                    return $this->sendError('Something went wrong while updating the method statement.');
                }

                if (isset($oldPath) && !empty($oldPath)) {
                    $this->uploadFile->deleteFileFromS3($oldPath);
                }

                return $this->sendResponse($methodStatement, 'Method statement updated successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function deleteMethodStatement(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $methodStatement = MethodStatement::whereId($request->id)
                    ->whereNull('project_activity_id')
                    ->first();

                if (!isset($methodStatement) || empty($methodStatement)) {
                    return $this->sendError('Method statement does not exists.');
                }

                $methodStatement->delete();

                return $this->sendResponse([], 'Method statement deleted Successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /*  */
    public function updateMethodActivity(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_activity_id' => 'required|exists:projects_activities,id',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $methodStatement = MethodStatement::whereId($request->id)
                    ->first();

                if (!isset($methodStatement) || empty($methodStatement)) {
                    return $this->sendError('Method statement does not exists.');
                }

                if ($request->filled('project_activity_id')) $methodStatement->project_activity_id = $request->project_activity_id;
                $methodStatement->updated_ip = $request->ip();

                if (!$methodStatement->save()) {
                    return $this->sendError('Something went wrong while updating the Method statement activity.');
                }

                return $this->sendResponse($methodStatement, 'Method statement activity updated successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function FunctionName1(Request $request)
    {
        # code...
    }
}
