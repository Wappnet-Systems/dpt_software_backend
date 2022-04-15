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
use App\Models\Tenant\ProjectMaterialRaisingRequest;
use App\Helpers\AppHelper;

class MaterialRaisingRequestsController extends Controller
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

    public function getMaterialRaisingRequests(Request $request)
    {
    }

    public function getMaterialRaisingRequestDetails(Request $request)
    {
    }

    public function addMaterialRaisingRequest(Request $request)
    {
        try {
            $user = $request->user();

            if (isset($user) && !empty($user)) {
                $validator = Validator::make($request->all(), [
                    'project_id' => 'required|exists:projects,id',
                    'material_type_id' => 'required|exists:material_types,id',
                    'unit_type_id' => 'required|exists:unit_types,id',
                    'quantity' => 'required|gt:0'
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $key => $value) {
                        return $this->sendError('Validation Error.', [$key => $value[0]]);
                    }
                }

                $materialRaisingRequest = new ProjectMaterialRaisingRequest();
                $materialRaisingRequest->project_id = $request->project_id;
                $materialRaisingRequest->material_type_id = $request->material_type_id;
                $materialRaisingRequest->unit_type_id = $request->unit_type_id;
                $materialRaisingRequest->quantity = $request->quantity;
                $materialRaisingRequest->created_ip = $request->ip();
                $materialRaisingRequest->updated_ip = $request->ip();

                if (!$materialRaisingRequest->save()) {
                    return $this->sendError('Something went wrong while creating the project material raising request.');
                }

                return $this->sendResponse($materialRaisingRequest, 'Project material raising request created successfully');
            } else {
                return $this->sendError('User does not exist');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateMaterialRaisingRequest(Request $request)
    {
    }

    public function deleteMaterialRaisingRequest(Request $request)
    {
    }

    public function changeMaterialRaisingRequestStatus(Request $request)
    {
    }
}
