<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\UnitType;

class UnitTypesController extends Controller
{
    public function __construct()
    {
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

                Config::set('database.default', 'tenant');
            }

            return $next($request);
        });
    }

    public function getUnitTypes(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = UnitType::whereStatus(UnitType::STATUS['Active'])
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
        }

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
                'per_page' => $unitTypes['per_page'],
                'next_page_url' => $unitTypes['next_page_url'],
                'prev_page_url' => $unitTypes['prev_page_url']
            ], 'Unit Type List');
        } else {
            return $this->sendResponse($results, 'Unit Type List');
        }
    }

    public function getDetails(Request $request)
    {
        $unitTypes = UnitType::select('id', 'name', 'status')->whereStatus(UnitType::STATUS['Active'])->whereId($request->id)->first();

        if (!isset($unitTypes) || empty($unitTypes)) {
            return $this->sendError('Unit type does not exists.');
        }

        return $this->sendResponse($unitTypes, 'Unit type details.');
    }

    public function addUnitType(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            $unitType = new UnitType();
            $unitType->name = $request->name;
            $unitType->created_ip = $request->ip();
            $unitType->updated_ip = $request->ip();

            if (!$unitType->save()) {
                return $this->sendError('Something went wrong while creating the unit Type.');
            }

            return $this->sendResponse($unitType, 'unit Type created successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateUnitType(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
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

            return $this->sendResponse($unitTypes, 'unit Type details updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $value) {
                    return $this->sendError('Validation Error.', [$key => $value[0]]);
                }
            }

            $unitTypes = UnitType::whereId($request->id)->first();

            if (!isset($unitTypes) || empty($unitTypes)) {
                return $this->sendError('Unit type does not exists.');
            }

            $unitTypes->deleted_at = null;
            $unitTypes->status = $request->status;
            $unitTypes->save();
            
            if ($unitTypes->status == UnitType::STATUS['Deleted']) {
                $unitTypes->delete();
            }

            return $this->sendResponse($unitTypes, 'Status changed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
