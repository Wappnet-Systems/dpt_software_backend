<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use App\Models\System\Organization;
use App\Models\System\User;
use App\Models\Tenant\Machinery;
use App\Helpers\AppHelper;

class MachineriesController extends Controller
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

    public function getMachineries(Request $request)
    {
        $limit = !empty($request->limit) ? $request->limit : config('constants.default_per_page_limit');
        $orderBy = !empty($request->orderby) ? $request->orderby : config('constants.default_orderby');

        $query = Machinery::whereStatus(Machinery::STATUS['Active'])
            ->orderBy('id', $orderBy);

        if (isset($request->search) && !empty($request->search)) {
            $search = trim(strtolower($request->search));

            $query = $query->whereRaw('LOWER(CONCAT(`name`)) LIKE ?', ['%' . $search . '%']);
        }

        if ($request->exists('cursor')) {
            $machineries = $query->cursorPaginate($limit)->toArray();
        } else {
            $machineries['data'] = $query->get()->toArray();
        }

        $results = [];
        if (!empty($machineries['data'])) {
            $results = $machineries['data'];
        }

        if ($request->exists('cursor')) {
            return $this->sendResponse([
                'lists' => $results,
                'per_page' => $machineries['per_page'],
                'next_page_url' => ltrim(str_replace($machineries['path'], "", $machineries['next_page_url']), "?cursor="),
                'prev_page_url' => ltrim(str_replace($machineries['path'], "", $machineries['prev_page_url']), "?cursor=")
            ], 'Machinery List');
        } else {
            return $this->sendResponse($results, 'Machinery List');
        }
    }

    public function getDetails(Request $request)
    {
        $machineries = Machinery::select('id', 'name', 'status')
            ->whereStatus(Machinery::STATUS['Active'])
            ->whereId($request->id)
            ->first();

        if (!isset($machineries) || empty($machineries)) {
            return $this->sendError('Machinery does not exists.');
        }

        return $this->sendResponse($machineries, 'Machinery details.');
    }

    public function addMachineryCategory(Request $request)
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

            $machineries = new Machinery();
            $machineries->name = $request->name;
            $machineries->created_ip = $request->ip();
            $machineries->updated_ip = $request->ip();

            if (!$machineries->save()) {
                return $this->sendError('Something went wrong while creating the machineries.');
            }

            return $this->sendResponse($machineries, 'Machineries created successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateMachineryCategory(Request $request, $id = null)
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

            $machineries = Machinery::whereId($request->id)->first();

            if (!isset($machineries) || empty($machineries)) {
                return $this->sendError('Machinery does not exists.');
            }

            if ($request->filled('name')) $machineries->name = $request->name;
            $machineries->updated_ip = $request->ip();

            if (!$machineries->save()) {
                return $this->sendError('Something went wrong while updating the machinery');
            }

            return $this->sendResponse($machineries, 'Machinery details updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeStatus(Request $request, $id = null)
    {
        try {
            $machineries = Machinery::whereId($request->id)->first();

            if (!isset($machineries) || empty($machineries)) {
                return $this->sendError('Machinery does not exists.');
            }

            $machineries->deleted_at = null;
            $machineries->status = $request->status;
            $machineries->save();

            if ($machineries->status == Machinery::STATUS['Deleted']) {
                $machineries->delete();
            }

            return $this->sendResponse($machineries, 'Status changed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
