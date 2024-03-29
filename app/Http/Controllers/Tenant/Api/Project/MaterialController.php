<?php

namespace App\Http\Controllers\Tenant\Api\Project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ProjectMaterial;
use App\Models\Tenant\ProjectInventory;
use App\Helpers\AppHelper;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MaterialImport;
use App\Helpers\UploadFile;
use App\Models\Tenant\RoleHasSubModule;
use Illuminate\Support\Facades\Log;

class MaterialController extends Controller
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

                // if (!AppHelper::roleHasModulePermission('Planning and Scheduling', $user)) {
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

    public function getMaterials(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Material Management', RoleHasSubModule::ACTIONS['list'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }
        
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectMaterial::with('project', 'unitType', 'materialType')
            ->whereProjectId($request->project_id ?? '')
            ->orderby('id', $orderBy);

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $projectMaterial = $query->cursorPaginate($limit)->toArray();
        } else {
            $projectMaterial['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($projectMaterial['data'])) {
            $results = $projectMaterial['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $projectMaterial['per_page'],
                'next_page_url' => ltrim(str_replace($projectMaterial['path'], "", $projectMaterial['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($projectMaterial['path'], "", $projectMaterial['prev_page_url']), "?cursor=")
            ], 'Project material List.');
        } else {
            return $this->sendResponse($results, 'Project material List.');
        }
    }

    public function getMaterialsDetails(Request $request)
    {
        $user = $request->user();

        // if (!AppHelper::roleHasSubModulePermission('Material Management', RoleHasSubModule::ACTIONS['view'], $user)) {
        //     return $this->sendError('You have no rights to access this action.', [], 401);
        // }

        $projectMaterial = ProjectMaterial::with('project', 'unitType', 'materialType')
            ->whereId($request->id)
            ->first();

        if (!isset($projectMaterial) || empty($projectMaterial)) {
            return $this->sendError('Project material does not exist.');
        }

        return $this->sendResponse($projectMaterial, 'Project material details.');
    }

    public function addMaterial(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Material Management', RoleHasSubModule::ACTIONS['create'], $user)) {
                //     return $this->sendError('You have no rights to access this action.', [], 401);
                // }

                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'material_type_id' => 'required|exists:material_types,id',
                    'unit_type_id' => 'required|exists:unit_types,id',
                    'quantity' => 'required|numeric|gt:0',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $projectMaterial = new ProjectMaterial();
                $projectMaterial->project_id = $request->project_id;
                $projectMaterial->material_type_id = $request->material_type_id;
                $projectMaterial->unit_type_id = $request->unit_type_id;
                $projectMaterial->quantity = $request->quantity;
                $projectMaterial->cost = $request->cost;
                $projectMaterial->created_by = $user->id;
                $projectMaterial->created_ip = $request->ip();
                $projectMaterial->updated_ip = $request->ip();

                if (!$projectMaterial->save()) {
                    return $this->sendError('Something went wrong while creating the project material.');
                }

                if (isset($projectMaterial->cost) && !empty($projectMaterial->cost)) {
                    ProjectMaterial::projectInventoryCostCalculation($projectMaterial);
                }

                return $this->sendResponse([], 'Project material created successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function updateMaterial(Request $request, $id = null)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                // if (!AppHelper::roleHasSubModulePermission('Material Management', RoleHasSubModule::ACTIONS['edit'], $user)) {
                //     return $this->sendError('You have no rights to access this action.', [], 401);
                // }

                $validator = Validator::make($request->all(), [
                    'material_type_id' => 'required|exists:material_types,id',
                    'unit_type_id' => 'required|exists:unit_types,id',
                    'quantity' => 'required|numeric|gt:0',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                $projectMaterial = ProjectMaterial::whereId($request->id)->first();

                if (!isset($projectMaterial) || empty($projectMaterial)) {
                    return $this->sendError('Project material does not exist.');
                }

                $projectInventory = ProjectInventory::whereProjectId($projectMaterial->project_id)
                    ->whereMaterialTypeId($projectMaterial->material_type_id)
                    ->whereUnitTypeId($projectMaterial->unit_type_id)
                    ->first();

                if (isset($projectMaterial->cost) && !empty($projectMaterial->cost)) {
                    if (isset($projectInventory) && !empty($projectInventory)) {
                        $projectInventory->average_cost = ProjectInventory::reCalcAverageCost($projectInventory->remaining_quantity, $projectInventory->average_cost, $projectMaterial->quantity, $projectMaterial->cost);
                        $projectInventory->total_quantity = $projectInventory->total_quantity - $projectMaterial->quantity;
                        $projectInventory->remaining_quantity = $projectInventory->remaining_quantity - $projectMaterial->quantity;
                        $projectInventory->save();
                    }
                }

                if ($request->filled('unit_type_id')) $projectMaterial->unit_type_id = $request->unit_type_id;
                if ($request->filled('quantity')) $projectMaterial->quantity = $request->quantity;
                if ($request->filled('cost')) $projectMaterial->cost = $request->cost;
                $projectMaterial->updated_by = $user->id;
                $projectMaterial->updated_ip = $request->ip();

                if (!$projectMaterial->save()) {
                    return $this->sendError('Something went wrong while updating the project material.');
                }

                if (isset($request->cost) && !empty($request->cost)) {
                    ProjectMaterial::projectInventoryCostCalculation($projectMaterial);
                }

                return $this->sendResponse([], 'Project material updated successfully.');
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function deleteMaterial(Request $request)
    {
        try {
            $user = $request->user();

            // if (!AppHelper::roleHasSubModulePermission('Material Management', RoleHasSubModule::ACTIONS['delete'], $user)) {
            //     return $this->sendError('You have no rights to access this action.', [], 401);
            // }

            $projectMaterial = ProjectMaterial::whereId($request->id)->first();

            if (!isset($projectMaterial) || empty($projectMaterial)) {
                return $this->sendError('Project material does not exist.');
            }

            $projectInventory = ProjectInventory::whereProjectId($projectMaterial->project_id)
                ->whereMaterialTypeId($projectMaterial->material_type_id)
                ->whereUnitTypeId($projectMaterial->unit_type_id)
                ->first();

            if (isset($projectInventory) && !empty($projectInventory)) {
                $projectInventory->average_cost = ProjectInventory::reCalcAverageCost($projectInventory->remaining_quantity, $projectInventory->average_cost, $projectMaterial->quantity, $projectMaterial->cost);
                $projectInventory->total_quantity = $projectInventory->total_quantity - $projectMaterial->quantity;
                $projectInventory->remaining_quantity = 0;
                $projectInventory->updated_ip = $request->ip();
                $projectInventory->save();

                /* if ($projectInventory->total_quantity == 0) {
                    $projectInventory->delete();
                } */
            }

            $projectMaterial->delete();

            return $this->sendResponse([], 'Project material deleted successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function uploadMaterialFormatFile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'materials' => 'required|mimes:csv,txt',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                }
            }

            if ($request->hasFile('materials')) {
                $filePath = sprintf('%s/%s', config('constants.format_files.material_file.path'), config('constants.format_files.material_file.name'));

                $this->uploadFile->deleteFileFromS3($filePath);

                $path = $this->uploadFile->uploadFileInS3($request, config('constants.format_files.material_file.path'), 'materials', null, null, false);
            }

            return $this->sendResponse(['path' => $path], 'Upload material file successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function exportMaterialFormatFile(Request $request)
    {
        try {
            $filePath = sprintf('%s/%s', config('constants.format_files.material_file.path'), config('constants.format_files.material_file.name'));

            return $this->sendResponse($this->uploadFile->getS3FilePath(null, $filePath), 'Material file download successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }

    public function importMaterial(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'upload_materials' => 'required|mimes:csv,txt',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]], 400);
                    }
                }

                if ($request->hasFile('upload_materials')) {
                    Excel::import(new MaterialImport, $request->file('upload_materials'));

                    return $this->sendResponse([], 'Project Material import successfully.');
                }
            } else {
                return $this->sendError('User not exists.', [], 404);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->sendError('Something went wrong!', [], 500);
        }
    }
}
