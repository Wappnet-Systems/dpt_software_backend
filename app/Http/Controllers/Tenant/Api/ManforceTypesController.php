<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\ManforceType;
use App\Helpers\AppHelper;

class ManforceTypesController extends Controller
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

                AppHelper::setDefaultDBConnection();
            }

            return $next($request);
        });
    }

    public function getManforceTypes(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = ManforceType::whereStatus(ManforceType::STATUS['Active'])
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
        }

        $totalQuery = $query;
        $totalQuery = $totalQuery->count();

        if ($request->exists('cursor')) {
            $manforceTypes = $query->cursorPaginate($limit)->toArray();
        } else {
            $manforceTypes['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($manforceTypes['data'])) {
            $results = $manforceTypes['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'total' => $totalQuery,
                'per_page' => $manforceTypes['per_page'],
                'next_page_url' => ltrim(str_replace($manforceTypes['path'], "", $manforceTypes['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($manforceTypes['path'], "", $manforceTypes['prev_page_url']), "?cursor=")
            ], 'Manforce Type List');
        } else {
            return $this->sendResponse($results, 'Manforce Type List');
        }
    }

    public function getDetails(Request $request)
    {
        $manforceType = ManforceType::whereId($request->id)->first();

        if (!isset($manforceType) || empty($manforceType)) {
            return $this->sendError('manforce type does not exists.');
        }

        return $this->sendResponse($manforceType, 'Manforce type details.');
    }

    public function addManforceType(Request $request)
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

            $manforceType = new ManforceType();
            $manforceType->name = $request->name;
            $manforceType->created_ip = $request->ip();
            $manforceType->updated_ip = $request->ip();

            if (!$manforceType->save()) {
                return $this->sendError('Something went wrong while creating the manforce type.');
            }

            return $this->sendResponse($manforceType, 'Manforce type created successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateManforceType(Request $request, $id = null)
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

            $manforceType = ManforceType::whereId($request->id)->first();

            if (!isset($manforceType) || empty($manforceType)) {
                return $this->sendError('Manforce Type does not exists.');
            }

            if ($request->filled('name')) $manforceType->name = $request->name;
            $manforceType->updated_ip = $request->ip();

            if (!$manforceType->save()) {
                return $this->sendError('Something went wrong while updating the manforce type.');
            }

            return $this->sendResponse($manforceType, 'Manforce type details updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeStatus(Request $request, $id = null)
    {
        try {
            $manforceType = ManforceType::whereId($request->id)->first();

            if (!isset($manforceType) || empty($manforceType)) {
                return $this->sendError('Manforce Type does not exists.');
            }

            $manforceType->deleted_at = null;
            $manforceType->status = $request->status;
            $manforceType->save();

            if ($manforceType->status == ManforceType::STATUS['Deleted']) {
                $manforceType->delete();
            }

            return $this->sendResponse($manforceType, 'Status changed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
