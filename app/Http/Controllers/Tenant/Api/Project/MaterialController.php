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

    public function getMaterials(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ProjectMaterial::with(['unitType'])->whereStatus(ProjectMaterial::STATUS['Active'])
            ->orderby('id', $orderBy);

        if (isset($request->projects_id) && !empty($request->projects_id)) {
            $query = $query->with(['project']);
        }

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
                'per_page' => $projectMaterial['per_page'],
                'next_page_url' => $projectMaterial['next_page_url'],
                'prev_page_url' => $projectMaterial['prev_page_url']
            ], 'Project material List.');
        } else {
            return $this->sendResponse($results, 'Project material List.');
        }
    }

    public function getMaterialsDetails(Request $request)
    {
        $projectMaterial = ProjectMaterial::with(['unitType'])->select('id', 'projects_id', 'unit_type_id', 'quantity', 'cost', 'status')
            ->whereId($request->id)
            ->first();

        if (!isset($projectMaterial) || empty($projectMaterial)) {
            return $this->sendError('Project material does not exist.');
        }

        return $this->sendResponse([$projectMaterial], 'Project material details.');
    }

    public function addMaterial(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'projects_id' => 'required|exists:projects,id',
                    'unit_type_id' => 'required|exists:unit_types,id',
                    'quantity' => 'required',
                    'cost' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $projectMaterial = new ProjectMaterial();
                $projectMaterial->projects_id = $request->projects_id;
                $projectMaterial->unit_type_id = $request->unit_type_id;
                $projectMaterial->quantity = $request->quantity;
                $projectMaterial->cost = $request->cost;
                $projectMaterial->created_by = $user->id;
                $projectMaterial->created_ip = $request->ip();
                $projectMaterial->updated_ip = $request->ip();

                if (!$projectMaterial->save()) {
                    return $this->sendError('Something went wrong while creating the Project material.');
                }

                $projectInventory = new ProjectInventory();
                $projectInventory->projects_id = $projectMaterial->projects_id;
                $projectInventory->project_material_id = $projectMaterial->id;
                $projectInventory->unit_type_id = $projectMaterial->unit_type_id;
                $projectInventory->total_quantity = $projectMaterial->quantity;
                $projectInventory->average_cost = $projectMaterial->cost;
                $projectInventory->assigned_quantity = 0;
                $projectInventory->remaining_quantity = $projectMaterial->quantity;
                $projectInventory->minimum_quantity = 0;
                $projectInventory->created_ip = $request->ip();
                $projectInventory->save();

                return $this->sendResponse($projectMaterial, 'Project material created successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateMaterial(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'id' => 'required',
                    'unit_type_id' => 'required|exists:unit_types,id',
                    'quantity' => 'required',
                    'cost' => 'required',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $projectMaterial =  ProjectMaterial::whereId($request->id)->first();

                if (!isset($projectMaterial) || empty($projectMaterial)) {
                    return $this->sendError('Project material does not exist.');
                }

                if ($request->filled('unit_type_id')) $projectMaterial->unit_type_id = $request->unit_type_id;
                if ($request->filled('quantity')) $projectMaterial->quantity = $request->quantity;
                if ($request->filled('cost')) $projectMaterial->cost = $request->cost;
                $projectMaterial->updated_by = $user->id;
                $projectMaterial->updated_ip = $request->ip();

                if (!$projectMaterial->save()) {
                    return $this->sendError('Something went wrong while udating the project material.');
                }

                $projectInventoryExists = ProjectInventory::whereProjectsId($projectMaterial->projects_id)
                    ->whereProjectMaterialId($projectMaterial->id)
                    ->whereUnitTypeId($projectMaterial->unit_type_id)
                    ->first();

                if (isset($projectInventoryExists) && !empty($projectInventoryExists)) {
                    $projectInventoryExists->total_quantity = $projectInventoryExists->total_quantity + $projectMaterial->quantity;
                    $projectInventoryExists->remaining_quantity = $projectInventoryExists->remaining_quantity + $projectMaterial->quantity;
                    $projectInventoryExists->average_cost = ProjectInventory::averageCost($projectMaterial->cost, $projectMaterial->quantity, $projectInventoryExists->total_quantity, $projectInventoryExists->average_cost);
                    $projectInventoryExists->updated_ip = $request->ip();
                    $projectInventoryExists->save();
                } else {
                    $projectInventory = new ProjectInventory();
                    $projectInventory->projects_id = $projectMaterial->projects_id;
                    $projectInventory->project_material_id = $projectMaterial->id;
                    $projectInventory->unit_type_id = $projectMaterial->unit_type_id;
                    $projectInventory->total_quantity = $projectMaterial->quantity;
                    $projectInventory->average_cost = $projectMaterial->cost;
                    $projectInventory->assigned_quantity = 0;
                    $projectInventory->remaining_quantity = $projectMaterial->quantity;
                    $projectInventory->minimum_quantity = 0;
                    $projectInventory->updated_ip = $request->ip();
                    $projectInventory->save();
                }

                return $this->sendResponse($projectMaterial, 'Project material updated successfully.');
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function deleteMaterial(Request $request)
    {
    }

    public function uploadMaterialFormatFile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'materials' => 'required|mimes:csv,txt',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            if ($request->hasFile('materials')) {
                $filePath = sprintf('%s/%s', config('constants.format_files.material_file.path'), config('constants.format_files.material_file.name'));

                $this->uploadFile->deleteFileFromS3($filePath);

                $path = $this->uploadFile->uploadFileInS3($request, config('constants.format_files.material_file.path'), 'materials', null, null, false);
            }

            return $this->sendResponse(['path' => $path], 'Upload material file successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function exportMaterialFormatFile(Request $request)
    {
        try {
            $filePath = sprintf('%s/%s', config('constants.format_files.material_file.path'), config('constants.format_files.material_file.name'));

            return $this->sendResponse($this->uploadFile->getS3FilePath(null, $filePath), 'Material file Download successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
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
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                if ($request->hasFile('upload_materials')) {
                    Excel::import(new MaterialImport, $request->file('upload_materials'));
                    return $this->sendResponse('Project Material import successfully.');
                }
            } else {
                return $this->sendError('User not exists.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
